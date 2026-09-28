require([
    'jquery',
    'jquery/jquery.cookie'
], function ($) {

    'use strict';

    const CHECK_INTERVAL = 1200;
    const SAVE_COOKIE = 'total-save-summary';

    let lastGrandTotal = '';

    /* =============================
       Render You Save Row
    ============================== */

    function renderSaveRow(save) {

        const $totalsTable = $('table.data.table.totals');
        const $grandRow = $totalsTable.find('tr.grand.totals');

        // Clean old UI
        $totalsTable.find('.you-save').remove();
        $('.no-cod-showing').remove();

        // Remove cookie if invalid
        if (!save || save === '₹ 0' || save == "giftvoucher") {
            $.removeCookie(SAVE_COOKIE, { path: '/' });
            return;
        }

        // Save cookie
        $.cookie(SAVE_COOKIE, save, {
            expires: 1,
            path: '/'
        });

        // Add row
        $grandRow.before(`
            <tr class="you-save">
                <th class="mark">
                    <span class="detailed">You Save</span>
                </th>
                <td class="amount">
                    <span class="price">${save}</span>
                </td>
            </tr>
        `);
    }


    /* =============================
       Fetch Saving (AJAX)
    ============================== */

    function fetchSaving() {

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
                console.error('Saving API Error:', err);
            }
        });
    }


    /* =============================
       Watch Grand Total
    ============================== */

    function watchTotal() {

        const currentTotal = $('tr.grand.totals .price')
            .first()
            .text()
            .trim();

        if (!currentTotal) return;

        if (currentTotal !== lastGrandTotal) {
            lastGrandTotal = currentTotal;
            fetchSaving();
        }
    }


    /* =============================
       Init
    ============================== */

    function init() {

        // Initial value
        lastGrandTotal = $('tr.grand.totals .price')
            .first()
            .text()
            .trim();

        // Watch changes
        setInterval(watchTotal, CHECK_INTERVAL);
    }


    $(document).ready(init);

    /* =============================
       Qty Box & Action Buttons
    ============================== */
    
    $(document).ready(function() {

        // Qty Increment
        $(document).on('click', '.inc-qty_box_cart', function() {
            let $input = $(this).siblings('.input-qty_box_cart').find('input');
            let val = parseFloat($input.val()) || 0;
            $input.val(val + 1).trigger('change');
        });

        // Qty Decrement (allow 0)
        $(document).on('click', '.dec-qty_box_cart', function() {
            let $input = $(this).siblings('.input-qty_box_cart').find('input');
            let val = parseFloat($input.val()) || 0;

            if (val > 0) {
                $input.val(val - 1).trigger('change');
            }
        });

        // Actions UI Handlers
        var $body = $('body');

        // Show delete/edit actions
        $(document).on('click', '.view-action-button', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Close all open action rows first
            $('.table_inside_cart_item').removeClass('active_delete');

            // Open current item actions
            $(this)
                .siblings('.table_inside_cart_item')
                .addClass('active_delete');
        });

        // Hide actions on outside click
        $body.on('click', function() {
            $('.table_inside_cart_item').removeClass('active_delete');
        });

        // Prevent closing when clicking inside cart item
        $(document).on('click', '.table_inside_cart_item', function(e) {
            e.stopPropagation();
        });

        // Adjust action button padding
        function adjustActionButtonPadding() {
            $('table.table_inside_cart_item').each(function() {
                var $table = $(this);
                var $actions = $table.find('.actions-toolbar');

                // Skip gift cart buttons
                if ($actions.hasClass('gift-cart-btns')) {
                    return;
                }

                var height = $table.outerHeight();
                var finalPadding = ((height - 15) / 2 - 23) / 2;

                if (finalPadding > 0) {
                    $actions
                        .find('a.action.action-delete, a.action.action-edit')
                        .css({
                            'padding-top': finalPadding + 'px',
                            'padding-bottom': finalPadding + 'px'
                        });
                }
            });
        }

        adjustActionButtonPadding();
        $(document).ajaxComplete(adjustActionButtonPadding);

    });

});

function moveRatings() {
    document.querySelectorAll(".amrelated-info").forEach(card => {
        const photoContainer = card.querySelector(".amrelated-photo-container");
        const rating = card.querySelector(".amrelated-details .custom-rating");
        if (photoContainer && rating && !photoContainer.contains(rating)) {
            photoContainer.appendChild(rating);
        }
    });
}

window.addEventListener("load", moveRatings);