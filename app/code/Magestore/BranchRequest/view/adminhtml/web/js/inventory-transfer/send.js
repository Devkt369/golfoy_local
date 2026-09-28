/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/form/form',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, Form, ModalConfirm) {
    'use strict';

    return Form.extend({
        actionPrevious: '',
        save: function (redirect, data) {
            if (data && data.action) {
                this.actionPrevious = data.action;
            } else if (this.actionPrevious) {
                data = {
                    action: this.actionPrevious
                };
            }
            this.validate();
            if (!this.additionalInvalid && !this.source.get('params.invalid')) {
                if (data && data.action === 'download_summary') {
                    setTimeout(() => {
                        for (let el of document.querySelectorAll('.loading-mask')) el.style.visibility = 'hidden';
                    }, 1000);
                }
                this.setAdditionalData(data)
                    .submit(redirect);
            } else {
                this.focusInvalid();
            }
        }
    });
});
