define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    function toggleUrlPatternField() {
        var pageType = $('[name="page_type"]').val();
        var field = $('[name="url_pattern"]').closest('.admin__field');
        if (!field.length) {
            return;
        }

        if (pageType === 'custom_url') {
            field.show();
        } else {
            field.hide();
            $('[name="url_pattern"]').val('');
        }
    }

    function validateSelectorOnSubmit(event) {
        var selector = $.trim($('[name="css_selector"]').val() || '');
        var regex = /^[#\.\[\]:>+~\*\w\-\s="'(),]+$/;
        if (selector && !regex.test(selector)) {
            event.preventDefault();
            alert($t('Invalid CSS selector format. Use standard CSS selector syntax.'));
            return false;
        }

        var pageType = $('[name="page_type"]').val();
        var urlPattern = $.trim($('[name="url_pattern"]').val() || '');
        if (pageType === 'custom_url' && !urlPattern) {
            event.preventDefault();
            alert($t('URL Pattern is required for custom URL page type.'));
            return false;
        }

        return true;
    }

    return function () {
        $(document).on('change', '[name="page_type"]', toggleUrlPatternField);
        $(document).on('click', 'button.save, button[data-ui-id*="save"]', function (event) {
            validateSelectorOnSubmit(event);
        });

        setTimeout(toggleUrlPatternField, 300);
    };
});
