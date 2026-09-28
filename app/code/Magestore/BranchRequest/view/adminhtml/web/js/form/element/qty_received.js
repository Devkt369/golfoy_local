/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/text',
    'uiRegistry'
], function (_, Text, registry) {
    'use strict';

    return Text.extend({
        defaults: {
            imports: {
                visibleOnStatus: "${$.provider}:data.transfer_summary.general_information.status"
            },
        },
        visibleOnStatus: function (status) {
            if (!parseInt(status)) {
                this.visible(false);
            } else {
                this.visible(true);
            }
        }
    });
});
