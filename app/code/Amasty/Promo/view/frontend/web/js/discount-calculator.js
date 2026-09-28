define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils'
], function ($, quote, priceUtils) {
    'use strict';

    return {
        /**
         * Update
         *
         * @params {object} products
         * @params {object} item
         * @returns {void}
         */
        update: function (products, item) {
            const priceElement = $(item.target).parent().find('span[data-price-type="basePrice"]');

            if (priceElement.length) {
                let itemPrice = this.getPriceAmount(priceElement),
                    discount = 0,
                    productSku = $(item.target).closest('div.ampromo-item').attr('data-product-sku'),
                    product = null;

                if (productSku in products) {
                    product = products[productSku];
                    const promoDiscount = String(product['discount']['discount_item']),
                        minimalPrice = product['discount']['minimal_price'];
                    if (promoDiscount === "" || promoDiscount === "100%" || promoDiscount === "null") {
                        discount = itemPrice;
                    } else if (promoDiscount.indexOf("%") !== -1) {
                        discount = this.getPercentDiscount(itemPrice, promoDiscount);
                    } else if (promoDiscount.indexOf("-") !== -1) {
                        discount = this.getFixedDiscount(itemPrice, promoDiscount);
                    } else {
                        discount = this.getFixedPrice(itemPrice, promoDiscount);
                    }

                    discount = this.getDiscountAfterMinimalPrice(minimalPrice, itemPrice, discount);
                    $(item.target).parent().find('span[data-price-type="newPrice"]').find('span[class="price"]')[0].innerHTML
                        = priceUtils.formatPrice(itemPrice - discount, quote.getPriceFormat());
                }
            }
        },

        /**
         * Get Percent Discount
         *
         * @params {Number} itemPrice
         * @params {string} promoDiscount
         * @returns {Number}
         */
        getPercentDiscount: function (itemPrice, promoDiscount) {
            const percent = parseFloat(promoDiscount.replace("%", "", promoDiscount));

            return itemPrice * percent / 100;
        },

        /**
         * Get Fixed Discount
         *
         * @params {Number} itemPrice
         * @params {string} promoDiscount
         * @returns {Number}
         */
        getFixedDiscount: function (itemPrice, promoDiscount) {
            let discount = Math.abs(promoDiscount);
            if (discount > itemPrice) {
                discount = itemPrice;
            }

            return discount;
        },

        /**
         * Get Fixed Price
         *
         * @params {Number} itemPrice
         * @params {string} promoDiscount
         * @returns {Number}
         */
        getFixedPrice: function (itemPrice, promoDiscount) {
            let discount = itemPrice - parseFloat(promoDiscount);
            if (discount < 0) {
                discount = 0;
            }

            return discount;
        },

        /**
         * Get Discount After Minimal Price
         *
         * @params {string} minimalPrice
         * @params {Number} itemPrice
         * @params {Number} discount
         * @returns {Number}
         */
        getDiscountAfterMinimalPrice: function (minimalPrice, itemPrice, discount) {
            itemPrice = Number(itemPrice);
            minimalPrice = Number(minimalPrice);
            if (itemPrice > minimalPrice && itemPrice - discount < minimalPrice) {
                discount = itemPrice - minimalPrice;
            }

            return discount;
        },

        /**
         * Get Price Amount
         *
         * @params {object} priceElement
         * @returns {Number}
         */
        getPriceAmount: function (priceElement) {
            return priceElement.data('priceAmount')
                ?? Number(priceElement.find('span[class="price"]')?.replace(/[^.,0-9]/gim, '').replace(/,/g, '.').replace(/[.](?!\d*$)/g, '') ?? 0);
        }
    };
});
