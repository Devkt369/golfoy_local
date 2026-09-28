/**
 * ArtM SeparateBundleCart - Simple Button Hijack
 * Prevents bundle product submission when attribute is set
 */

define([
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data'
], function ($, urlBuilder, customerData) {
    'use strict';

    return function (config) {
        
        $(document).ready(function () {
            
            // Only run on product pages with add-to-cart forms
            var productId = $('input[name="product"]').val();
            var addToCartForms = $('form[data-role="tocart-form"], #product_addtocart_form');
            
            if (!productId || addToCartForms.length === 0) {
                // Not a product page or no add-to-cart forms, exit silently
                return;
            }
            
            //console.log('ArtM SeparateBundleCart: Product page detected');
            
            // Additional check: only proceed if we're on a product detail page
            if (!$('.product-info-main').length && !$('.product.info.detailed').length) {
                // Not a product detail page
                return;
            }
            
            // Check product info
            $.ajax({
                url: urlBuilder.build('artm_separatebundlecart/debug/productinfo'),
                type: 'POST',
                dataType: 'json',
                data: {
                    product_id: productId,
                    form_key: $('input[name="form_key"]').val()
                },
                success: function(response) {
                    //console.log('Product response:', response);
                    
                    if (response.product_type === 'bundle' && 
                        (response.add_product_separate == 1 || response.add_product_separate === 'Yes')) {
                        
                        //console.log('HIJACKING BUNDLE PRODUCT BUTTONS');
                        hijackButtons(response);
                    }
                }
            });
            
            function hijackButtons(productInfo) {
                // Find ONLY the add to cart buttons and forms (more specific selectors)
                var forms = $('form[data-role="tocart-form"], #product_addtocart_form');
                var buttons = forms.find('button[type="submit"], input[type="submit"]').filter('[title*="Add"], .action.tocart, #product-addtocart-button');
                
                //console.log('Found forms:', forms.length);
                //console.log('Found specific add-to-cart buttons:', buttons.length);
                
                // Store original handlers before overriding
                var originalData = {};
                
                // Only hijack specific add-to-cart forms, be more surgical
                forms.each(function(index) {
                    var form = $(this);
                    // Store original submit handler
                    originalData['form_' + index] = form.data('events');
                    
                    // Override submit with our handler, but only for this specific case
                    form.off('submit.artm').on('submit.artm', function(e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        processInstead();
                        return false;
                    });
                });
                
                // Only hijack specific add-to-cart buttons
                buttons.each(function(index) {
                    var button = $(this);
                    // Store original click handler
                    originalData['button_' + index] = button.data('events');
                    
                    button.off('click.artm').on('click.artm', function(e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        processInstead();
                        return false;
                    });
                });
                
                function processInstead() {
                    //console.log('HIJACKED! Bundle product with add_product_separate=Yes detected. Processing individual products via AJAX instead of normal submission.');
                    
                    // Get form data
                    var form = $('form[data-role="tocart-form"], #product_addtocart_form').first();
                    
                    var bundleOptions = {};
                    form.find('select[name^="bundle_option"], input[name^="bundle_option"]:checked').each(function() {
                        var name = $(this).attr('name');
                        var value = $(this).val();
                        var match = name.match(/bundle_option\[(\d+)\]/);
                        if (match) {
                            bundleOptions[match[1]] = bundleOptions[match[1]] || [];
                            bundleOptions[match[1]].push(value);
                        }
                    });
                    
                    var bundleOptionQty = {};
                    form.find('input[name^="bundle_option_qty"]').each(function() {
                        var name = $(this).attr('name');
                        var value = $(this).val();
                        var match = name.match(/bundle_option_qty\[(\d+)\]/);
                        if (match) {
                            bundleOptionQty[match[1]] = value;
                        }
                    });
                    
                    //console.log('Bundle options:', bundleOptions);
                    
                    // Process via AJAX
                    $.ajax({
                        url: urlBuilder.build('artm_separatebundlecart/ajax/addtocart'),
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            product_id: productId,
                            bundle_options: bundleOptions,
                            bundle_option_qty: bundleOptionQty,
                            qty: form.find('input[name="qty"]').val() || 1,
                            form_key: form.find('input[name="form_key"]').val()
                        },
                        success: function(response) {
                            //console.log('AJAX success:', response);
                            if (response.success) {
                                //console.log('SUCCESS! Added individual products:', response.message);
                                //console.log('Products added count:', response.count);
                                
                                // Update minicart counter and sections
                                updateMinicart();
                                
                                // Try to trigger Magento's add to cart success event
                                $('body').trigger('catalogCategoryAddToCartRedirect', [urlBuilder.build('checkout/cart/'), response]);
                                
                                // Alternative: try to show a simple success message
                                showSuccessMessage(response);
                            } else {
                                console.error('Error adding products:', response.message);
                                showErrorMessage(response.message);
                            }
                        },
                        error: function() {
                            console.error('AJAX Error occurred while adding products to cart');
                            showErrorMessage('Network error occurred. Please try again.');
                        }
                    });
                }
            }
            
            // Function to update minicart after AJAX cart addition
            function updateMinicart() {
                //console.log('Updating minicart sections');
                
                // Reload cart section data from server
                customerData.reload(['cart'], false);
                
                // Also trigger cart update event for other components
                $('body').trigger('processStart');
                
                // Force refresh of customer sections after a short delay
                setTimeout(function() {
                    customerData.invalidate(['cart']);
                    customerData.reload(['cart'], false);
                    $('body').trigger('processStop');
                    //console.log('Minicart update completed');
                }, 500);
            }
            
            // Simple success message function
            function showSuccessMessage(response) {
                // Create a more descriptive notification
                var message = 'Added ' + response.count + ' items to cart';
                if (response.product_count && response.product_count !== response.count) {
                    message = 'Added ' + response.count + ' items (' + response.product_count + ' different products) to cart';
                }
                
                // Try to use Magento's message system if available
                if (typeof window.checkoutConfig !== 'undefined' && window.require) {
                    require(['Magento_Ui/js/model/messageList'], function(messageList) {
                        messageList.addSuccessMessage({message: message});
                    });
                } else {
                    // Fallback: create a simple success div
                    var successDiv = $('<div class="message-success success message" style="position: fixed;top: 20px;right: 20px;z-index: 9999;padding: 15px;background: rgb(26 87 28);color: white;border-radius: 5px;/* display: none; */font-size: 12px;font-weight: 600;">'+
                        message +
                        '</div>');

                    $('body').append(successDiv);
                    
                    // Auto remove after 5 seconds
                    setTimeout(function() {
                        successDiv.fadeOut();
                    }, 5000);
                }
            }
            
            // Error message display function
            function showErrorMessage(errorText) {
                // Create a simple error notification
                var errorDiv = $('<div class="message-error message" style="position: fixed;top: 20px;right: 20px;z-index: 9999;padding: 15px;background: #e22626;color: white;border-radius: 5px;font-size: 12px;font-weight: 600;max-width: 350px;line-height: 1.4;">' + 
                    errorText.replace(/\n/g, '<br>') +
                    '</div>');
                $('body').append(errorDiv);
                
                // Auto remove after 10 seconds
                setTimeout(function() {
                    errorDiv.fadeOut();
                }, 10000);
            }
        });
    };
});