require(['jquery','jquery/jquery.cookie'], function ($) {
    'use strict';

    const SAVE_COOKIE = 'total-save-summary';
    const CHECK_INTERVAL = 1200;

    let lastGrandTotal = '';

    /* ===============================
       MAIN RENDER FUNCTION
    ================================ */

    function renderSaveRow(save) {

        // Remove old UI
        $('.you-save').remove();
        $('.no-cod-showing').remove();

        // Clear cookie
        if (!save || save === '₹ 0') {
            $.removeCookie(SAVE_COOKIE, { path: '/' });
            return;
        }

        // Gift Card Case
        if (save === 'giftvoucher') {

            $.removeCookie(SAVE_COOKIE, { path: '/' });

            const msg = `
                <div class="no-cod-showing">
                    *Please order gift cards separately for Cash on Delivery.
                    E-gift cards aren't available for COD.*
                </div>
            `;

            $('#checkout-payment-method-load').after(msg);
            return;
        }

        // Save cookie
        $.cookie(SAVE_COOKIE, save, {
            expires: 1,
            path: '/'
        });

        // Row HTML
        const rowHtml = `
            <tr class="you-save">
                <th class="mark">
                    <span class="detailed">You Save</span>
                </th>
                <td class="amount">
                    <span class="price">${save}</span>
                </td>
            </tr>
        `;

        // Insert before grand total (works everywhere)
        $('tr.grand.totals').before(rowHtml);
    }


    /* ===============================
       AJAX PRICE FETCH
    ================================ */

    function fetchSavingAmount() {

        $.ajax({
            url: '/common/index/priceupdate/',
            type: 'POST',
            dataType: 'json',

            success: function (res) {
                if (res && res.save !== undefined) {
                    renderSaveRow(res.save);
                }
            },

            error: function (err) {
                console.error('Saving fetch failed', err);
            }
        });
    }


    /* ===============================
       GRAND TOTAL WATCHER
    ================================ */

    function watchGrandTotal() {

        const currentTotal = $('tr.grand.totals .price').first().text().trim();

        if (!currentTotal) return;

        if (currentTotal !== lastGrandTotal) {
            lastGrandTotal = currentTotal;
            fetchSavingAmount();
        }
    }


    /* ===============================
       RESTORE FROM COOKIE (RELOAD)
    ================================ */

    function restoreFromCookie() {

        const saved = $.cookie(SAVE_COOKIE);

        if (saved) {
            renderSaveRow(saved);
        }
    }


    /* ===============================
       INIT
    ================================ */

    function init() {

        restoreFromCookie();

        setInterval(function () {
            watchGrandTotal();
        }, CHECK_INTERVAL);
    }

    $(document).ready(function () {
         init();
        /* ===============================
           COMMON HELPERS
        =============================== */

        function addFocused($el) {
            var $field = $el.closest(".field");
            $field.addClass("focused");

            // mark select fields separately if needed
            if ($el.is('select')) {
                $field.addClass('select');
            }

            var $street = $el.closest("fieldset.field.street");
            if ($street.length) {
                $street.addClass("focused");
            }
        }

        function removeFocused($el) {
            var $field = $el.closest(".field");
            $field.removeClass("focused select");

            var $street = $el.closest("fieldset.field.street");
            if ($street.length) {
                $street.removeClass("focused");
            }
        }

        // Debounce helper (KO safe)
        function debounce(func, wait) {
            var timeout;
            return function () {
                var context = this,
                    args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    func.apply(context, args);
                }, wait);
            };
        }

        /* ===============================
           PREFILLED INPUTS
        =============================== */

        function focusPreFilledInputs() {
            $(".checkout-index-index form input.input-text").each(function () {
                if (this.value !== "") {
                    addFocused($(this));
                }
            });
        }

        /* ===============================
           PREFILLED SELECTS  ✅ NEW
        =============================== */

        function focusPreFilledSelects() {
            $(".checkout-index-index form select.select").each(function () {
                if ($(this).val()) {
                    addFocused($(this));
                }
            });
        }

        // Debounced version for KO re-render
        var debouncedFocus = debounce(function () {
            focusPreFilledInputs();
            focusPreFilledSelects();
        }, 200);

        // Initial run
        debouncedFocus();

        /* ===============================
           KO / DYNAMIC OBSERVER
        =============================== */

        var observer = new MutationObserver(debouncedFocus);
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        /* ===============================
           INPUT EVENTS
        =============================== */

        $(document).on("focusin", ".checkout-index-index form input.input-text", function () {
            addFocused($(this));
        });

        $(document).on("focusout", ".checkout-index-index form input.input-text", function () {
            if (!$(this).val()) {
                removeFocused($(this));
            }
        });

        /* ===============================
           SELECT EVENTS  ✅ NEW
        =============================== */

        $(document).on("change focus", ".checkout-index-index form select.select", function () {
            if ($(this).val()) {
                addFocused($(this));
            } else {
                removeFocused($(this));
            }
        });

        setInterval(AddFocusedClass , 1000);

        function AddFocusedClass() {
          $(".checkout-index-index form select.select").each(function () {
            $(this).parent().parent(".field").addClass("focused select");
          });
        }

        /* ===============================
           GIFT CARD TOGGLE
        =============================== */

        $("#discount-giftcard-form-title").on("click", function () {
            $("#discount-giftcard-form .content").slideToggle(300);
            $("#block-gift-card-discount-heading").toggleClass("active");
        });

        $(document).on(
            "click",
            "fieldset.field.street.admin__control-fields>legend.label",
            function () {
                $(this).parent("fieldset").addClass("focused");
                $(this)
                    .parent("fieldset")
                    .find("div[name='shippingAddress.street.0']")
                    .find("input")
                    .focus();
            }
        );
        jQuery(document).on(
            "click",
            "fieldset.field.street.admin__control-fields>legend.label",
            function () {
                console.log(24234234);
                jQuery(this).parent("fieldset").addClass("focused");
                jQuery(this)
                    .parent("fieldset")
                    .find("div[name='billingAddresssnapmint.street.0']")
                    .find("input")
                    .focus();
            }
        );

        $(document).on("input", 'input[name="postcode"]', function () {

            $(this).addClass('minimum-length-6 validate-length');

            var $fieldWrapper = $(this).closest(".field").siblings(".field");
            var $stateSelect  = $fieldWrapper.find('select[name="region_id"]');
            var $cityInput    = $fieldWrapper.find('input[name="city"]');
            var $regionInput  = $fieldWrapper.find('input[name="region"]');

            let pincode = $(this).val().replace(/\D/g, '').substring(0, 6);
            $(this).val(pincode);

            if (pincode.length !== 6) {
                return;
            }

            $stateSelect.add($cityInput).prop("disabled", true);

            $.ajax({
                url: "https://api.postalpincode.in/pincode/" + pincode,
                method: "GET",
                dataType: "json",
                success: function (response) {

                    if (
                        !response ||
                        !response[0] ||
                        response[0].Status !== "Success" ||
                        !response[0].PostOffice.length
                    ) {
                        resetFields();
                        return;
                    }

                    // Take first post office (all have same District & State)
                    const postOffice = response[0].PostOffice[0];

                    const district = postOffice.District || "";
                    const state    = postOffice.State || "";

                    // City / Region input
                    $cityInput.val(district).trigger("change");
                    $regionInput.val(district).trigger("change");

                    if (district) {
                        $("input[name='city']")
                            .closest(".field")
                            .addClass("focused");
                    }

                    // Select state from dropdown
                    if (state) {
                        const stateValue = $stateSelect
                            .find('option[data-title="' + state + '"]')
                            .val();

                        $stateSelect.val(stateValue || "");
                    }

                    $stateSelect.add($cityInput).prop("disabled", false);
                },
                error: function () {
                    resetFields();
                }
            });

            function resetFields() {
                $cityInput.val("");
                $regionInput.val("");
                $stateSelect.val("");
                $stateSelect.add($cityInput).prop("disabled", false);
            }
        });


        $(document).on(
            "click",
            ".opc-wrapper .shipping-address-item.selected-item",
            function () {
                if (
                    !$("li#shipping.checkout-shipping-address").hasClass("fixed")
                ) {
                    $("li#shipping.checkout-shipping-address").addClass("fixed");
                }
            }
        );
        $(document).on(
            "click",
            "button.action.action-select-shipping-item, button.action.primary.action-save-address",
            function () {
                setTimeout(function () {
                    if (
                        $("li#shipping.checkout-shipping-address").hasClass("fixed")
                    ) {
                        $("li#shipping.checkout-shipping-address").removeClass(
                            "fixed"
                        );
                    }
                }, 500);
            }
        );
        $(document).on("click", "span.cross-btn-shipping", function(e) {
            e.preventDefault();
            $('li#shipping').removeClass('fixed');
        });





        // function addNewRowCheckout(save) {
        //     const $totalsTable = jQuery("table.data.table.totals");
        //     const $grandTotalRow = $totalsTable.find("tr.grand.totals");

        //     $totalsTable.find("tr.you-save").remove();
        //     jQuery(".no-cod-showing").remove();
        //     jQuery.removeCookie("total-save-summary");
        //     if (save === "₹ 0" || save === "giftvoucher") {

        //         if (save === "giftvoucher") {
        //             const giftCardMsg = `
        //                 <div class="no-cod-showing">
        //                     *Please order gift cards separately for Cash on Delivery.
        //                     E-gift cards aren't available for COD.*
        //                 </div>
        //             `;
        //             jQuery("#checkout-payment-method-load").after(giftCardMsg);
        //         }

        //         return;
        //     }

        //     // Case: valid saving amount
        //     const newRowHtml = `
        //         <tr class="you-save">
        //             <th class="mark">
        //                 <span class="detailed">You Save</span>
        //             </th>
        //             <td class="amount">
        //                 <span class="price">${save}</span>
        //             </td>
        //         </tr>
        //     `;

        //     $grandTotalRow.before(newRowHtml);

        //     jQuery.cookie("total-save-summary", save, {
        //         expires: 1,
        //         path: "/"
        //     });
        // }

        // function makeAjaxCall() {
        //     jQuery.ajax({
        //         url: "/common/index/priceupdate/",
        //         type: "POST",
        //         dataType: "json",
        //         success: function (response) {
        //             if (response && response.save !== undefined) {
        //                 addNewRowCheckout(response.save);
        //             }
        //         },
        //         error: function (xhr) {
        //             console.error("Price update failed:", xhr.responseText);
        //         }
        //     });
        // }

        // var initialValue = jQuery(".checkout-cart-index table.data.table.totals tr.grand.totals td.amount span.price").text();
        // function checkForChanges() {
        //     var currentValue = jQuery(".checkout-cart-index table.data.table.totals tr.grand.totals td.amount span.price").text();
        //     if (currentValue !== initialValue) {
        //         makeAjaxCall();
        //         initialValue = currentValue;
        //     }
        // }
        // setInterval(checkForChanges, 1000);

        // function getCookieValue(cookieName) {
        //     var name = cookieName + "=";
        //     var decodedCookie = decodeURIComponent(document.cookie);
        //     var cookieArray = decodedCookie.split(";");
        
        //     for (var i = 0; i < cookieArray.length; i++) {
        //         var cookie = cookieArray[i].trim();
        //         if (cookie.indexOf(name) === 0) {
        //         return cookie.substring(name.length, cookie.length);
        //         }
        //     }
        //     return;
        //     }
        
        //     function addNewRow() {
        //     var formData = getCookieValue("total-save-summary");
        //     if (formData) {
        //         if (formData == "₹ 0") {
        //         jQuery("tr#you-save").remove();
        //         return;
        //         } else {
        //         var formData = getCookieValue("total-save-summary");
        //         var newRowHtml =
        //             '<tr class="you-save" id="you-save">' +
        //             '<th class="mark" colspan="1">' +
        //             '<span class="detailed">You Save</span>' +
        //             "</th>" +
        //             '<td class="amount">' +
        //             '<span class="price">' +
        //             formData +
        //             "</span>" +
        //             "</td>" +
        //             "</tr>";
        
        //         jQuery("tr#you-save").remove();
        //         jQuery(".no-cod-showing").remove();
        //         jQuery(
        //             ".opc-block-summary table.data.table.table-totals tr.grand.totals"
        //         ).before(newRowHtml);
        //         }
        //     }
        //     }
        //     function checkoutOrderSummary() {
        //     if (jQuery("body").hasClass("checkout-index-index")) {
        //         jQuery("tr#you-save").remove();
        //         addNewRow();
        //     }
        //     }
        //     setInterval(checkoutOrderSummary, 4000);

        console.log("✅ Custom checkout JS loaded (inputs + selects, KO safe)");

    });
});

