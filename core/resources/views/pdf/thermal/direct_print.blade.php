<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ gs()->siteName($pageTitle ?? '') }}</title>

    <link rel="stylesheet" href="{{ asset('assets/admin/css/thermal.css') }}">

    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0 !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 2mm;
                width: 72mm;
                height: auto !important;
                overflow: visible !important;
            }

            /* Prevent ALL page breaks for continuous receipt */
            * {
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                page-break-after: avoid !important;
                break-inside: avoid !important;
                break-before: avoid !important;
                break-after: avoid !important;
            }

            html,
            body,
            .header,
            .content,
            .invoice-info,
            .customer-info,
            .table,
            .summary,
            .footer {
                page-break-inside: avoid !important;
                page-break-before: avoid !important;
                page-break-after: avoid !important;
                break-inside: avoid !important;
                break-before: avoid !important;
                break-after: avoid !important;
            }

            tr,
            td,
            th {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .btn {
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="btn btn-primary" onclick="printInvoice()">🖨️ Print Now</button>
        <button class="btn btn-secondary" onclick="window.close()">❌ Close</button>
    </div>

    <!-- Invoice Content -->
    <div class="header">
        @if(gs('site_address'))
        <div class="text-sm">{{ __(gs('site_address')) }}</div>
        @endif
        @if(gs('site_phone'))
        <div class="text-sm">Tel: {{ __(gs('site_phone')) }}</div>
        @endif
        @if(gs('site_email'))
        <div class="text-sm">Email: {{ __(gs('site_email')) }}</div>
        @endif
    </div>

    <div class="content">
        <!-- Invoice Header -->
        <div class="invoice-info center">
            <div class="h2">{{ __(gs('site_name')) }}</div>
            <div class="strong">@lang('Invoice No.'): #{{ $sale->invoice_no }}</div>
            <div>@lang('Date'): {{ showDateTime($sale->sale_date, 'd/m/Y H:i') }}</div>
            @if($sale->warehouse)
            <div>@lang('Warehouse'): {{ $sale->warehouse->name }}</div>
            @endif
        </div>

        <!-- Customer Information -->
        <div class="customer-info">
            <div class="strong">@lang('Bill To'):</div>
            <div>@lang('Name'): {{ $customer->name }}</div>
            @if($customer->mobile)
            <div>@lang('Mobile'): {{ $customer->mobile }}</div>
            @endif
            @if($customer->email)
            <div>@lang('Email'): {{ $customer->email }}</div>
            @endif
            @if($customer->address)
            <div>@lang('Address'): {{ $customer->address }}</div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="table">
            <thead>
                <tr>
                    <th class="item">@lang('Item')</th>
                    <th class="qty">@lang('Qty')</th>
                    <th class="price">@lang('Price')</th>
                    <th class="total">@lang('Total')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sale->saleDetails as $item)
                <tr>
                    <td class="item">
                        <div class="strong">{{ $item->product->name }}</div>
                        @if($item->product->sku)
                        <div class="text-xs">{{ $item->product->sku }}</div>
                        @endif
                    </td>
                    <td class="qty">{{ $item->quantity }}{{ $item->product->unit ? ' ' . $item->product->unit->name : '' }}</td>
                    <td class="price">{{ showAmount($item->price) }}</td>
                    <td class="total">{{ showAmount($item->total) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="center">@lang('No items found')</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <span>@lang('Subtotal'):</span>
                <span>{{ showAmount($sale->total_price) }}</span>
            </div>

            @if($sale->discount_amount > 0)
            <div class="summary-row">
                <span>@lang('Discount'):</span>
                <span>-{{ showAmount($sale->discount_amount) }}</span>
            </div>
            @endif

            <div class="summary-row total">
                <span>@lang('Grand Total'):</span>
                <span>{{ showAmount($sale->receivable_amount) }}</span>
            </div>

            <div class="summary-row">
                <span>@lang('Received'):</span>
                <span>{{ showAmount($sale->received_amount) }}</span>
            </div>

            <div class="summary-row">
                <span>
                    @if ($sale->due_amount >= 0)
                    @lang('Due Amount'):
                    @else
                    @lang('Change'):
                    @endif
                </span>
                <span class="strong">{{ showAmount(abs($sale->due_amount)) }}</span>
            </div>

            @if($sale->saleReturn)
            <div class="summary-row total-after-return">
                <span>@lang('Total After Return'):</span>
                <span class="strong">{{ showAmount($sale->receivable_amount - $sale->saleReturn->paid_amount) }}</span>
            </div>
            @endif
        </div>

        <!-- Sale Return Information -->
        @if($sale->saleReturn)
        <div class="sale-return-info">
            <div class="section-title">@lang('RETURN INFORMATION')</div>

            <div class="return-summary">
                <div class="summary-row">
                    <span>@lang('Return Date'):</span>
                    <span>{{ showDateTime($sale->saleReturn->return_date, 'd/m/Y') }}</span>
                </div>

                <div class="summary-row">
                    <span>@lang('Return Amount'):</span>
                    <span>{{ showAmount($sale->saleReturn->total_price) }}</span>
                </div>

                @if($sale->saleReturn->discount_amount > 0)
                <div class="summary-row">
                    <span>@lang('Return Discount'):</span>
                    <span>-{{ showAmount($sale->saleReturn->discount_amount) }}</span>
                </div>
                @endif

                <div class="summary-row">
                    <span>@lang('Return Payable'):</span>
                    <span>{{ showAmount($sale->saleReturn->payable_amount) }}</span>
                </div>

                <div class="summary-row">
                    <span>@lang('Return Paid'):</span>
                    <span>{{ showAmount($sale->saleReturn->paid_amount) }}</span>
                </div>

                @if($sale->saleReturn->due_amount != 0)
                <div class="summary-row">
                    <span>
                        @if ($sale->saleReturn->due_amount >= 0)
                        @lang('Return Due'):
                        @else
                        @lang('Return Change'):
                        @endif
                    </span>
                    <span class="strong">{{ showAmount(abs($sale->saleReturn->due_amount)) }}</span>
                </div>
                @endif
            </div>

            <!-- Return Items -->
            @if($sale->saleReturn->details && $sale->saleReturn->details->count() > 0)
            <div class="return-items">
                <div class="section-subtitle">@lang('Returned Items'):</div>
                <table class="table return-table">
                    <thead>
                        <tr>
                            <th class="item">@lang('Item')</th>
                            <th class="qty">@lang('Qty')</th>
                            <th class="price">@lang('Price')</th>
                            <th class="total">@lang('Total')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->saleReturn->details as $returnItem)
                        <tr>
                            <td class="item">
                                <div class="strong">{{ $returnItem->product->name }}</div>
                                @if($returnItem->product->sku)
                                <div class="text-xs">{{ $returnItem->product->sku }}</div>
                                @endif
                            </td>
                            <td class="qty">{{ $returnItem->quantity }}{{ $returnItem->product->unit ? ' ' . $returnItem->product->unit->name : '' }}</td>
                            <td class="price">{{ showAmount($returnItem->price) }}</td>
                            <td class="total">{{ showAmount($returnItem->total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        <!-- Customer Signature Section -->
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-label">@lang('Customer')</div>
        </div>

    </div>

    <div class="footer">
        <div class="thank-you">@lang('Thank You!')</div>
        <div class="text-xs">@lang('Powered by AiSoftware.lk')</div>
    </div>

    <script>
        function printInvoice() {
            // Hide print controls
            document.querySelector('.print-controls').style.display = 'none';

            // Print the page
            window.print();

            // Show print controls again after printing
            setTimeout(function() {
                document.querySelector('.print-controls').style.display = 'block';
            }, 1000);
        }

        // Auto-print when page loads (optional)
        window.addEventListener('load', function() {
            // Uncomment the line below if you want auto-print
            // setTimeout(printInvoice, 500);
        });

        // Handle print dialog close
        window.addEventListener('afterprint', function() {
            document.querySelector('.print-controls').style.display = 'block';
        });
    </script>
</body>

</html>