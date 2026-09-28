define([
    'jquery',
    'mage/translate',
    'mage/collapsible'
], function ($, $t) {
    'use strict';

    return function (config) {
        var $root = $(config.rootSelector || '#upload-root');
        if (!$root.length || $root.data('mapping-init')) {
            return;
        }
        $root.data('mapping-init', true);

        var state = {
            uploadId: null,
            csvColumns: [],
            autoMappingMap: {},
            autoMappedColumns: [],
            unmappedColumns: [],
            selectedAttributeSetId: null,
            selectedAttributeSetName: '',
            validation: null,
            attributes: [],
            mappingSelections: {},
            ignoredColumns: {},
            requiredMissing: []
        };

        function renderBaseUi() {
            $root.html([
                '<div class="admin__page-section-item-content">',
                '  <div class="admin__fieldset-wrapper">',
                '    <div class="admin__fieldset-wrapper-title" data-role="title"><strong>' + $t('Upload Product Sheet') + '</strong></div>',
                '    <div class="admin__fieldset-wrapper-content" data-role="content">',
                '      <div class="message message-notice notice" style="margin-bottom:10px;"><div>' + $t('attribute_set is required. You can use parent and child columns for configurable products.') + '</div></div>',
                '      <div class="admin__field">',
                '        <div class="admin__field-control"><input type="file" id="artm-mass-upload-file" class="admin__control-file" accept=".csv,.xlsx" /></div>',
                '      </div>',
                '      <div class="admin__field">',
                '        <div class="admin__field-control">',
                '          <button id="artm-download-sample-csv" type="button" class="action-secondary"><span>' + $t('Download Sample CSV') + '</span></button>',
                '        </div>',
                '      </div>',
                '      <div class="admin__field">',
                '        <div class="admin__field-control">',
                '          <button id="artm-mass-upload-btn" type="button" class="action-primary"><span>' + $t('Upload') + '</span></button>',
                '          <span id="artm-mass-upload-loader" style="display:none;margin-left:10px;">' + $t('Loading...') + '</span>',
                '        </div>',
                '      </div>',
                '      <div id="artm-mass-upload-message" class="message" style="display:none;"></div>',
                '    </div>',
                '  </div>',
                '  <div id="artm-validation-section" class="admin__fieldset-wrapper" style="display:none;margin-top:16px;">',
                '    <div class="admin__fieldset-wrapper-title" data-role="title"><strong>' + $t('ATTRIBUTE SET VALIDATION') + '</strong></div>',
                '    <div class="admin__fieldset-wrapper-content" data-role="content"></div>',
                '  </div>',
                '  <div id="artm-column-mapping-section" class="admin__fieldset-wrapper" style="display:none;margin-top:16px;">',
                '    <div class="admin__fieldset-wrapper-title" data-role="title"><strong>' + $t('Column Mapping') + '</strong></div>',
                '    <div class="admin__fieldset-wrapper-content" data-role="content"></div>',
                '  </div>',
                '</div>'
            ].join(''));

            $root.find('.admin__fieldset-wrapper').collapsible({
                openedState: 'active',
                collapsible: true,
                active: true
            });
        }

        function setLoading(flag) {
            $('#artm-mass-upload-loader').toggle(!!flag);
            $('#artm-mass-upload-btn').prop('disabled', !!flag);
        }

        function showMessage(type, text) {
            $('#artm-mass-upload-message')
                .removeClass('message-success message-error success error')
                .addClass(type === 'success' ? 'message message-success success' : 'message message-error error')
                .text(text)
                .show();
        }

        function downloadSampleCsv() {
            var rows = [
                'sku,product_name,attribute_set,parent,child',
                'CONF-001,Configurable Demo Shirt,Default,,SIMPLE-001|SIMPLE-002',
                'SIMPLE-001,Configurable Demo Shirt Red S,Default,CONF-001,',
                'SIMPLE-002,Configurable Demo Shirt Blue M,Default,CONF-001,'
            ];
            var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'massproducts_sample_upload.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        function uploadFile() {
            var input = $('#artm-mass-upload-file')[0];
            var file = input && input.files ? input.files[0] : null;

            if (!file) {
                showMessage('error', $t('Please choose a CSV or XLSX file.'));
                return;
            }

            var ext = (file.name.split('.').pop() || '').toLowerCase();
            if ($.inArray(ext, ['csv', 'xlsx']) === -1) {
                showMessage('error', $t('Invalid file type. Only CSV and XLSX are allowed.'));
                return;
            }

            var formData = new FormData();
            formData.append('upload_file', file);
            formData.append('form_key', window.FORM_KEY || '');

            setLoading(true);
            $.ajax({
                url: config.uploadUrl,
                type: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Upload failed.'));
                    return;
                }

                state.uploadId = resp.upload_id;
                state.csvColumns = (resp.columns || []).filter(function (col) {
                    var c = col.toLowerCase().trim();
                    return c !== 'attribute_set' && c !== 'parent' && c !== 'child';
                });
                state.autoMappingMap = resp.auto_mapping_map || {};
                state.autoMappedColumns = (resp.auto_mapped || []).filter(function (col) {
                    var c = String(col).toLowerCase().trim();
                    return c !== 'attribute_set' && c !== 'parent' && c !== 'child';
                });
                state.unmappedColumns = (resp.unmapped || []).filter(function (col) {
                    var c = String(col).toLowerCase().trim();
                    return c !== 'attribute_set' && c !== 'parent' && c !== 'child';
                });

                if (state.autoMappingMap.attribute_set) {
                    delete state.autoMappingMap.attribute_set;
                }
                showMessage('success', $t('File uploaded successfully.'));
                runValidation();
            }).fail(function () {
                showMessage('error', $t('Upload failed due to server error.'));
            }).always(function () {
                setLoading(false);
            });
        }

        function runValidation() {
            setLoading(true);
            $.ajax({
                url: config.validateUrl,
                type: 'GET',
                dataType: 'json',
                data: { upload_id: state.uploadId }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    renderValidationError(resp);
                    $('#artm-column-mapping-section').hide();
                    return;
                }

                state.csvColumns = (resp.csv_columns || []).filter(function (col) {
                    var c = col.toLowerCase().trim();
                    return c !== 'attribute_set' && c !== 'parent' && c !== 'child';
                });
                state.validation = resp.attribute_set_validation || null;
                state.selectedAttributeSetId = resp.attribute_set_id || null;
                state.selectedAttributeSetName = resp.attribute_set_name || '';

                renderValidationPanel();

                if (!state.validation || parseInt(state.validation.invalid_rows, 10) > 0) {
                    $('#artm-column-mapping-section').hide();
                    showMessage('error', $t('Fix CSV errors and reupload file.'));
                    return;
                }

                if (!state.selectedAttributeSetId) {
                    $('#artm-column-mapping-section').hide();
                    showMessage('error', $t('Unable to determine a valid attribute set from CSV.'));
                    return;
                }

                loadAttributesBySet(state.selectedAttributeSetId);
            }).fail(function (xhr) {
                var msg = (xhr && xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : $t('Unable to validate attribute_set column.');
                renderValidationError({ message: msg });
                $('#artm-column-mapping-section').hide();
            }).always(function () {
                setLoading(false);
            });
        }

        function renderValidationError(resp) {
            var msg = resp && resp.message ? resp.message : $t('Validation failed.');
            var $section = $('#artm-validation-section');
            $section.find('[data-role="content"]').html('<div class="message message-error error"><div>' + escapeHtml(msg) + '</div></div>');
            $section.show();
        }

        function renderValidationPanel() {
            var val = state.validation || { total_rows: 0, valid_rows: 0, invalid_rows: 0, errors: [] };
            var html = [];
            html.push('<div class="message message-success success"><div>' + $t('Column detected: attribute_set') + ' &#10003;</div></div>');
            html.push('<p><strong>' + $t('Total Rows:') + '</strong> ' + parseInt(val.total_rows, 10) + '</p>');
            html.push('<p><strong>' + $t('Valid Rows:') + '</strong> ' + parseInt(val.valid_rows, 10) + '</p>');
            html.push('<p><strong>' + $t('Invalid Rows:') + '</strong> ' + parseInt(val.invalid_rows, 10) + '</p>');

            if (val.errors && val.errors.length) {
                html.push('<table class="data-grid" style="margin-top:10px;"><thead><tr><th>' + $t('Row') + '</th><th>' + $t('SKU') + '</th><th>' + $t('Error') + '</th></tr></thead><tbody>');
                $.each(val.errors, function (_, item) {
                    html.push('<tr><td>' + escapeHtml(String(item.row || '')) + '</td><td>' + escapeHtml(String(item.sku || '')) + '</td><td>' + escapeHtml(String(item.message || '')) + '</td></tr>');
                });
                html.push('</tbody></table>');
                html.push('<button type="button" id="artm-download-error-csv" class="action-secondary" style="margin-top:10px;"><span>' + $t('Download Error CSV') + '</span></button>');
                html.push('<div class="message message-error error" style="margin-top:10px;"><div>' + $t('Fix CSV errors and reupload file.') + '</div></div>');
            }

            var $section = $('#artm-validation-section');
            $section.find('[data-role="content"]').html(html.join(''));
            $section.show();

            $('#artm-download-error-csv').off('click').on('click', downloadErrorCsv);
        }

        function downloadErrorCsv() {
            var val = state.validation || { errors: [] };
            var rows = ['row,sku,error'];
            $.each(val.errors || [], function (_, item) {
                rows.push([item.row || '', sanitizeCsv(item.sku || ''), sanitizeCsv(item.message || '')].join(','));
            });
            var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'attribute_set_validation_errors.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        function loadAttributesBySet(attributeSetId) {
            $.ajax({
                url: config.attributesBySetUrl,
                type: 'GET',
                dataType: 'json',
                data: { attribute_set_id: attributeSetId }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to load attributes.'));
                    return;
                }

                state.attributes = resp.attributes || [];
                state.mappingSelections = {};
                state.ignoredColumns = {};

                $.each(state.autoMappingMap, function (csvColumn, attributeCode) {
                    if ($.inArray(csvColumn, state.csvColumns) !== -1 && attributeCode) {
                        state.mappingSelections[csvColumn] = attributeCode;
                        state.ignoredColumns[csvColumn] = false;
                    }
                });

                renderMappingGrid();
            }).fail(function () {
                showMessage('error', $t('Unable to load attributes.'));
            });
        }

        function renderMappingGrid() {
            var $section = $('#artm-column-mapping-section');
            var $content = $section.find('[data-role="content"]');
            var html = [];

            html.push('<div class="message message-notice notice"><div>' + $t('Resolved attribute set: ') + escapeHtml(state.selectedAttributeSetName) + '</div></div>');
            if (state.autoMappedColumns.length) {
                html.push('<div class="message message-success success"><div>' + $t('Auto mapped columns: ') + escapeHtml(state.autoMappedColumns.join(', ')) + '</div></div>');
            }
            if (state.unmappedColumns.length) {
                html.push('<div class="message message-warning warning"><div>' + $t('Unmapped columns require manual selection: ') + escapeHtml(state.unmappedColumns.join(', ')) + '</div></div>');
            }
            html.push('<div id="artm-mapping-warning" class="message message-warning warning" style="display:none;"></div>');
            html.push('<div id="artm-mapping-inline-error" class="message message-error error" style="display:none;"></div>');
            html.push('<table class="data-grid" style="margin-top:10px;"><thead><tr><th>' + $t('CSV Column') + '</th><th>' + $t('Map To Attribute') + '</th><th>' + $t('Preview') + '</th></tr></thead><tbody>');

            $.each(state.csvColumns, function (_, column) {
                html.push('<tr>');
                html.push('<td>' + escapeHtml(column) + '</td>');
                html.push('<td>' + renderAttributeSelect(column) + '</td>');
                html.push('<td>' + escapeHtml(column) + '</td>');
                html.push('</tr>');
            });

            html.push('</tbody></table>');
            html.push('<button type="button" id="artm-create-draft-btn" class="action-primary" disabled="disabled"><span>' + $t('Create Draft') + '</span></button>');
            html.push('<span id="artm-draft-loader" style="display:none;margin-left:10px;">' + $t('Saving draft...') + '</span>');
            html.push('<div id="artm-draft-success" style="display:none;margin-top:16px;"></div>');

            $content.html(html.join(''));
            $section.show();

            $content.find('.artm-mapping-select').on('change', function () {
                var col = $(this).data('column');
                var val = $(this).val();
                if (val === '__ignore__') {
                    state.mappingSelections[col] = '';
                    state.ignoredColumns[col] = true;
                } else {
                    state.mappingSelections[col] = val;
                    state.ignoredColumns[col] = false;
                }
                updateDuplicateSelections();
                updateValidationState();
            });

            $('#artm-create-draft-btn').off('click').on('click', saveMappingAndContinue);
            updateDuplicateSelections();
            updateValidationState();
        }

        function renderAttributeSelect(column) {
            var opts = ['<option value="">' + $t('-- Select Attribute --') + '</option>', '<option value="__ignore__">' + $t('Ignore') + '</option>'];
            var selectedValue = state.mappingSelections[column] || '';
            var isLocked = isAutoMappedColumn(column);

            $.each(state.attributes, function (_, a) {
                var label = a.frontend_label || a.attribute_code;
                var selected = selectedValue === a.attribute_code ? ' selected="selected"' : '';
                opts.push('<option value="' + escapeHtml(a.attribute_code) + '"' + selected + '>' + escapeHtml(label + ' (' + a.attribute_code + ')') + '</option>');
            });

            var lockAttr = isLocked ? ' disabled="disabled"' : '';
            var lockNote = isLocked ? '<div style="margin-top:4px;color:#2f7d32;font-size:12px;">' + $t('Auto mapped') + '</div>' : '';
            return '<div><select class="admin__control-select artm-mapping-select" data-column="' + escapeHtml(column) + '"' + lockAttr + '>' + opts.join('') + '</select>' + lockNote + '</div>';
        }

        function updateDuplicateSelections() {
            var used = {};
            $.each(state.mappingSelections, function (_, attr) {
                if (attr) {
                    used[attr] = true;
                }
            });

            $('.artm-mapping-select').each(function () {
                var $select = $(this);
                var own = $select.val();
                $select.find('option').prop('disabled', false);

                if ($select.is(':disabled')) {
                    $select.find('option[value="__ignore__"]').prop('disabled', true);
                }

                $.each(used, function (attr) {
                    if (attr && attr !== own) {
                        $select.find('option[value="' + attr + '"]').prop('disabled', true);
                    }
                });
            });
        }

        function updateValidationState() {
            // Draft phase only checks that every CSV column is either mapped or ignored.
            // Required attribute validation will run during QUEUE stage, not draft stage.
            var allHandled = true;

            $.each(state.csvColumns, function (_, col) {
                var attr = state.mappingSelections[col] || '';
                var ignored = !!state.ignoredColumns[col];
                if (!attr && !ignored) {
                    allHandled = false;
                }
            });

            var $warning = $('#artm-mapping-warning');
            if (!allHandled) {
                $warning.html('<div>' + $t('All CSV columns must be mapped or ignored before creating a draft.') + '</div>').show();
            } else {
                $warning.hide().empty();
            }

            state.requiredMissing = [];
            $('#artm-create-draft-btn').prop('disabled', !allHandled);
        }

        function isAutoMappedColumn(column) {
            return !!state.autoMappingMap[column];
        }

        function saveMappingAndContinue() {
            var mappings = [];
            $.each(state.csvColumns, function (_, col) {
                var attr = state.mappingSelections[col] || '';
                if (attr) {
                    mappings.push({ csv_column: col, attribute_code: attr });
                }
            });

            var $btn = $('#artm-create-draft-btn');
            var $loader = $('#artm-draft-loader');
            $btn.prop('disabled', true);
            $loader.show();
            $('#artm-mapping-inline-error').hide().empty();

            // Step 1: save column mappings
            $.ajax({
                url: config.mappingSaveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    upload_id: state.uploadId,
                    mappings: JSON.stringify(mappings)
                }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    $loader.hide();
                    $btn.prop('disabled', false);
                    $('#artm-mapping-inline-error').text(resp && resp.message ? resp.message : $t('Unable to save mapping.')).show();
                    return;
                }

                // Step 2: create draft rows
                $.ajax({
                    url: config.draftCreateUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        upload_id: state.uploadId
                    }
                }).done(function (draftResp) {
                    $loader.hide();
                    if (!draftResp || !draftResp.success) {
                        $btn.prop('disabled', false);
                        $('#artm-mapping-inline-error').text(draftResp && draftResp.message ? draftResp.message : $t('Unable to create draft.')).show();
                        return;
                    }

                    // Show success panel
                    var batchId = escapeHtml(String(draftResp.batch_id || ''));
                    var savedCount = parseInt(draftResp.saved, 10) || 0;
                    var createdCount = parseInt(draftResp.created_count, 10) || 0;
                    var updateCount = parseInt(draftResp.update_count, 10) || 0;
                    $('#artm-draft-success').html([
                        '<div class="message message-success success">',
                        '<div>&#10004; ' + $t('Draft Created Successfully') + '</div>',
                        '<div><strong>' + $t('Batch ID:') + '</strong> ' + batchId + '</div>',
                        '<div><strong>' + $t('Rows saved:') + '</strong> ' + savedCount + '</div>',
                        '<div><strong>' + $t('Create count:') + '</strong> ' + createdCount + '</div>',
                        '<div><strong>' + $t('Update count:') + '</strong> ' + updateCount + '</div>',
                        '</div>',
                        '<button type="button" id="artm-go-to-drafts-btn" class="action-secondary" style="margin-top:8px;">',
                        '<span>' + $t('Go to Draft Tab') + '</span></button>'
                    ].join('')).show();

                    $btn.hide();
                    $('#artm-go-to-drafts-btn').off('click').on('click', function () {
                        window.location.href = config.draftTabUrl;
                    });

                    // Auto-redirect after 4 seconds
                    setTimeout(function () {
                        window.location.href = config.draftTabUrl;
                    }, 4000);

                }).fail(function () {
                    $loader.hide();
                    $btn.prop('disabled', false);
                    $('#artm-mapping-inline-error').text($t('Unable to create draft.')).show();
                });

            }).fail(function () {
                $loader.hide();
                $btn.prop('disabled', false);
                $('#artm-mapping-inline-error').text($t('Unable to save mapping.')).show();
            });
        }

        function sanitizeCsv(val) {
            var text = String(val || '').replace(/"/g, '""');
            return '"' + text + '"';
        }

        function escapeHtml(val) {
            return $('<div/>').text(val || '').html();
        }

        renderBaseUi();
        $('#artm-download-sample-csv').on('click', downloadSampleCsv);
        $('#artm-mass-upload-btn').on('click', uploadFile);
    };
});
