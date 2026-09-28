define(['jquery'], function ($) {
    'use strict';

    return function initAttributesAssignmentTab() {
        var $root = $('#massproducts-attributes-assignment');
        if (!$root.length) {
            return;
        }

        var setsUrl = String($root.data('sets-url') || '');
        var attributesUrl = String($root.data('attributes-url') || '');
        var saveUrl = String($root.data('save-url') || '');
        var $setList = $('#massproducts-aa-set-list');
        var $title = $('#massproducts-aa-current-title');
        var $grid = $('#massproducts-aa-attributes');
        var $saveBtn = $('#massproducts-aa-save');

        var currentSetId = 0;
        var currentSetName = 'Global Attributes';

        function esc(text) {
            return $('<div/>').text(String(text || '')).html();
        }

        function renderSets(items) {
            var html = '';
            $.each(items || [], function (_, item) {
                var id = Number(item.attribute_set_id || 0);
                var name = String(item.attribute_set_name || ('Set #' + id));
                html += '<button type="button" class="massproducts-aa-set-item' + (id === currentSetId ? ' is-active' : '') + '" data-id="' + id + '" data-name="' + esc(name) + '">' + esc(name) + '</button>';
            });
            $setList.html(html || '<div class="massproducts-aa-empty">No attribute sets found.</div>');
        }

        function renderAttributes(items) {
            var html = '';
            $.each(items || [], function (_, item) {
                var code = String(item.attribute_code || '');
                var label = String(item.label || code);
                var required = item.is_required ? '<span style="color:#d32f2f;font-weight:700;"> *</span>' : '';
                var checked = item.enabled ? ' checked' : '';
                var disabled = (currentSetId !== 0 && item.global_enabled === false) ? ' disabled' : '';
                var rowClass = disabled ? 'massproducts-aa-row is-disabled' : 'massproducts-aa-row';
                html += '<label class="' + rowClass + '">'
                    + '<input type="checkbox" class="massproducts-aa-check" value="' + esc(code) + '"' + checked + disabled + ' />'
                    + '<span>' + esc(label) + required + '</span>'
                    + '</label>';
            });
            $grid.html(html || '<div class="massproducts-aa-empty">No attributes found.</div>');
        }

        function loadSets() {
            $.ajax({
                url: setsUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    $setList.html('<div class="massproducts-aa-empty">Unable to load sets.</div>');
                    return;
                }
                renderSets(resp.items || []);
                loadAttributes(currentSetId, currentSetName);
            }).fail(function () {
                $setList.html('<div class="massproducts-aa-empty">Unable to load sets.</div>');
            });
        }

        function loadAttributes(setId, setName) {
            currentSetId = Number(setId || 0);
            currentSetName = String(setName || 'Attributes');
            $title.text(currentSetName + ' Attributes');
            $grid.html('<div class="massproducts-aa-empty">Loading...</div>');

            $setList.find('.massproducts-aa-set-item').removeClass('is-active');
            $setList.find('.massproducts-aa-set-item[data-id="' + currentSetId + '"]').addClass('is-active');

            $.ajax({
                url: attributesUrl,
                type: 'GET',
                dataType: 'json',
                data: { attribute_set_id: currentSetId }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    $grid.html('<div class="massproducts-aa-empty">Unable to load attributes.</div>');
                    return;
                }
                renderAttributes(resp.items || []);
            }).fail(function () {
                $grid.html('<div class="massproducts-aa-empty">Unable to load attributes.</div>');
            });
        }

        function saveCurrent() {
            var enabled = [];
            $grid.find('.massproducts-aa-check').each(function () {
                var $el = $(this);
                if ($el.prop('checked')) {
                    enabled.push(String($el.val() || ''));
                }
            });

            $saveBtn.prop('disabled', true).text('Saving...');
            $.ajax({
                url: saveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY,
                    attribute_set_id: currentSetId,
                    enabled_codes: enabled
                }
            }).done(function (resp) {
                if (resp && resp.success) {
                    loadAttributes(currentSetId, currentSetName);
                    alert(resp.message || 'Saved successfully.');
                    return;
                }
                alert((resp && resp.message) ? resp.message : 'Unable to save assignments.');
            }).fail(function () {
                alert('Unable to save assignments.');
            }).always(function () {
                $saveBtn.prop('disabled', false).text('Save');
            });
        }

        $setList.off('click.massproductsAA').on('click.massproductsAA', '.massproducts-aa-set-item', function () {
            var $btn = $(this);
            loadAttributes(Number($btn.data('id') || 0), String($btn.data('name') || 'Attributes'));
        });

        $saveBtn.off('click.massproductsAA').on('click.massproductsAA', function () {
            saveCurrent();
        });

        loadSets();
    };
});
