define([
    'jquery',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Customer/js/customer-data'
], function ($, getTotalsAction, customerData) {
    "use strict";

    $(document).ready(function () {
        // Initialize a variable to hold the timeout ID
        var updateTimer;

        $(document).on('change keyup', 'input[name$="[qty]"]', function () {
            var form = $('form#form-validate');

            // Clear the previous timer if the user acts again before 500ms
            clearTimeout(updateTimer);

            let cartItemsCountBefore = $("tbody.cart.item.cart_page_single_prd").length;

            // Set a new timer
            updateTimer = setTimeout(function () {
                $.ajax({
                    url: form.attr('action'),
                    data: form.serialize(),
                    showLoader: true,
                    success: function (res) {
                        var parsedResponse = $.parseHTML(res);
                        var result = $(parsedResponse).find("#form-validate");
                        if(result.length == 0 ){
                            window.location.reload();
                        }
                        var sections = ['cart'];

                        $("#form-validate").replaceWith(result);

                        // Reload mini cart
                        customerData.reload(sections, true);

                        // Reload totals summary
                        var deferred = $.Deferred();
                        getTotalsAction([], deferred);

                        setTimeout(() => {
                            if ($(".gift-voucher-inline-block input.form-control.input-text").val() > 0) {
                                console.log("Gift voucher code is present, triggering click event on add button.", $(".gift-voucher-inline-block input.form-control.input-text").val());
                                $("button#giftvoucher_add").trigger('click');
                            }
                        }, 1000);

                        // update delivery estimate if cart items count has changed
                        let cartItemsCountAfter = $("tbody.cart.item.cart_page_single_prd").length;
                        if (cartItemsCountAfter !== cartItemsCountBefore) {
                            $.ajax({
                                url: '/estimatedelivery/expressdelivery/calculatetime',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    form_key: window.FORM_KEY
                                },
                                success: function (response) {

                                    if (!response.success || response.html === '') {
                                        console.log('ArtM Review: No delivery estimate available');
                                        return;
                                    }
                                    $('#cart-estimate-delivery').html(response.html);
                                },
                                error: function () {
                                    console.log('ArtM Review: AJAX load failed');
                                }
                            });
                        }


                        // console.log("Cart updated successfully." , $(document).find('ul.checkout.methods.items.checkout-methods-items').length);
                        if ($(document).find('ul.checkout.methods.items.checkout-methods-items').length == 0) {
                            // console.log("Checkout methods are not present, reloading the page.");
                            location.reload();
                        }

                    },
                    error: function (xhr) {
                        console.error("Cart update failed.");
                    }
                });
            }, 500); // 500ms delay
        });


        $(document).on('submit', '#discount-coupon-form', function (e) {
            e.preventDefault();
            var form = $(this);

            var couponInput = $('#coupon_code');
            var button = form.find('.action.apply, .action.cancel'); // captures current state

            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                type: 'POST',
                showLoader: true,
                success: function (response) {
                    if (response.success === true) {
                        var isRemoved = response.removed === true;

                        if (!isRemoved) {
                            // Coupon was APPLIED
                            couponInput.prop('disabled', true);
                            $('#remove-coupon').val('1'); // ✅ FIX: set to 1 so next submit sends remove request
                            button
                                .removeClass('apply')
                                .addClass('cancel')
                                .find('span')
                                .text('Cancel Coupon');
                            form.find('.fieldset.coupon').addClass('applied');
                        } else {
                            // Coupon was REMOVED
                            couponInput.prop('disabled', false).val('');
                            $('#remove-coupon').val('0'); // reset back to 0
                            button
                                .removeClass('cancel')
                                .addClass('apply')
                                .find('span')
                                .text('Apply');
                            form.find('.fieldset.coupon').removeClass('applied');
                        }
                    }

                    // Reload cart sections and totals after state is updated
                    var sections = ['cart'];
                    customerData.reload(sections, true);
                    var deferred = $.Deferred();
                    getTotalsAction([], deferred);
                },
                error: function () {
                    alert('An error occurred while applying the discount.');
                }
            });
        });

    });
});

