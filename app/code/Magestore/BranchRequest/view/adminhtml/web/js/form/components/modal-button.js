/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/components/html',
    'uiRegistry'
], function ($, Component, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            imports: {
                canVisibleOnForm: "${$.provider}:data.transfer_summary.general_information.status"
            }
        },

        canVisibleOnForm: function (status) {
            if (!parseInt(status)) {
                // this.visible(true);
            } else {
                this.content('');
            }
        }
    });
});
