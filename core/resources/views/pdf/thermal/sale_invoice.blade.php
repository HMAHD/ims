@extends('pdf.thermal.master')

@section('main-content')
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

@endsection