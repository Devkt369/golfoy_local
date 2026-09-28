require(["jquery"], function ($) {
    $(document).ready(function () {
        function checkPincode() {
            var input = $('input[name="postcode"]');
            var value = input.val() || '';
            var submitBtn = $('.customer-address-form button[type="submit"], .customer-address-form .action.primary');
            // remove non-numeric
            value = value.replace(/[^0-9]/g, '');
            input.val(value);
            // message element
            var msg = $('.pincode-message');
            if (msg.length === 0) {
                input.after('<div class="pincode-message" style="color:red;font-size:12px;margin-top:5px;"></div>');
                msg = $('.pincode-message');
            }
            // validation
            if (/^[0-9]{6}$/.test(value)) {
                msg.text('');
                submitBtn.prop('disabled', false)
                    .removeClass('disabled');
                return true;
            } else {
                msg.text('Please enter a valid 6-digit PIN code');
                submitBtn.prop('disabled', true)
                    .addClass('disabled');
                return false;
            }
        }

        $(document).on('keyup change', 'input[name="postcode"]', function () {
            checkPincode();
        });

        checkPincode();
        $(document).on('click', '.customer-address-form button[type="submit"], .customer-address-form .action.primary', function (e) {
            var value = $('input[name="postcode"]').val() || '';
            if (!/^[0-9]{6}$/.test(value)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
        
        if ($(window).width() <= 768) {

            /* Add class based on text for sidebar nav items */
            $('#block-collapsible-nav .nav.items > .nav.item').each(function () {
                var text = $(this).text().trim();
                var className = text.replace(/\s+/g, '_').toLowerCase();
                $(this).addClass(className);
            });

            setInterval(function () {
                $('.title.block-collapsible-nav-title').each(function () {
                    var text = $(this).text().trim();
                    var className = text.replace(/\s+/g, '').toLowerCase();
                    $(this).addClass(className);
                });
            }, 1000);

            if ($(".title.block-collapsible-nav-title").length) {
                var selected_bar = $("div#block-collapsible-nav")
                    .find("li.nav.item.current")
                    .find("strong")
                    .text();
                $(".title.block-collapsible-nav-title").html(
                    "<strong>" + selected_bar + "</strong"
                );
            }
            var title = jQuery('.title.block-collapsible-nav-title strong').html();
            if (title == '') {
                jQuery('body.customer-account-edit .title.block-collapsible-nav-title strong').html('My Account');
            }


        }
    });
});