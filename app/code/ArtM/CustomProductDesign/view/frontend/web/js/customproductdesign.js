require(['jquery'], function($) {
    $(document).ready(function() {

        function hideCustomizationOption() {
            // On product page
            $('.product-options-wrapper .field').each(function () {
                if ($(this).find('label').text().trim() === 'Customization') {
                    $(this).hide();
                }
            });

            // On cart page
            $('.product.options dt').each(function () {
                if ($(this).text().trim() === 'Customization') {
                    $(this).next('dd').hide(); // remove value
                    $(this).hide();            // remove label
                }
            });

            // On checkout (order summary)
            $('.checkout-cart-index .item-options dt').each(function () {
                if ($(this).text().trim() === 'Customization') {
                    $(this).next('dd').hide(); // value
                    $(this).hide();            // label
                }
            });

            // On mini cart
            $('.minicart-items .product.options dt').each(function () {
                if ($(this).text().trim() === 'Customization') {
                    $(this).next('dd').hide();
                    $(this).hide();
                }
            });
        }

        hideCustomizationOption();

    });
});