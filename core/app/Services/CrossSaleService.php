<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnApplication;
use App\Models\SaleDueApplication;
use Illuminate\Support\Facades\DB;

class CrossSaleService
{
    /**
     * Get customer's available returns and dues for a new sale
     */
    public function getCustomerAvailableAmounts($customerId)
    {
        $customer = Customer::findOrFail($customerId);

        // Get available returns with detailed product information
        $availableReturns = $customer->saleReturns()
            ->where('due_amount', '>', 0)
            ->with(['sale', 'details.product.unit'])
            ->get()
            ->map(function ($return) {
                // Calculate how much has already been applied
                $appliedAmount = SaleReturnApplication::where('original_sale_return_id', $return->id)
                    ->sum('applied_amount');
                $remainingAmount = max(0, $return->due_amount - $appliedAmount);

                // Format return details for easy use in new sale
                $returnDetails = $return->details->map(function ($detail) {
                    return [
                        'product_id' => $detail->product_id,
                        'product_name' => $detail->product->name,
                        'product_sku' => $detail->product->sku,
                        'unit_name' => $detail->product->unit->name ?? '',
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'total' => $detail->quantity * $detail->price
                    ];
                });

                return [
                    'id' => $return->id,
                    'sale_invoice' => $return->sale->invoice_no ?? 'N/A',
                    'return_date' => $return->return_date,
                    'total_amount' => $return->due_amount,
                    'applied_amount' => $appliedAmount,
                    'remaining_amount' => $remainingAmount,
                    'details' => $returnDetails,
                    'can_add_to_sale' => true // Flag to indicate these can be added as line items
                ];
            })
            ->filter(function ($return) {
                return $return['remaining_amount'] > 0;
            })
            ->values();

        // Get available dues
        $availableDues = $customer->sale()
            ->where('due_amount', '>', 0)
            ->get()
            ->map(function ($sale) {
                // Calculate how much has already been applied
                $appliedAmount = SaleDueApplication::where('original_sale_id', $sale->id)
                    ->sum('applied_amount');
                $remainingAmount = max(0, $sale->due_amount - $appliedAmount);

                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'sale_date' => $sale->sale_date,
                    'total_amount' => $sale->due_amount,
                    'applied_amount' => $appliedAmount,
                    'remaining_amount' => $remainingAmount
                ];
            })
            ->filter(function ($sale) {
                return $sale['remaining_amount'] > 0;
            })
            ->values();

        return [
            'returns' => $availableReturns,
            'dues' => $availableDues,
            'total_return_amount' => $availableReturns->sum('remaining_amount'),
            'total_due_amount' => $availableDues->sum('remaining_amount')
        ];
    }

    /**
     * Apply returns and dues to a new sale
     */
    public function applyCrossSaleAmounts($saleId, $appliedReturns = [], $appliedDues = [])
    {
        $sale = Sale::findOrFail($saleId);

        DB::transaction(function () use ($sale, $appliedReturns, $appliedDues) {
            $totalReturnAmount = 0;
            $totalDueAmount = 0;

            // Apply returns
            foreach ($appliedReturns as $returnData) {
                $saleReturn = SaleReturn::findOrFail($returnData['return_id']);

                // Calculate remaining amount manually
                $alreadyApplied = SaleReturnApplication::where('original_sale_return_id', $saleReturn->id)
                    ->sum('applied_amount');
                $remainingAmount = max(0, $saleReturn->due_amount - $alreadyApplied);

                $appliedAmount = min($returnData['amount'], $remainingAmount);

                if ($appliedAmount > 0) {
                    SaleReturnApplication::create([
                        'new_sale_id' => $sale->id,
                        'original_sale_return_id' => $saleReturn->id,
                        'customer_id' => $sale->customer_id,
                        'applied_amount' => $appliedAmount,
                        'note' => $returnData['note'] ?? null
                    ]);

                    $totalReturnAmount += $appliedAmount;
                }
            }

            // Apply dues
            foreach ($appliedDues as $dueData) {
                $originalSale = Sale::findOrFail($dueData['sale_id']);

                // Calculate remaining amount manually
                $alreadyApplied = SaleDueApplication::where('original_sale_id', $originalSale->id)
                    ->sum('applied_amount');
                $remainingAmount = max(0, $originalSale->due_amount - $alreadyApplied);

                $appliedAmount = min($dueData['amount'], $remainingAmount);

                if ($appliedAmount > 0) {
                    SaleDueApplication::create([
                        'new_sale_id' => $sale->id,
                        'original_sale_id' => $originalSale->id,
                        'customer_id' => $sale->customer_id,
                        'applied_amount' => $appliedAmount,
                        'note' => $dueData['note'] ?? null
                    ]);

                    $totalDueAmount += $appliedAmount;
                }
            }

            // Update sale with applied amounts
            $sale->applied_return_amount = $totalReturnAmount;
            $sale->applied_due_amount = $totalDueAmount;
            $sale->applied_returns = $appliedReturns;
            $sale->applied_dues = $appliedDues;

            // Recalculate due amount
            $newDueAmount = $sale->receivable_amount + $totalDueAmount - $totalReturnAmount - $sale->received_amount;
            $sale->due_amount = $newDueAmount;

            $sale->save();
        });

        return $sale->fresh();
    }

    /**
     * Remove applied cross-sale amounts from a sale
     */
    public function removeCrossSaleAmounts($saleId)
    {
        $sale = Sale::findOrFail($saleId);

        DB::transaction(function () use ($sale) {
            // Remove return applications
            $sale->returnApplications()->delete();

            // Remove due applications
            $sale->dueApplications()->delete();

            // Reset sale amounts
            $sale->applied_return_amount = 0;
            $sale->applied_due_amount = 0;
            $sale->applied_returns = null;
            $sale->applied_dues = null;

            // Recalculate due amount
            $sale->due_amount = $sale->receivable_amount - $sale->received_amount;
            $sale->save();
        });

        return $sale->fresh();
    }
}
