// app/code/ArtM/Review/view/frontend/web/js/view/review-popup.js
define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'jquery',
    'ko'
], function (Component, customerData, $, ko) {
    'use strict';

    return Component.extend({

        showPopup:    ko.observable(false),
        customerId:   ko.observable(''),
        orderId:      ko.observable(''),
        orderDate:    ko.observable(''),
        products:     ko.observableArray([]),
        productCount: ko.observable(0),

        initialize: function () {
            this._super();
            var self = this;
            var reviewSection = customerData.get('artm-review');

            // bind reactively whenever section data changes
            reviewSection.subscribe(function (data) {
                self._applyData(data);
            });

            // apply on first load if already available
            self._applyData(reviewSection());

            return this;
        },

        _applyData: function (data) {
            var self = this;
            if (data && data.show_popup) {
                self.showPopup(true);
                self.customerId(data.customer_id   || '');
                self.orderId(data.order_id         || '');
                self.orderDate(data.order_date     || '');
                self.products(data.products        || []);
                self.productCount(data.product_count || 0);

                // open modal after KO renders the DOM
                setTimeout(function () {
                    $('#open_review_popup').trigger('click');
                }, 1000);
            } else {
                self.showPopup(false);
            }
        }
    });
});