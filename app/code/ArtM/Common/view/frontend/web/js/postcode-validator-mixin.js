define([], function () {
    'use strict';

    return function (target) {

        var originalValidate = target.validate;

        target.validate = function (postCode, countryId, postCodesPatterns) {
            var result = originalValidate.call(this, postCode, countryId, postCodesPatterns);
            if (!result) {
                return false;
            }
            if (!postCode) {
                return true;
            }
            if (countryId === 'IN') {
                return /^[0-9]{6}$/.test(postCode);
            }
            return result;
        };

        return target;
    };
});