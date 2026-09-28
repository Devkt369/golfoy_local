//https://magento.stackexchange.com/questions/208834/how-to-post-data-to-a-payment-provider-when-placing-an-order-in-magento-2

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Snapcommerce_Snapmint/js/Snapmint/form-builder',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader, formBuilder, totals, priceUtils, quote) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Snapcommerce_Snapmint/payment/snapmint'
            }, 
            /**
             * Returns payment method instructions.
             *
             * @return {*}
             */
            getInstructions: function () {  
                return window.checkoutConfig.payment.instructions[this.item.method];
            },

            getEmi: function(){
                if (totals.totals()) {
                    var grandTotal = parseFloat(totals.totals()['grand_total']);
                    
                    var emi = (parseFloat(grandTotal) * parseFloat(33.33)) / parseFloat(100);
                    return priceUtils.formatPrice(emi, quote.getPriceFormat());
                }
            },

            emiText: function(){
                if (totals.totals()) {
                    var grandTotal = parseFloat(totals.totals()['grand_total']);

                    if(grandTotal < 3000){
                        var emiText = '2';
                    }else if(grandTotal > 3000){
                        var emiText = '2 / 3'
                    }else{
                        
                    }
                    
                    return emiText;
                }
            },

            getDownPayment: function(){
                if (totals.totals()) {
                    var grandTotal = parseFloat(totals.totals()['grand_total']);
                        if(grandTotal < 3000){
                    var dp = (parseFloat(grandTotal) * parseFloat(33.33)) / parseFloat(100);
                        }else if(grandTotal > 3000){
                      var dp = (parseFloat(grandTotal) * parseFloat(25)) / parseFloat(100);
                        }
                   // return priceUtils.formatPrice(dp, quote.getPriceFormat());

                    return Math.round(dp)
               
                }
            },

            redirectAfterPlaceOrder: false,
            /**
             * After place order callback
             */

            afterPlaceOrder: function () {
                var controller_url = url.build('snapmint/checkout/redirect');
                $.post(controller_url, 'json')
                .done(function (response) {
                    customerData.invalidate(['cart']);

                    console.log('view/frontend/web/js/view/payment/method-renderer/snapmint-method.js');
                    console.log(formBuilder(response).serialize());
                    formBuilder(response).submit(); //this function builds and submits the form
                })
                .fail(function (response) {
                    errorProcessor.process(response, this.messageContainer);
                })
                .always(function () {
                    fullScreenLoader.stopLoader();
                });
            }/*
            afterPlaceOrder: function () {
                
                $(document).ready(function() {
                
                    $.ajax({
                        url: url.build('snapmint/checkout/redirect'),
                        type: 'get',                    
                        dataType: 'json',
                        cache: false,
                        processData: false, // Don't process the files
                        contentType: false, // Set content type to false as jQuery will tell the server its a query string request
                        success: function (data) { 
                            $("#citrusloader",parent.document).html(data['html']);                  
                            //alert(data['html']);
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        }
                    });
                });
            }*/
        });
    }
);
