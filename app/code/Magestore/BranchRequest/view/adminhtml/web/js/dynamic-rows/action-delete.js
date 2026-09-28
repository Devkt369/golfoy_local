/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/dynamic-rows/action-delete',
    'uiRegistry'
], function (Abstract, registry) {
    'use strict';

    return Abstract.extend({
        defaults: {
            imports: {
                canVisibleOnForm: "${$.provider}:data.transfer_summary.general_information.status"
            },

            canVisibleOnForm: function (status) {
                if (!parseInt(status)) {
                    this.visible(true);
                } else {
                    this.visible(false);
                }
            }
        }
    });
});
