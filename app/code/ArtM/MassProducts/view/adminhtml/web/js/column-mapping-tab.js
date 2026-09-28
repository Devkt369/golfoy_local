define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (config) {
        var $root = $(config.rootSelector || '#artm-column-mapping-tab-root');
        if (!$root.length || $root.data('inited')) {
            return;
        }
        $root.data('inited', true);

        function escapeHtml(val) {
            return $('<div/>').text(val || '').html();
        }

        var attributeOptions = [];

        function showMessage(type, message) {
            var cls = type === 'success' ? 'message message-success success' : 'message message-error error';
            $('#artm-colmap-msg').attr('class', cls).html('<div>' + escapeHtml(message) + '</div>').show();
        }

        function renderRows(items) {
            var rows = [];
            $.each(items || [], function (_, item) {
                rows.push(renderRow(item.entity_id, item.csv_column, item.attribute_code));
            });

            if (!rows.length) {
                rows.push('<tr><td colspan="3" style="text-align:center;">' + $t('No records found.') + '</td></tr>');
            }

            $('#artm-colmap-body').html(rows.join(''));
            bindRowEvents();
        }

        function renderRow(entityId, csvColumn, attributeCode) {
            var selectedLabel = getAttributeLabel(attributeCode);
            return [
                '<tr data-id="' + escapeHtml(String(entityId || '')) + '">',
                '<td class="artm-col-csv" data-value="' + escapeHtml(csvColumn || '') + '">' + escapeHtml(csvColumn || '') + '</td>',
                '<td class="artm-col-attr" data-value="' + escapeHtml(attributeCode || '') + '">' + escapeHtml(selectedLabel) + '</td>',
                '<td>',
                '<button type="button" class="action-secondary artm-edit-row"><span>' + $t('Edit') + '</span></button> ',
                '<button type="button" class="action-secondary artm-delete-row"><span>' + $t('Delete') + '</span></button>',
                '</td>',
                '</tr>'
            ].join('');
        }

        function getAttributeLabel(attributeCode) {
            var label = attributeCode || '';
            $.each(attributeOptions, function (_, item) {
                if (String(item.value) === String(attributeCode)) {
                    label = item.label;
                    return false;
                }
            });
            return label;
        }

        function buildAttributeSelectHtml(className, selectedValue) {
            var options = ['<option value="">' + $t('-- Select Attribute --') + '</option>'];
            $.each(attributeOptions, function (_, item) {
                var selected = String(item.value) === String(selectedValue || '') ? ' selected="selected"' : '';
                options.push('<option value="' + escapeHtml(item.value) + '"' + selected + '>' + escapeHtml(item.label) + '</option>');
            });
            return '<select class="admin__control-select ' + className + '">' + options.join('') + '</select>';
        }

        function loadAttributeOptions() {
            return $.ajax({
                url: config.attributesUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to load attribute options.'));
                    return;
                }

                attributeOptions = resp.items || [];
                var $addSelect = $('#artm-colmap-new-attribute');
                var html = ['<option value="">' + $t('-- Select Attribute --') + '</option>'];
                $.each(attributeOptions, function (_, item) {
                    html.push('<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</option>');
                });
                $addSelect.html(html.join(''));
            }).fail(function () {
                showMessage('error', $t('Unable to load attribute options.'));
            });
        }

        function loadRows() {
            $.ajax({
                url: config.listUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to load mappings.'));
                    return;
                }
                $('#artm-colmap-msg').hide();
                renderRows(resp.items || []);
            }).fail(function () {
                showMessage('error', $t('Unable to load mappings.'));
            });
        }

        function bindRowEvents() {
            $('.artm-edit-row').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                var entityId = parseInt($tr.attr('data-id'), 10) || 0;
                if (!entityId) {
                    return;
                }

                var csvValue = String($tr.find('.artm-col-csv').attr('data-value') || '');
                var attrValue = String($tr.find('.artm-col-attr').attr('data-value') || '');

                $tr.find('.artm-col-csv').html('<input type="text" class="admin__control-text artm-csv-column-edit" value="' + escapeHtml(csvValue) + '"/>');
                $tr.find('.artm-col-attr').html(buildAttributeSelectHtml('artm-attr-code-edit', attrValue));
                $(this).replaceWith('<button type="button" class="action-secondary artm-save-row"><span>' + $t('Save') + '</span></button> <button type="button" class="action-secondary artm-cancel-row"><span>' + $t('Cancel') + '</span></button>');
                bindRowEvents();
            });

            $('.artm-cancel-row').off('click').on('click', function () {
                loadRows();
            });

            $('.artm-save-row').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                var entityId = parseInt($tr.attr('data-id'), 10) || 0;
                var csvColumn = ($tr.find('.artm-csv-column-edit').val() || '').trim();
                var attributeCode = ($tr.find('.artm-attr-code-edit').val() || '').trim();

                $.ajax({
                    url: config.saveUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        entity_id: entityId,
                        csv_column: csvColumn,
                        attribute_code: attributeCode
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to save mapping.'));
                        return;
                    }
                    showMessage('success', $t('Mapping saved successfully.'));
                    loadRows();
                }).fail(function () {
                    showMessage('error', $t('Unable to save mapping.'));
                });
            });

            $('.artm-delete-row').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                var entityId = parseInt($tr.attr('data-id'), 10) || 0;

                if (!entityId) {
                    $tr.remove();
                    if (!$('#artm-colmap-body tr').length) {
                        renderRows([]);
                    }
                    return;
                }

                $.ajax({
                    url: config.deleteUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        entity_id: entityId
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to delete mapping.'));
                        return;
                    }
                    showMessage('success', $t('Mapping deleted successfully.'));
                    loadRows();
                }).fail(function () {
                    showMessage('error', $t('Unable to delete mapping.'));
                });
            });
        }

        function bindAddAction() {
            $('#artm-colmap-add-btn').off('click').on('click', function () {
                var csvColumn = ($('#artm-colmap-new-column').val() || '').trim();
                var attributeCode = ($('#artm-colmap-new-attribute').val() || '').trim();

                $.ajax({
                    url: config.saveUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        entity_id: 0,
                        csv_column: csvColumn,
                        attribute_code: attributeCode
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to save mapping.'));
                        return;
                    }

                    $('#artm-colmap-new-column').val('');
                    $('#artm-colmap-new-attribute').val('');
                    showMessage('success', $t('Mapping saved successfully.'));
                    loadRows();
                }).fail(function () {
                    showMessage('error', $t('Unable to save mapping.'));
                });
            });
        }

        $.when(loadAttributeOptions()).always(function () {
            bindAddAction();
            loadRows();
        });
    };
});
