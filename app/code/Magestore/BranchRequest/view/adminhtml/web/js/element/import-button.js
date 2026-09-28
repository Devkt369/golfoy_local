/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/components/button',
    'uiRegistry'
], function ($, Component, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            elementTmpl: 'Magestore_BranchRequest/element/import-button',
            imports: {
                canVisibleOnForm: "${$.provider}:data.transfer_summary.general_information.status"
            }
        },

        handleOnclick: function () {
            $('#import-form').modal('openModal');
        },

        canVisibleOnForm: function (status) {
            if (!parseInt(status)) {
                this.visible(true);
            } else {
                this.visible(false);
            }
        }
    });
});
