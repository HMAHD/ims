@extends('admin.layouts.app')
@section('panel')
<div class="row gy-3">
    <div class="col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <form
                    action="@isset($sale) {{ route('admin.sale.update', @$sale->id) }} @else {{ route('admin.sale.store') }} @endisset"
                    method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label>@lang('Invoice No.')</label>
                                <div class="input-group">
                                    <input class="form-control" name="invoice_no" type="text"
                                        value="@if (@$sale) {{ @$sale->invoice_no }} @else {{ $invoiceNumber }} @endif" required
                                        readonly>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group" id="customer-wrapper">
                                <label> @lang('Customer')</label>
                                <select class="form-control select2" id="customer" name="customer_id" required>
                                    <option value="" selected disabled>@lang('Select One')</option>
                                    @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected($customer->id == @$sale->customer_id)>
                                        {{ __($customer->name) . ' +' . $customer->mobile }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label>@lang('Date')</label>
                                <input class="form-control timepicker" name="sale_date" type="text"
                                    value="{{ old('sale_date', @$sale->sale_date) }}" autocomplete="off">
                            </div>
                        </div>

                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Warehouse')
                                    @if (!@$sale)
                                    <i class="fa fa-info-circle text--primary" title="@lang('Sales products are obtained based on warehouse hierarchy. If confirmed that the product is available in your warehouse, the products will be found!')">
                                    </i>
                                    @endif
                                </label>
                                @if (@$sale)
                                <select class="form-control select2" name="warehouse_id" required data-minimum-results-for-search="-1">
                                    <option value="{{ @$sale->warehouse_id }}" selected>
                                        {{ __($sale->warehouse->name) }}
                                    </option>
                                </select>
                                @else
                                <select class="form-control select2" name="warehouse_id" required data-minimum-results-for-search="-1">
                                    <option value="" selected disabled>@lang('Select One')</option>
                                    @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected($warehouse->id == @$sale->warehouse_id)>
                                        {{ __($warehouse->name) }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif

                            </div>
                        </div>

                        <!-- Customer Cross-Sale Information -->
                        <div class="col-sm-12" id="customer-cross-sale-section" style="display: none;">
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">@lang('Customer Previous Returns & Due Amounts')</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success">@lang('Available Returns')</h6>
                                            <div id="available-returns-list">
                                                <!-- Returns will be loaded here -->
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-warning">@lang('Outstanding Dues')</h6>
                                            <div id="available-dues-list">
                                                <!-- Dues will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('Apply Return Amount')</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                    <input type="number" class="form-control" id="apply-return-amount" step="0.01" min="0" value="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>@lang('Apply Due Amount')</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                    <input type="number" class="form-control" id="apply-due-amount" step="0.01" min="0" value="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group products-container position-relative">
                                <label> @lang('Product')<span class="text--danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="las la-search"></i></span>
                                    <input class="form-control keyword" name="search" type="search" placeholder="@lang('Product Name or SKU')">
                                </div>
                                <ul class="products">
                                </ul>
                                <span class="text--danger error-message"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="table-responsive">
                            <table class="productTable table border">
                                <thead class="border bg--dark">
                                    <tr>
                                        <th>@lang('Name')</th>
                                        <th>@lang('In Stock')</th>
                                        <th>@lang('Quantity')<span class="text--danger">*</span></th>
                                        <th>@lang('Price')<span class="text--danger">*</span></th>
                                        <th>@lang('Total')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @isset($sale)
                                    @foreach ($sale->saleDetails as $item)
                                    <tr class="product-row product-row-{{ $item->product->id }}" data-product_id="{{ $item->product->id }}">

                                        <td class="fw-bold">
                                            <input class="form-control" type="text" value="{{ $item->product->name }}" readonly required>
                                            <input name="products[{{ $loop->index }}][product_id]" type="hidden"
                                                value="{{ $item->product->id }}" />
                                        </td>

                                        <td class="fw-bold">
                                            @php
                                            $stock = @$item->product->productStock
                                            ->where('warehouse_id', $sale->warehouse_id)
                                            ->first()->quantity;
                                            @endphp
                                            <input class="form-control stock_quantity" name="products[{{ $loop->index }}][stock_quantity]"
                                                data-id="{{ $item->product->id }}" type="hidden" value="{{ $stock }}" readonly required>

                                            <div class="input-group">
                                                <input class="form-control stock_quantity" name="products[{{ $loop->index }}][stock_quantity]"
                                                    data-id="{{ $item->product->id }}" type="number" value="{{ $stock }}" readonly
                                                    required>
                                                <span class="input-group-text">{{ $item->product->unit->name }}</span>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="input-group">
                                                <input class="form-control quantity" name="products[{{ $loop->index }}][quantity]"
                                                    data-id="{{ $item->product->id }}" data-qty="{{ $item->quantity }}" type="number" step="0.001" min="0.001"
                                                    value="{{ $item->quantity }}" required>
                                                <span class="input-group-text">{{ $item->product->unit->name }}</span>
                                            </div>
                                            <span class="error-message text--danger"></span>
                                        </td>

                                        <td>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control sales_price" name="products[{{ $loop->index }}][price]"
                                                    data-id="{{ $item->product->id }}" type="text" value="{{ $item->price }}" step="any"
                                                    required>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                <input class="form-control total" type="number" value="{{ $item->price * $item->quantity }}"
                                                    readonly>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline--danger disabled h-45" type="button">
                                                <i class="la la-trash"></i> @lang('Remove')
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @endisset
                                </tbody>
                            </table>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 col-sm-6">
                            <div class="form-group">
                                <label>@lang('Note')</label>
                                <textarea class="form-control" name="note">{{ old('note', @$sale->note) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label> @lang('Total Price')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control total_price" type="number" value="{{ @$sale->total_price }}" required
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label> @lang('Discount')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control" name="discount" type="number"
                                                value="{{ old('discount', getAmount(@$sale->discount_amount)) }}" step="any">
                                        </div>
                                        <span class="error-message text--danger"></span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>@lang('Receivable Amount')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control receivable_amount" type="number"
                                                value="{{ getAmount(@$sale->receivable_amount) }}" disabled>
                                        </div>
                                    </div>
                                </div>
                                @isset($sale)
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>@lang('Received Amount')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control" name="received_amount" type="number"
                                                value="{{ getAmount(@$sale->received_amount) }}" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>@lang('Due Amount')</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                            <input class="form-control due_amount" type="number" value="{{ getAmount(@$sale->due_amount) }}"
                                                disabled>
                                        </div>
                                    </div>
                                </div>
                                @endisset
                            </div>
                        </div>
                    </div>

                    @if (isset($sale) && $sale->return_status == 1)
                    <div class="alert alert-danger p-3 d-flex flex-column" role="alert">
                        <h4 class="text--danger text-center"> <i class="fa fa-exclamation-circle" aria-hidden="true"></i> @lang('Some products has returned from this sale')
                        </h4>

                        <p class="text--danger text-center">
                            @lang('You can\'t edit a sale after return any quantity from it.')
                            <a class="text--primary text-decoration-underline"
                                href="{{ route('admin.sale.return.edit', $sale->saleReturn->id) }}">@lang('View Return Details')</a>
                        </p>
                    </div>
                    @endif

                    <button class="btn btn--primary w-100 h-45 submit-btn" type="submit"
                        @if (isset($sale) && $sale->return_status == 1) disabled @endif>@lang('Submit')</button>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- SW Notification Modal -->
<div class="modal fade" id="warningModal" role="dialog" aria-labelledby="cookieModalLabel" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cookieModalLabel">@lang('Warning!')</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">

                <i class="la la-4x la-info-circle text--warning" aria-hidden="true"></i>

                <h6>@lang('Please select a warehouse before selecting product.')</h6>
            </div>

        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<x-back route="{{ route('admin.sale.index') }}" />
@endpush

@push('style')
<style>
    .empty-notification img {
        width: 30px;
        padding-top: 12px;
    }
</style>
@endpush

@push('script-lib')
<script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('style-lib')
<link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('script')
<script>
    (function($) {
        'use strict';
        $('.timepicker').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            timePicker: false,
            timePicker24Hour: false,
            autoUpdateInput: true,
            timePickerSeconds: false,
            maxDate: new Date(),
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
        $('.timepicker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD'));
        });

        $('.timepicker').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        let productArray = [];

        @if(@$sale)
        productArray = @json($sale->saleDetails->pluck('product_id')->toArray());
        @endif

        calculateGrandTotal();

        // Cross-sale functionality
        let customerCrossSaleData = {};
        let appliedReturnAmount = 0;
        let appliedDueAmount = 0;

        // Load customer cross-sale data when customer is selected
        $('#customer').on('change', function() {
            const customerId = $(this).val();
            if (customerId) {
                loadCustomerCrossSaleData(customerId);
            } else {
                $('#customer-cross-sale-section').hide();
            }
        });

        function loadCustomerCrossSaleData(customerId) {
            $.ajax({
                url: "{{ route('admin.sale.customer.cross.sale.data') }}",
                method: 'GET',
                data: {
                    customer_id: customerId
                },
                success: function(response) {
                    if (response.success) {
                        customerCrossSaleData = response.data;
                        displayCrossSaleData(response.data);
                        if (response.data.returns.length > 0 || response.data.dues.length > 0) {
                            $('#customer-cross-sale-section').show();
                        } else {
                            $('#customer-cross-sale-section').hide();
                        }
                    }
                },
                error: function() {
                    $('#customer-cross-sale-section').hide();
                }
            });
        }

        function displayCrossSaleData(data) {
            // Display available returns
            let returnsHtml = '';
            if (data.returns.length > 0) {
                returnsHtml += '<div class="table-responsive"><table class="table table-sm">';
                returnsHtml += '<thead><tr><th>Invoice</th><th>Date</th><th>Amount</th><th>Action</th></tr></thead><tbody>';
                data.returns.forEach(function(returnItem) {
                    returnsHtml += `<tr>
                        <td>${returnItem.sale_invoice}</td>
                        <td>${returnItem.return_date}</td>
                        <td>{{ gs('cur_sym') }}${returnItem.remaining_amount}</td>
                        <td><button type="button" class="btn btn-sm btn-success apply-return-btn" data-return-id="${returnItem.id}" data-amount="${returnItem.remaining_amount}">Apply</button></td>
                    </tr>`;
                });
                returnsHtml += '</tbody></table></div>';
                returnsHtml += `<p class="text-success"><strong>Total Available: {{ gs('cur_sym') }}${data.total_return_amount}</strong></p>`;
            } else {
                returnsHtml = '<p class="text-muted">No available returns</p>';
            }
            $('#available-returns-list').html(returnsHtml);

            // Display available dues
            let duesHtml = '';
            if (data.dues.length > 0) {
                duesHtml += '<div class="table-responsive"><table class="table table-sm">';
                duesHtml += '<thead><tr><th>Invoice</th><th>Date</th><th>Amount</th><th>Action</th></tr></thead><tbody>';
                data.dues.forEach(function(due) {
                    duesHtml += `<tr>
                        <td>${due.invoice_no}</td>
                        <td>${due.sale_date}</td>
                        <td>{{ gs('cur_sym') }}${due.remaining_amount}</td>
                        <td><button type="button" class="btn btn-sm btn-warning apply-due-btn" data-sale-id="${due.id}" data-amount="${due.remaining_amount}">Apply</button></td>
                    </tr>`;
                });
                duesHtml += '</tbody></table></div>';
                duesHtml += `<p class="text-warning"><strong>Total Outstanding: {{ gs('cur_sym') }}${data.total_due_amount}</strong></p>`;
            } else {
                duesHtml = '<p class="text-muted">No outstanding dues</p>';
            }
            $('#available-dues-list').html(duesHtml);
        }

        // Handle apply return button clicks
        $(document).on('click', '.apply-return-btn', function() {
            const amount = parseFloat($(this).data('amount'));
            const currentAmount = parseFloat($('#apply-return-amount').val()) || 0;
            $('#apply-return-amount').val((currentAmount + amount).toFixed(2));
            appliedReturnAmount = currentAmount + amount;
            calculateGrandTotal();
        });

        // Handle apply due button clicks
        $(document).on('click', '.apply-due-btn', function() {
            const amount = parseFloat($(this).data('amount'));
            const currentAmount = parseFloat($('#apply-due-amount').val()) || 0;
            $('#apply-due-amount').val((currentAmount + amount).toFixed(2));
            appliedDueAmount = currentAmount + amount;
            calculateGrandTotal();
        });

        // Handle manual input changes
        $('#apply-return-amount').on('input', function() {
            appliedReturnAmount = parseFloat($(this).val()) || 0;
            calculateGrandTotal();
        });

        $('#apply-due-amount').on('input', function() {
            appliedDueAmount = parseFloat($(this).val()) || 0;
            calculateGrandTotal();
        });

        $("[name='search']").on('input', function() {
            $('.products-container .error-message').empty();
            let data = {};
            data.search = $(this).val();
            data.warehouse = $("[name=warehouse_id]").find(':selected').val();
            var warehouseId = data.warehouse;

            if (data.warehouse && data.search) {
                $.ajax({
                    url: "{{ route('admin.sale.search.product') }}",
                    method: 'GET',
                    data: data,
                    success: function(response) {
                        var products = '';
                        $(".products").html('');

                        if (response.data.length) {
                            $.each(response.data, function(key, product) {
                                // Find stock for the selected warehouse
                                var stock = product.product_stock ? product.product_stock.find((e) => e.warehouse_id == warehouseId) : null;
                                var stockQuantity = stock ? stock.quantity : 0;
                                var unitName = product.unit ? product.unit.name : '';

                                products +=
                                    `<li class="products__item productItem pt-2" data-stock="${stockQuantity}" data-id="${product.id}" data-name="${product.name}" data-unit="${unitName}">
                                            <h6>${product.name}</h6>
                                            <small>SKU: ${product.sku}</small>
                                        </li>`;
                            });
                        } else {
                            $('.products-container .error-message').html(`
                                <div class="empty-notification text-center">
                                    <img src="{{ getImage('assets/images/empty_list.png') }}" alt="empty">
                                    <p class="mt-3">@lang('No product found')</p>
                                </div>
                            `);
                        }

                        $(".products").html(products);
                    },
                    error: function(xhr, status, error) {
                        console.error('Product search error:', error);
                        console.error('Response:', xhr.responseText);
                        $('.products-container .error-message').html(`
                            <div class="alert alert-danger">
                                <p>Error searching products. Please try again.</p>
                            </div>
                        `);
                    }
                });
            } else if (!data.warehouse) {
                $('#warningModal').modal('show');
                $(this).val('');
            } else {
                $(".products").empty();
                $('.products-container .error-message').empty();
            }
        });

        $('body').on('click', '.productItem', function() {
            let index = $('.product-row ').length + 1;

            $(".no-data").addClass('d-none');
            var data = $(this).data();


            let productId = data.id;

            if (!productArray.includes(productId)) {
                productArray.push(productId);

                $(".productTable tbody").append(`
                        <tr data-product_id="${data.id}" class="product-row product-row-${data.id}">
                            <td data-label="@lang('Name')" class="fw-bold">
                                <input type="text" class="form-control" value="${data.name}" readonly required>
                                <input type="hidden" class="product_id" name="products[${index}][product_id]" value="${data.id}"/>
                            </td>

                            <td data-label="@lang('In Stock')">
                                <div class="input-group">
                                    <input type="number" name="products[${index}][stock_quantity]" value="${data.stock}"  class="form-control stock_quantity" data-id="${data.id}" readonly required>
                                    <span class="input-group-text">${data.unit}</span>
                                </div>
                            </td>

                            <td data-label="@lang('Quantity')">
                                <div class="input-group">
                                    <input type="number" step="0.001" min="0.001" name="products[${index}][quantity]" value="1"  class="form-control quantity" data-id="${data.id}" required>
                                    <span class="input-group-text">${data.unit}</span>
                                </div>
                                <span class="error-message text--danger"></span>
                            </td>
                            <td data-label="@lang('Price')">
                                <div class="input-group">
                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                    <input type="number" name="products[${index}][price]" class="form-control sales_price" data-id="${data.id}" value="0" required>
                                </div>
                            </td>
                            <td data-label="@lang('Total')">
                                <div class="input-group">
                                    <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                    <input type="number" value="0" class="form-control total" readonly>
                                </div>
                            </td>
                            <td data-label="@lang('Action')">
                                <button type="button" class="btn btn-outline--danger removeBtn h-45" >
                                    <i class="la la-trash"></i> @lang('Remove')
                                </button>
                            </td>
                        </tr>
                    `);

            } else {
                let quantityField = $(`[data-product_id=${productId}]`).find('.quantity');
                quantityField.val(Number(quantityField.val()) + 1);

                calculateProductData(productId)
            }

            $(".products").empty();
            $("[name='search']").val("");


        });

        // Remove the product row from table
        $(".productTable").on('click', '.removeBtn', function() {
            let productId = Number($(this).parents('tr').find('.product_id').val());
            let indexToRemove = productArray.indexOf(productId);
            productArray.splice(indexToRemove, 1)
            $(this).parents('tr').remove();
            calculateGrandTotal();
        });

        let error = false;


        $(".productTable").on('input', '.quantity', function() {

            var id = $(this).data('id');
            var initialQty = Number($(this).parents('tr').find('.quantity').data('qty'));
            var stockQty = Number($(this).parents('tr').find('.stock_quantity').val());
            var qty = Number($(this).val());

            var accStock = stockQty + (initialQty ? initialQty : 0);

            if (accStock < qty) {
                error = true;
                $(this).parent().siblings('.error-message').text('Ensure quantity does not exceed available stock levels.');
            } else if ($(this).val() == '') {
                $(this).parent().siblings('.error-message').text('This field cannot be left empty.');
                error = true;
            } else {
                calculateProductData(id);
                error = false;
                $(this).parent().siblings('.error-message').empty();
            }
            manageSubmitButton();
        });


        $(".productTable").on('input', '.sales_price', function() {
            calculateProductData($(this).data('id'));
        });

        $("[name=discount]").on('input', function() {
            let totalPrice = $('.total_price').val() * 1;
            if (this.value < 0) {
                this.value = '';
                $(this).parent().siblings('.error-message').text(`Discount amount must not be less than 0`);
                error = true;
            } else if (this.value > totalPrice) {
                $(this).parent().siblings('.error-message').text(
                    `Discount amount must not be greater than total price`);
                error = true;
            } else {
                $(this).parent().siblings('.error-message').empty();
                error = false;
            }
            manageSubmitButton();
            calculateGrandTotal();
        });


        function manageSubmitButton() {
            if (error) {
                $('.submit-btn').attr('disabled', 'disabled');
            } else {
                $('.submit-btn').removeAttr('disabled');
            }
        }

        function calculateProductData(id) {
            var qty = parseFloat($(".productTable .product-row-" + id + " .quantity").val() * 1);
            var salePrice = parseFloat($(".productTable .product-row-" + id + " .sales_price").val() * 1);
            var total = qty * salePrice;
            $(".productTable .product-row-" + id + "  .total").val(total.toFixed(2))
            calculateGrandTotal();
        }

        $('[name=received_amount]').on('input', function() {
            calculateGrandTotal();
            let payableAmount = Number($('.receivable_amount').val());
            let payingAmount = Number($(this).val());


            if (payableAmount < payingAmount) {
                $(this).val(payableAmount);
                $(".due_amount").val(0);
            }
        });



        function calculateGrandTotal() {
            var total = 0;
            $(".productTable .total").each(function(index, element) {
                total = total + parseFloat($(element).val());
            });

            var discount = parseFloat($("[name=discount]").val() * 1);
            $(".total_price").val(total.toFixed(2));

            // Calculate with cross-sale adjustments
            var payableAmount = total - discount + appliedDueAmount - appliedReturnAmount;

            $(".receivable_amount").val(payableAmount.toFixed(2));
            let payingAmount = $('[name=received_amount]').val();
            $(".due_amount").val((payableAmount - payingAmount).toFixed(2));

            // Update display for cross-sale amounts
            updateCrossSaleDisplay();
        }

        function updateCrossSaleDisplay() {
            // Add visual indicators for applied amounts
            if (appliedReturnAmount > 0 || appliedDueAmount > 0) {
                let crossSaleInfo = '';
                if (appliedReturnAmount > 0) {
                    crossSaleInfo += `<div class="text-success">Applied Return: -{{ gs('cur_sym') }}${appliedReturnAmount.toFixed(2)}</div>`;
                }
                if (appliedDueAmount > 0) {
                    crossSaleInfo += `<div class="text-warning">Applied Due: +{{ gs('cur_sym') }}${appliedDueAmount.toFixed(2)}</div>`;
                }

                // Add or update cross-sale display in the summary section
                if ($('#cross-sale-summary').length === 0) {
                    $('.receivable_amount').parent().parent().after(`
                        <div class="col-sm-12" id="cross-sale-summary">
                            <div class="card border-info">
                                <div class="card-body p-2">
                                    <h6 class="text-info mb-1">Cross-Sale Adjustments:</h6>
                                    <div id="cross-sale-details">${crossSaleInfo}</div>
                                </div>
                            </div>
                        </div>
                    `);
                } else {
                    $('#cross-sale-details').html(crossSaleInfo);
                }
            } else {
                $('#cross-sale-summary').remove();
            }
        }


        $('[name=warehouse_id]').on('change', function() {
            if (productArray) {
                productArray = [];
                $("tbody").empty();
            }
        })

        function extractAmount(value) {
            let stringValue = String(value);
            let cleanedValue = stringValue.replace(/[\$USD\s]/g, '');
            return parseFloat(cleanedValue);
        }


    })(jQuery);
</script>
@endpush
