@extends('admin.layouts.app')
@section('panel')
<div class="row gy-3">
    <div class="col-lg-12 col-md-12 mb-30">
        <div class="card">
            <div class="card-body">
                <form action="@isset($adjustment) {{ route('admin.adjustment.update', @$adjustment->id) }} @else {{ route('admin.adjustment.store') }} @endisset" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label">@lang('Warehouse')</label>
                                @if (@$adjustment)
                                <select class="form-control select2" name="warehouse_id" data-minimum-results-for-search="-1" required>
                                    <option value="{{ $adjustment->warehouse_id }}" selected readonly>
                                        {{ __($adjustment->warehouse->name) }}
                                    </option>
                                </select>
                                @else
                                <select class="form-control select2" name="warehouse_id" data-minimum-results-for-search="-1" required>
                                    <option value="" selected disabled>@lang('Select One')</option>
                                    @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ __($warehouse->name) }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>

                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>@lang('Date')</label>
                                <input class="form-control timepicker" name="adjust_date" type="text" value="{{ old('adjust_date', @$adjustment->adjust_date) }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group products-container position-relative">
                                <label class="form-label d-flex align-items-center justify-content-between">
                                    <span>@lang('Product')</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-product-list">
                                        <i class="fas fa-list"></i> @lang('Browse All')
                                    </button>
                                </label>

                                <!-- Search Input -->
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="las la-search"></i></span>
                                    <input class="form-control keyword" name="search" type="search" placeholder="@lang('Product Name or SKU')">
                                </div>

                                <!-- Scrollable Product List -->
                                <div id="product-scroll-list" class="product-scroll-container" style="display: none;">
                                    <div class="product-scroll-header">
                                        <h6 class="mb-0">@lang('Select Product')</h6>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="close-product-list">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="product-scroll-list-content">
                                        <!-- Products will be loaded here -->
                                    </div>
                                </div>

                                <ul class="products">
                                    <!-- Product data will append here after search -->
                                </ul>
                                <span class="text--danger error-message"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="table-responsive table-responsive--lg">
                            <table class="productTable table border">
                                <thead class="border bg--dark">
                                    <tr>
                                        <th>@lang('Name')</th>
                                        <th>@lang('Current Stock')</th>
                                        <th>@lang('Stock - After Adjust')</th>
                                        <th class="qty-field">@lang('Adjust Qty') <span class="text-danger">*</span></th>
                                        <th>@lang('Type') <span class="text-danger">*</span></th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @isset($adjustment)
                                    @foreach ($adjustment->adjustmentDetails as $item)
                                    <tr class="product-row" data-product_id="{{ $item->product->id }}">
                                        <td>
                                            {{ $item->product->name }}
                                            <input name="products[{{ $loop->index }}][product_id]" type="hidden" value="{{ $item->product->id }}" />
                                        </td>

                                        <td>
                                            @php
                                            $quantity = $item->product->productStock->where('warehouse_id', $adjustment->warehouse_id)->first()->quantity;
                                            @endphp
                                            <span class="stock-qty">{{ $quantity }}</span>
                                            {{ $item->product->unit->name }}
                                        </td>

                                        <td>
                                            <span class="after-adjust-qty">{{ $quantity }}</span>
                                            {{ $item->product->unit->name }}
                                        </td>

                                        <td>

                                            <input class="old-qty" type="hidden" value="{{ $item->quantity }}">
                                            <input class="old-type" type="hidden" value="{{ $item->adjust_type }}">

                                            <div class="input-group">
                                                <input class="bg--white form-control quantity" name="products[{{ $loop->index }}][quantity]" data-id="{{ $item->product->id }}" data-qty="{{ $item->quantity }}" type="number" step="0.001" min="0.001" value="{{ $item->quantity }}" required>

                                                <span class="input-group-text">{{ $item->product->unit->name }}</span>
                                            </div>
                                        </td>

                                        <td>
                                            <select class="form-control adjust-type" name="products[{{ $loop->index }}][adjust_type]" required>
                                                <option value="1" @selected(1==$item->adjust_type)>
                                                    @lang('Subtract')</option>
                                                <option value="2" @selected(2==$item->adjust_type)>
                                                    @lang('Add')</option>
                                            </select>
                                        </td>

                                        <td>
                                            <button class="btn btn-outline--danger h-45 disabled max-content" type="button">
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
                        <div class="col-md-12 col-sm-6">
                            <div class="form-group">
                                <label>@lang('Note')</label>
                                <textarea class="form-control" name="note" required>{{ old('note', @$adjustment->note) }}</textarea>
                            </div>
                        </div>
                    </div>

                    @permit('admin.adjustment.update')
                    <button class="btn btn--primary w-100 h-45 submit-btn" type="submit">@lang('Submit')</button>
                    @endpermit
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
<x-back route="{{ route('admin.adjustment.index') }}" />
@endpush

@push('script-lib')
<script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('style-lib')
<link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('style')
<style>
    .table td {
        white-space: unset;
        padding: 8px 15px;
    }

    .qty-field {
        min-width: 280px;
        width: 280px
    }

    .max-content {
        width: max-content
    }

    .empty-notification img {
        width: 30px;
        padding-top: 12px;
    }

    /* Product scroll list styles */
    .product-scroll-container {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        max-height: 400px;
        overflow: hidden;
    }

    .product-scroll-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #f8f9fa;
        border-bottom: 1px solid #ddd;
    }

    .product-scroll-list-content {
        max-height: 350px;
        overflow-y: auto;
        padding: 8px;
    }

    .product-scroll-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        margin-bottom: 8px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: white;
    }

    .product-scroll-item:hover {
        border-color: #007bff;
        background: #f8f9ff;
        transform: translateY(-1px);
    }

    .product-scroll-item.out-of-stock {
        opacity: 0.6;
        cursor: not-allowed;
        background: #f8f8f8;
    }

    .product-scroll-item.out-of-stock:hover {
        border-color: #dc3545;
        background: #fff5f5;
        transform: none;
    }

    .product-info {
        flex: 1;
    }

    .product-stock {
        margin-left: 12px;
    }

    @media (max-width: 768px) {
        .product-scroll-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 500px;
            max-height: 70vh;
        }
    }
</style>
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

        @if(@$adjustment)
        productArray = @json($adjustment->adjustmentDetails->pluck('product_id')->toArray());
        @endif

        $("[name='search']").on('input', function() {
            let data = {};
            data.search = $(this).val();
            data.warehouse = $("[name=warehouse_id]").find(':selected').val();
            var warehouseId = data.warehouse;

            if (data.warehouse && data.search) {
                $('.products-container .error-message').empty();

                $.ajax({
                    url: "{{ route('admin.adjustment.search.product') }}",
                    method: 'GET',
                    data: data,
                    success: function(response) {

                        var products = '';
                        $(".products").html('');
                        if (response.data.length) {

                            $.each(response.data, function(key, product) {
                                if (product) {
                                    if (product.product_stock) {
                                        var stock = product.product_stock.find((product) => product.warehouse_id == warehouseId);

                                        products +=
                                            `<li class="products__item productItem pt-2" data-id="${product.id}" data-name="${product.name}" data-unit="${product.unit.name}" data-stock="${ stock ? stock.quantity : 0}">
                                                    <h6>${product.name}</h6><small>SKU: ${product.sku}</small>
                                                </li>`;
                                    }
                                }
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
            var data = $(this).data();
            let productId = data.id;

            if (!productArray.includes(productId)) {
                productArray.push(productId);

                $(".productTable tbody").append(`
                        <tr data-product_id="${data.id}" class="product-row">
                            <td data-label="@lang('Name')">
                                ${data.name}
                                <input type="hidden" name="products[${index}][product_id]" value="${data.id}"/>
                            </td>

                            <td data-label="@lang('Current Stock')">
                                <span class="stock-qty">${data.stock}</span> ${data.unit}
                            </td>

                            <td data-label="@lang('Stock - After Adjust')">
                                <span class="after-adjust-qty"></span>
                                ${data.unit}
                                <br/>
                                <span class="text--danger error-message"></span>
                            </td>

                            <td data-label="@lang('Adjust Qty')">
                                <input type="hidden" class="old-qty" value="0">
                                <input type="hidden" class="old-type" value="1">

                                <div class="input-group">
                                    <input type="number" step="0.001" min="0.001" name="products[${index}][quantity]" value="1"  class="bg--white form-control quantity" data-id="${data.id}" required>
                                    <span class="input-group-text">${data.unit}</span>
                                </div>
                            </td>
                            <td data-label="@lang('Type')">
                                <select name="products[${index}][adjust_type]" class="form-control adjust-type " required>
                                    <option value="1" @selected(1 == request()->adjust_type)>@lang('Subtract')(-)</option>
                                    <option value="2" @selected(2 == request()->adjust_type)>@lang('Add')(+)</option>
                                </select>
                            </td>
                            <td data-label="@lang('Action')">
                                <button type="button" class="btn btn-outline--danger removeBtn h-45 max-content">
                                    <i class="la la-trash"></i> @lang('Remove')
                                </button>
                            </td>
                        </tr>
                    `);

                setAdjustQuantity($(".productTable tr:last-child").find('.quantity'));

            } else {
                let quantityField = $(`[data-product_id=${productId}]`).find('.quantity');
                quantityField.val(Number(quantityField.val()) + 1);
                setAdjustQuantity(quantityField);
            }

            $(".products").empty();
            $("[name='search']").val("");

        });

        let error = false;

        $(".productTable").on('input', '.quantity', function() {
            if (this.value < 0) {
                this.value = 0;
            }
            setAdjustQuantity($(this));
        });

        $(".productTable").on('change', '.adjust-type', function() {
            setAdjustQuantity($(this));
        });

        function manageSubmitButton() {
            if (error) {
                $('.submit-btn').attr('disabled', 'disabled');
            } else {
                $('.submit-btn').removeAttr('disabled');
            }
        }

        function setAdjustQuantity($this) {

            let parent = $this.parents('tr');
            let adjustQty = parent.find('.quantity').val() * 1;
            let oldQty = parent.find('.old-qty').val() * 1;
            let oldType = parent.find('.old-type').val() * 1;
            let type = parent.find('.adjust-type').val() * 1;

            if (oldType == type && adjustQty == oldQty) { // If same as old value
                adjustQty = 0;
            } else {

                if (oldType == 2 && type == 1) { // If from ADD to SUBTRACT
                    adjustQty = (adjustQty * -1) + (oldQty * -1);
                } else if (oldType == 1 && type == 2) { // If from SUBTRACT to ADD
                    adjustQty += oldQty;
                } else if (type == 1) {
                    adjustQty = oldQty - adjustQty;
                } else {
                    adjustQty = adjustQty - oldQty;
                }
            }

            let stockQty = parent.find('.stock-qty').text() * 1;
            let afterAdjustStock = stockQty + (adjustQty * 1);

            parent.find('.after-adjust-qty').text(afterAdjustStock);

            if (afterAdjustStock < 0) {

                error = true;
                parent.find('.after-adjust-qty').siblings('.error-message').text("You can\'t subtract, the current stock is 0")
                parent.find('.after-adjust-qty').addClass('text--danger');
            } else {
                error = false;
                parent.find('.after-adjust-qty').siblings('.error-message').empty()
                parent.find('.after-adjust-qty').removeClass('text--danger');
            }

            manageSubmitButton()
        }

        $(".productTable").on('click', '.removeBtn', function() {
            let productId = Number($(this).parents('tr').find('.product_id').val());
            let indexToRemove = productArray.indexOf(productId);
            productArray.splice(indexToRemove, 1)
            $(this).parents('tr').remove();
            calculateGrandTotal();
        });

        $('[name=warehouse_id]').on('change', function() {
            if (productArray) {
                productArray = [];
                $("tbody").empty();
            }
        })

        // Product scroll list functionality
        $('#toggle-product-list').on('click', function() {
            const warehouseId = $("[name=warehouse_id]").find(':selected').val();
            if (!warehouseId) {
                $('#warningModal').modal('show');
                return;
            }

            $('#product-scroll-list').toggle();
            if ($('#product-scroll-list').is(':visible')) {
                loadAllProducts(warehouseId);
                $(this).html('<i class="fas fa-search"></i> @lang("Search Mode")');
            } else {
                $(this).html('<i class="fas fa-list"></i> @lang("Browse All")');
            }
        });

        $('#close-product-list').on('click', function() {
            $('#product-scroll-list').hide();
            $('#toggle-product-list').html('<i class="fas fa-list"></i> @lang("Browse All")');
        });

        // Load all products for scroll list
        function loadAllProducts(warehouseId) {
            $.ajax({
                url: "{{ route('admin.adjustment.search.product') }}",
                type: "GET",
                data: {
                    warehouse: warehouseId,
                    search: '', // Empty search to get all products
                    all_products: true
                },
                success: function(response) {
                    displayProductScrollList(response.data);
                },
                error: function() {
                    $('.product-scroll-list-content').html('<p class="text-center text-muted p-3">@lang("Error loading products")</p>');
                }
            });
        }

        // Display products in scroll list
        function displayProductScrollList(products) {
            let html = '';
            const warehouseId = $("[name=warehouse_id]").find(':selected').val();

            if (products && products.length > 0) {
                products.forEach(function(product) {
                    if (product && product.product_stock) {
                        const stock = product.product_stock.find(s => s.warehouse_id == warehouseId);
                        const stockQuantity = stock ? stock.quantity : 0;
                        const unitName = product.unit ? product.unit.name : '';
                        const isOutOfStock = stockQuantity <= 0;

                        html += `
                            <div class="product-scroll-item ${isOutOfStock ? 'out-of-stock' : ''}"
                                 data-product-id="${product.id}"
                                 data-product-name="${product.name}"
                                 data-stock="${stockQuantity}"
                                 data-unit="${unitName}">
                                <div class="product-info">
                                    <div class="fw-bold">${product.name}</div>
                                    <small class="text-muted">SKU: ${product.sku}</small>
                                </div>
                                <div class="product-stock">
                                    <span class="badge ${isOutOfStock ? 'bg-danger' : 'bg-success'}">
                                        ${stockQuantity} ${unitName}
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                });
            } else {
                html = '<p class="text-center text-muted p-3">@lang("No products found")</p>';
            }
            $('.product-scroll-list-content').html(html);
        }

        // Handle product selection from scroll list
        $(document).on('click', '.product-scroll-item:not(.out-of-stock)', function() {
            const productId = $(this).data('product-id');
            const productName = $(this).data('product-name');
            const stock = $(this).data('stock');
            const unit = $(this).data('unit');

            // Add product to adjustment using existing functionality
            if (productArray.includes(productId)) {
                notify('error', 'Product already added');
                return;
            }

            // Simulate click on productItem to use existing logic
            const fakeProductItem = $('<div>').data({
                id: productId,
                name: productName,
                stock: stock,
                unit: unit
            });

            // Trigger existing product selection logic
            let index = $('.product-row ').length + 1;
            productArray.push(productId);

            $(".productTable tbody").append(`
                <tr data-product_id="${productId}" class="product-row">
                    <td data-label="@lang('Name')">
                        ${productName}
                        <input type="hidden" name="products[${index}][product_id]" value="${productId}"/>
                    </td>
                    <td data-label="@lang('Current Stock')">
                        <span class="stock-qty">${stock}</span> ${unit}
                    </td>
                    <td data-label="@lang('Stock - After Adjust')">
                        <span class="after-adjust-qty"></span>
                        ${unit}
                        <br/>
                        <span class="text--danger error-message"></span>
                    </td>
                    <td data-label="@lang('Adjust Qty')">
                        <input type="hidden" class="old-qty" value="0">
                        <input type="hidden" class="old-type" value="1">
                        <div class="input-group">
                            <input type="number" step="0.001" min="0.001" name="products[${index}][quantity]" value="1" class="bg--white form-control quantity" data-id="${productId}" required>
                            <span class="input-group-text">${unit}</span>
                        </div>
                    </td>
                    <td data-label="@lang('Type')">
                        <select name="products[${index}][adjust_type]" class="form-control adjust-type" required>
                            <option value="1">@lang('Subtract')(-)</option>
                            <option value="2">@lang('Add')(+)</option>
                        </select>
                    </td>
                    <td data-label="@lang('Action')">
                        <button type="button" class="btn btn-outline--danger removeBtn h-45 max-content">
                            <i class="la la-trash"></i> @lang('Remove')
                        </button>
                    </td>
                </tr>
            `);

            setAdjustQuantity($(".productTable tr:last-child").find('.quantity'));

            // Close scroll list
            $('#product-scroll-list').hide();
            $('#toggle-product-list').html('<i class="fas fa-list"></i> @lang("Browse All")');
        });

    })(jQuery);
</script>
@endpush