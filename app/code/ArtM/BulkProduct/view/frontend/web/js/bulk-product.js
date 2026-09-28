/**
 * Copyright © ArtM. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, urlBuilder, alert, $t) {
    'use strict';

    return function (config, element) {
        var $element = $(element);
        var options = {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            sizeOptions: config.sizeOptions || [],
            quantityOptions: config.quantityOptions || [],
            addToCartUrl: config.addToCartUrl || '',
            productId: config.productId || 0
        };

        var BulkProduct = {
            init: function () {
                this.bindEvents();
                this.updateSummary();
            },

            bindEvents: function () {
                // Add to cart button
                $element.on('click', '.bulk-add-to-cart', this.addToCart.bind(this));
            },

            onQuantityChange: function (event) {
                this.updateSummary();
                this.toggleAddToCartButton();
            },

            updateSummary: function () {
                // This is now handled by the inline JavaScript updateTotals() function
                if (typeof updateTotals === 'function') {
                    updateTotals();
                }
            },

            toggleAddToCartButton: function () {
                // This is now handled by the inline JavaScript updateTotals() function
            },

            addToCart: function (event) {
                event.preventDefault();
                
                var items = this.getSelectedItems();
                if (items.length === 0) {
                    alert({
                        title: $t('Error'),
                        content: $t('Please select at least one item with quantity.')
                    });
                    return;
                }

                this.submitBulkOrder(items);
            },

            getSelectedItems: function () {
                var items = [];
                
                $element.find('.bulk-quantity-input').each(function () {
                    var $input = $(this);
                    var qty = parseInt($input.val()) || 0;
                    
                    if (qty > 0) {
                        items.push({
                            product_id: $input.data('product-id'),
                            configurable_product_id: $input.data('configurable-id'),
                            option_product_id: $input.data('option-id'),
                            qty: qty,
                            size: $input.closest('.bulk-option-row').data('size')
                        });
                    }
                });
                
                return items;
            },

            submitBulkOrder: function (items) {
                var self = this;
                var $button = $('#bulk-add-to-cart-btn');
                
                // Show loading
                $button.addClass('loading').prop('disabled', true);
                $element.trigger('processStart');

                $.ajax({
                    url: options.addToCartUrl,
                    type: 'POST',
                    dataType: 'json', 
                    data: {
                        items: items,
                        form_key: $('input[name="form_key"]').val()
                    },
                    success: function (response) {
                        self.handleAddToCartResponse(response);
                        $element.trigger('processStop');
                    },
                    error: function (xhr, status, error) {
                        self.handleAddToCartError(error);
                    },
                    complete: function () {
                        $button.removeClass('loading').prop('disabled', false);
                        $element.trigger('processStop');
                    }
                });
            },

            handleAddToCartResponse: function (response) {
                if (response.success) {
                    require(['Magento_Customer/js/customer-data'], function (customerData) {
                        var sections = ['cart'];
                        customerData.invalidate(sections);
                        customerData.reload(sections, true);
                    });

                    // 🔹 Optionally also trigger this (helps in some themes)
                    $('[data-block="minicart"]').trigger('contentUpdated');

                    var modalHTML = `
                        <div id="add_to_Cart_success_modal" class="atc_success_modal">
                            <div id="modal-content-product" class="modal-content" data-role="content">
                            <button class="action-close" type="button">
                                <span class="close">&times;</span>
                            </button>
                                
                                    <div class="add-to-cart-dialog">
                                        <div class="earn_cashback_text_addtocart_popup">
                                            `+ $('.product_name a').text() +`            
                                            <span> has been added to cart.</span>
                                        </div>
                                    </div>
                                <footer class="modal-footer">
                                    <button class="continue-shopping" type="button"><span>Continue Shopping</span></button>
                                    <button class="review-cart-checkout" type="button"><span>Review Cart &amp; Checkout</span></button>
                                </footer>
                            </div>
                        </div>
                    `;
                    $('body').append(modalHTML);
                    $('[data-block="minicart"]').trigger('contentUpdated');

                } else {
                    alert({
                        title: $t('Error'),
                        content: response.message || $t('Unable to add products to cart. Please try again.')
                    });
                }
            },

            handleAddToCartError: function (error) {
                alert({
                    title: $t('Error'),
                    content: $t('An error occurred while adding products to cart. Please try again.')
                });
                console.error('Bulk add to cart error:', error);
            }
        };

        // Initialize
        BulkProduct.init();

        return BulkProduct;
    };
});