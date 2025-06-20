<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Services\CrossSaleService;
use Carbon\Carbon;
use Illuminate\Http\Request;


class SaleController extends Controller
{

    protected $pageTitle;
    protected $products;
    protected $productIds;
    protected $totalPrice;
    protected $productStocks;
    protected $oldStocks;
    protected $saleDetails;
    protected $oldWarehouseId;

    public function __construct()
    {
        $this->pageTitle = 'All Sales';
    }

    protected function getSales()
    {
        return Sale::searchable(['invoice_no', 'due_amount', 'customer:name,mobile', 'warehouse:name'])->dateFilter('sale_date')->with('customer', 'warehouse', 'saleReturn')->orderBy('id', 'desc');
    }

    public function index()
    {
        $pageTitle = $this->pageTitle;
        $sales     = $this->getSales()->paginate(getPaginate());
        $pdfButton = true;
        $routePDF  = route('admin.sale.pdf') . "?";
        $routeCSV  = route('admin.sale.csv') . "?";
        if (request()->search) {
            $routePDF .= "search=" . request()->search . "&";
            $routeCSV .= "search=" . request()->search . "&";
        }
        if (request()->date) {
            $routePDF .= "date=" . request()->date;
            $routeCSV .= "date=" . request()->date;
        }
        return view('admin.sale.index', compact('pageTitle', 'sales', 'pdfButton', 'routePDF', 'routeCSV'));
    }

    public function salePDF()
    {
        $pageTitle = $this->pageTitle;
        $sales     = $this->getSales()->get();
        return downloadPDF('pdf.sale.list', compact('pageTitle', 'sales'));
    }

    public function saleCSV()
    {
        $pageTitle = $this->pageTitle;
        $filename  = $this->downloadCsv($pageTitle, $this->getSales()->get());
        return response()->download(...$filename);
    }

    protected function downloadCsv($pageTitle, $data)
    {
        $filename = "assets/files/csv/example.csv";
        $myFile   = fopen($filename, 'w');
        $column   = "Invoice No.,Date,Customer,Mobile,Total Amount,Warehouse,Discount,Receivable,Paid,Due\n";
        $curSym   = gs('cur_sym');
        foreach ($data as $sale) {
            $invoice        = $sale->invoice_no;
            $date           = showDateTime(@$sale->sale_date, 'd-m-Y');
            $customer       = $sale->customer->name;
            $customerMobile = $sale->customer->mobile;
            $warehouse      = $sale->warehouse->name;
            $totalAmount    = $curSym . getAmount($sale->total_price);
            $discount       = $curSym . getAmount($sale->discount_amount);
            $receivable     = $curSym . getAmount($sale->receivable_amount);
            $received       = $curSym . getAmount($sale->received_amount);
            $due            = $curSym . getAmount($sale->due_amount);

            $column .= "$invoice,$date,$customer,$customerMobile,$totalAmount,$warehouse,$discount,$receivable,$received,$due \n";
        }
        fwrite($myFile, $column);
        $headers = [
            'Content-Type' => 'application/csv',
        ];
        $name  = $pageTitle . time() . '.csv';
        $array = [$filename, $name, $headers];
        return $array;
    }

    public function downloadInvoice($id)
    {
        $pageTitle = "INVOICE";
        $sale      = Sale::where('id', $id)
            ->with([
                'customer',
                'saleDetails',
                'saleDetails.product',
                'saleDetails.product.unit',
                'saleReturn',
                'saleReturn.details',
                'saleReturn.details.product',
                'saleReturn.details.product.unit'
            ])
            ->whereHas('saleDetails')
            ->firstOrFail();
        $customer  = $sale->customer;

        return downloadPDF('pdf.sale.invoice', compact('pageTitle', 'sale', 'customer'));
    }

    public function downloadThermalInvoice($id)
    {
        $pageTitle = "THERMAL INVOICE";
        $sale      = Sale::where('id', $id)
            ->with([
                'customer',
                'saleDetails',
                'saleDetails.product',
                'saleDetails.product.unit',
                'saleReturn',
                'saleReturn.details',
                'saleReturn.details.product',
                'saleReturn.details.product.unit',
                'returnApplications',
                'returnApplications.originalSaleReturn',
                'returnApplications.originalSaleReturn.sale',
                'dueApplications',
                'dueApplications.originalSale'
            ])
            ->whereHas('saleDetails')
            ->firstOrFail();
        $customer  = $sale->customer;

        // Check if this is a direct print request
        if (request()->has('direct_print')) {
            return view('pdf.thermal.direct_print', compact('pageTitle', 'sale', 'customer'));
        }

        return downloadPDF('pdf.thermal.sale_invoice', compact('pageTitle', 'sale', 'customer'));
    }

    public function create()
    {
        $pageTitle  = 'New Sale';
        $warehouses = Warehouse::active()->orderBy('name')->get();

        $lastSale      = Sale::orderBy('id', 'DESC')->first();
        $lastInvoiceNo = @$lastSale->invoice_no;

        $invoiceNumber = generateInvoiceNumber($lastInvoiceNo);

        $customers = Customer::select('id', 'name', 'mobile')->get();

        return view('admin.sale.form', compact('pageTitle', 'warehouses', 'invoiceNumber', 'customers'));
    }

    public function edit($id)
    {
        $sale       = Sale::where('id', $id)->with('saleDetails', 'saleDetails.product.productStock', 'saleDetails.product.unit', 'customer')->whereHas('saleDetails')->firstOrFail();
        $pageTitle  = 'Edit Sale';
        $warehouses = Warehouse::active()->get();
        $customers  = Customer::select('id', 'name', 'mobile')->get();
        return view('admin.sale.form', compact('pageTitle', 'sale', 'warehouses', 'customers'));
    }


    public function store(Request $request)
    {
        $this->validation($request);


        $this->products   = collect($request->products);
        $this->productIds = $this->products->pluck('product_id')->toArray();
        $this->totalPrice = $this->getTotalPrice();

        if ($request->discount > $this->totalPrice) {
            $notify[] = ['error', 'Discount amount mustn\'t be greater than total price'];
            return back()->withNotify($notify)->withInput();
        }
        //warehouse product qty checked
        $this->productStocks = ProductStock::where('warehouse_id', $request->warehouse_id)->whereIn('product_id', $this->productIds)->get();

        foreach ($this->productStocks as $stock) {
            $product = (object) $this->products->where('product_id', $stock->product_id)->first();
            if ($stock->quantity <  $product->quantity) {
                $notify[] = ['error', 'Insufficient Product in the warehouse! Please check the stock'];
                return back()->withNotify($notify)->withInput();
            }
        }

        $lastSale      = Sale::orderBy('id', 'DESC')->first();
        $lastInvoiceNo = @$lastSale->invoice_no;

        $sale             = new Sale();
        $sale->invoice_no = generateInvoiceNumber($lastInvoiceNo);
        $sale             = $this->saveSaleData($sale);
        $this->oldStocks  = collect([]);

        $this->updateStock($sale->id);
        $this->storeSaleDetails($sale);

        Action::newEntry($sale, 'CREATED');

        $notify[] = ['success', 'Sale data added successfully'];
        return to_route('admin.sale.edit', $sale->id)->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $this->validation($request);
        $this->products   = collect($request->products);
        $this->totalPrice = $this->getTotalPrice();
        $this->productIds = $this->products->pluck('product_id')->toArray();

        if ($request->discount > $this->totalPrice) {
            $notify[] = ['error', 'Discount amount mustn\'t greater than total price'];
            return back()->withNotify($notify)->withInput();
        }

        $sale = Sale::with('saleDetails')->findOrFail($id);

        if ($sale->warehouse_id != $request->warehouse_id) {
            $notify[] = ['error', 'You can\'t change the warehouse'];
            return back()->withNotify($notify);
        }

        if ($sale->return_status) {
            $notify[] = ['error', 'You can\'t update this sale'];
            return back()->withNotify($notify);
        }

        $this->productStocks = ProductStock::where('warehouse_id', $request->warehouse_id)
            ->whereIn('product_id', $this->productIds)
            ->get();

        $this->oldStocks = collect([]);

        if ($request->warehouse_id != $sale->warehouse_id) {
            $this->oldStocks = ProductStock::where('warehouse_id', $sale->warehouse_id)->whereIn('product_id', $this->productIds)->get();
        }

        $this->saleDetails    = $sale->saleDetails;
        $this->oldWarehouseId = $sale->warehouse_id;

        // If increase the quantity then we need to check the current stock.
        $checkStock = $this->checkStockAvailability();

        if (!empty($checkStock)) {
            return $checkStock;
        }

        $sale = $this->saveSaleData($sale);
        $this->updateStock($id);
        $this->storeSaleDetails($sale);
        Action::newEntry($sale, 'UPDATED');

        $notify[] = ['success', 'Sale data updated successfully'];
        return back()->withNotify($notify);
    }


    protected function updateStock($saleId)
    {
        foreach ($this->products as $product) {

            $product = (object) $product;

            $saleDetail = SaleDetails::where('sale_id', $saleId)->where('product_id', $product->product_id)->first();

            $quantity = $product->quantity - @$saleDetail->quantity ?? 0;


            $newStock = $this->productStocks->where('product_id', $product->product_id)->first();

            if ($this->oldStocks->count()) {
                $oldStock = $this->oldStocks->where('product_id', $product->product_id)->first();
                if ($oldStock) {
                    $quantity            = $product->quantity;
                    $oldStock->quantity += @$saleDetail->quantity ?? 0;
                    $oldStock->save();
                }
            }
            $newStock->quantity -= $quantity;
            $newStock->save();

            //total_sale product update
            $product              = Product::find($product->product_id);
            $product->total_sale += $quantity;
            $product->save();
        }
    }

    protected function saveSaleData($sale)
    {
        $request    = request();
        $discount   = $request->discount ?? 0;
        $receivable = $this->totalPrice - $discount;
        $dueAmount  = $receivable - $sale->received_amount ?? 0;

        $sale->customer_id       = $request->customer_id;
        $sale->warehouse_id      = $request->warehouse_id;
        $sale->sale_date         = Carbon::parse($request->sale_date);
        $sale->total_price       = $this->totalPrice;
        $sale->discount_amount   = $discount;
        $sale->receivable_amount = $receivable;
        $sale->due_amount        = $dueAmount;
        $sale->note              = $request->note;
        $sale->save();

        return $sale;
    }

    protected function storeSaleDetails($sale)
    {
        $saleDetails = @$this->saleDetails ?? null;

        foreach ($this->products as $product) {
            $product    = (object) $product;
            $saleDetail = new SaleDetails();

            if ($saleDetails) {
                $saleDetail = $saleDetails->where('product_id', $product->product_id)->first() ?? new SaleDetails();
            }

            $saleDetail->sale_id    = $sale->id;
            $saleDetail->product_id = $product->product_id;
            $saleDetail->quantity   = $product->quantity;
            $saleDetail->price      = $product->price;
            $saleDetail->total      = $product->quantity * $product->price;

            // Handle return information
            $saleDetail->is_return = isset($product->is_return) ? (bool)$product->is_return : false;
            $saleDetail->return_invoice = $product->return_invoice ?? null;
            $saleDetail->return_note = $product->return_note ?? null;

            $saleDetail->save();
        }
    }

    protected function checkStockAvailability()
    {
        $products = $this->products;
        $notify   = [];

        foreach ($products as $product) {
            $product = (object) $product;

            $saleDetail = $this->saleDetails->where('product_id', $product->product_id)->first();

            if ($saleDetail) {
                $productStock = $this->productStocks->where('product_id', $product->product_id)->first();

                if ($this->oldStocks->count()) {
                    $oldStock = $this->oldStocks->where('product_id', $product->product_id)->first();

                    if ($oldStock) {
                        $newStock = ($oldStock->quantity + $saleDetail->quantity) - $product->quantity;
                        if ($newStock < 0) {
                            $notify[] = ['error', 'You can\'t increase the quantity because this product may already be sold'];
                            return back()->withNotify($notify)->withInput();
                        }
                        if ($productStock && $productStock->quantity < $product->quantity) {
                            $notify[] = ['error', 'You can\'t change warehouse quantity because this product may already be sold'];
                            return back()->withNotify($notify)->withInput();
                        }
                    }
                }

                if ($productStock) {
                    $newStock = $productStock->quantity + @$saleDetail->quantity;
                    if ($newStock < $product->quantity) {
                        $notify[] = ['error', 'You can\'t increase the quantity because this product may already be sold'];
                        return back()->withNotify($notify)->withInput();
                    }
                } else {
                    $notify[] = ['error', 'Insufficient warehouse product may already be sold'];
                    return back()->withNotify($notify)->withInput();
                }
            }
        }

        return $notify;
    }

    protected function getTotalPrice()
    {
        return $this->products->sum(function ($item) {
            return $item['quantity'] * $item['price'];
        }, 1);
    }

    protected function validation($request)
    {
        $request->validate([
            'customer_id'           => 'required|exists:customers,id',
            'warehouse_id'          => 'required|exists:warehouses,id',
            'sale_date'             => 'required|date_format:Y-m-d',
            'products'              => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|gt:0',
            'products.*.quantity'   => 'required|numeric', // Allow negative for returns
            'products.*.price'      => 'required|numeric|gte:0',
            'products.*.is_return'  => 'nullable|boolean',
            'products.*.return_invoice' => 'nullable|string',
            'discount'              => 'nullable|numeric|gte:0',
            'note'                  => 'nullable|string',
        ]);
    }

    public function searchProduct(Request $request)
    {
        try {
            $warehouse = $request->warehouse;
            $search = $request->search;
            $allProducts = $request->boolean('all_products', false);

            if (!$warehouse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Warehouse is required',
                    'data' => []
                ]);
            }

            $query = Product::query();

            // If requesting all products or search is empty, get all products
            if ($allProducts || empty($search)) {
                // Get all products with stock information for the warehouse
                $products = $query->with(['productStock' => function ($q) use ($warehouse) {
                    $q->where('warehouse_id', $warehouse);
                }, 'unit'])->get();
            } else {
                // Search specific products with stock
                $products = $query
                    ->whereHas('productStock', function ($q) use ($warehouse) {
                        $q->where('warehouse_id', $warehouse)->where('quantity', '>', 0);
                    })
                    ->where(function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('sku', 'like', '%' . $search . '%');
                    })
                    ->with(['productStock', 'unit'])
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching products: ' . $e->getMessage(),
                'error_details' => $e->getTraceAsString(),
                'data' => []
            ], 500);
        }
    }

    public function lastInvoice()
    {
        $lastInvoiceNo = sale::latest()->first()->invoice_no;

        return response()->json([
            'status' => true,
            'data'   => $lastInvoiceNo,

        ]);
    }

    /**
     * Get customer's available returns and dues for cross-sale application
     */
    public function getCustomerCrossSaleData(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id'
        ]);

        $crossSaleService = new CrossSaleService();
        $data = $crossSaleService->getCustomerAvailableAmounts($request->customer_id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Apply cross-sale amounts to a sale
     */
    public function applyCrossSaleAmounts(Request $request, $saleId)
    {
        $request->validate([
            'applied_returns' => 'nullable|array',
            'applied_returns.*.return_id' => 'required|exists:sale_returns,id',
            'applied_returns.*.amount' => 'required|numeric|min:0',
            'applied_dues' => 'nullable|array',
            'applied_dues.*.sale_id' => 'required|exists:sales,id',
            'applied_dues.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $crossSaleService = new CrossSaleService();
            $sale = $crossSaleService->applyCrossSaleAmounts(
                $saleId,
                $request->applied_returns ?? [],
                $request->applied_dues ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Cross-sale amounts applied successfully',
                'sale' => $sale
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error applying cross-sale amounts: ' . $e->getMessage()
            ], 500);
        }
    }
}
