define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    // Compatibility shim for stale UI metadata referencing a non-existent Magento_Rule component path.
    return Abstract.extend({
        defaults: {
            elementTmpl: 'ui/form/element/input'
        }
    });
});
