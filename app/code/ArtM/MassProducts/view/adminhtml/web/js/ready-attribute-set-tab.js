define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return function initReadyAttributeSetTab(config) {
        config = config || {};
        var rowDetailsMap = {};
        var activeUploadProductId = 0;
        var activeUploadRowId = 0;
        var activeUploadDraftId = 0;
        var activeUploadSku = '';

        function ensureSharedStyles() {
            if ($('#artm-shared-table-enhancements-style').length) {
                return;
            }
            $('head').append(
                '<style id="artm-shared-table-enhancements-style">'
                + '.artm-table-title-row{display:flex;justify-content:space-between;align-items:center;gap:10px;}'
                + '.artm-table-title-row > strong{flex:1;min-width:0;}'
                + '.artm-table-title-actions{display:flex;align-items:center;gap:8px;margin-left:auto;justify-content:flex-end;}'
                + '.artm-ready-fieldset{margin-bottom:16px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.06);overflow:hidden;}'
                + '.artm-ready-fieldset .admin__fieldset-wrapper-title{padding:10px 12px;border-bottom:1px solid #edf2f7;background:#f8fafc;}'
                + '.artm-ready-fieldset .admin__fieldset-wrapper-content{padding:8px 10px 10px;}'
                + '.artm-ready-table-wrap{overflow:auto;max-width:100%;}'
                + '.artm-ready-table{min-width:1200px;}'
                + '.artm-table-fullscreen .artm-ready-table thead{position:sticky;top:0;z-index:5;background:#eef2f7;}'
                + '.artm-ready-table td.artm-col-sku,.artm-ready-table th.artm-col-sku{position:sticky;z-index:4;min-width:170px;border-right:1px solid #d7dce1;background:#f1f5f9 !important;}'
                + '.artm-ready-table.artm-has-selection td.artm-col-sku,.artm-ready-table.artm-has-selection th.artm-col-sku{left:40px;}'
                + '.artm-ready-table.artm-no-selection td.artm-col-sku,.artm-ready-table.artm-no-selection th.artm-col-sku{left:0;}'
                + '.artm-ready-table thead th.artm-col-sku{z-index:6;background:#e2e8f0 !important;}'
                + '.artm-expand-table-btn{font-size:11px;padding:4px 8px;border:1px solid #94a3b8;background:#f8fafc;border-radius:4px;cursor:pointer;}'
                + '.artm-expand-table-btn:hover{background:#eef2ff;}'
                + '.artm-toggle-table-visibility-btn{font-size:11px;padding:4px 8px;border:1px solid #94a3b8;background:#f8fafc;border-radius:4px;cursor:pointer;line-height:1;}'
                + '.artm-toggle-table-visibility-btn:hover{background:#eef2ff;}'
                + '.artm-table-fullscreen{position:fixed;inset:8px;z-index:100000;background:#fff;border:1px solid #cbd5e1;border-radius:6px;padding:10px;overflow:auto;}'
                + '.artm-table-fullscreen table{font-size:11px;line-height:1.25;}'
                + '.artm-table-fullscreen .artm-ready-table-wrap{max-height:calc(100vh - 140px);}'
                + '.artm-table-fullscreen th,.artm-table-fullscreen td{padding:2px !important;}'
                + '.artm-ready-table textarea{max-height:120px;overflow:auto;}'
                + '.artm-table-fullscreen input,.artm-table-fullscreen textarea,.artm-table-fullscreen select{font-size:10px !important;padding:2px !important;min-height:20px;line-height:1.2;}'
                + '.artm-table-fullscreen textarea{min-height:34px;max-height:80px;overflow:auto;}'
                + '.artm-table-fullscreen th.artm-col-actions,.artm-table-fullscreen td.artm-col-actions{min-width:74px !important;max-width:74px !important;width:74px !important;}'
                + '.artm-table-fullscreen .artm-ready-links{gap:2px;}'
                + '.artm-table-fullscreen .artm-ready-btn{font-size:10px;padding:2px 4px;line-height:1.2;margin:0;}'
                + '.artm-ready-btn.artm-btn-processing{opacity:.92;pointer-events:none;}'
                + '.artm-ready-btn.artm-btn-processing:after{content:"";display:inline-block;width:10px;height:10px;margin-left:6px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:artmReadyBtnSpin .8s linear infinite;vertical-align:middle;}'
                + '@keyframes artmReadyBtnSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}'
                + '.artm-col-upload-right{position:sticky;right:0;z-index:3;min-width:88px;max-width:88px;width:88px;background:inherit;color:#000;text-align:center;border-left:1px solid #d7dce1;}'
                + 'thead .artm-col-upload-right{z-index:4;background:inherit;color:#000;}'
                + '.artm-col-actions.artm-col-actions-offset{right:88px;}'
                + '.artm-row-moved-out td{opacity:0.75;background:#f8fafc !important;}'
                + '.artm-row-moved-label{display:inline-block;padding:2px 6px;border-radius:10px;background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:700;}'
                + '.artm-ready-link-btn{display:inline-block;margin-top:4px;color:#0f4c81;font-size:11px;text-decoration:underline;}'
                + '.artm-row-detail-modal{display:none;position:fixed;inset:0;z-index:100001;}'
                + '.artm-row-detail-modal .artm-rd-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5);}'
                + '.artm-row-detail-modal .artm-rd-dialog{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:min(980px,95vw);max-height:90vh;overflow:auto;background:#fff;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,.3);}'
                + '.artm-row-detail-modal .artm-rd-head{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid #e2e8f0;}'
                + '.artm-row-detail-modal .artm-rd-body{padding:12px 14px;}'
                + '.artm-row-detail-modal .artm-rd-close{border:none;background:transparent;font-size:22px;cursor:pointer;color:#334155;}'
                + '.artm-row-detail-modal table{width:100%;border-collapse:collapse;font-size:12px;}'
                + '.artm-row-detail-modal td{border-bottom:1px solid #e2e8f0;padding:7px 8px;vertical-align:top;}'
                + '.artm-row-detail-modal td:first-child{width:220px;font-weight:700;color:#334155;}'
                + '.artm-ready-media-gallery{display:flex;flex-wrap:wrap;gap:6px;align-items:flex-start;}'
                + '.artm-ready-media-item{width:54px;height:54px;border:1px solid #d6d6d6;border-radius:4px;overflow:hidden;background:#f8fafc;display:flex;align-items:center;justify-content:center;}'
                + '.artm-ready-media-item img{width:100%;height:100%;object-fit:contain;display:block;}'
                + '.artm-ready-media-upload{width:54px;height:54px;border:2px dashed #9aa4b2;border-radius:4px;background:#f8fafc;color:#334155;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:24px;line-height:1;}'
                + '.artm-ready-media-upload:hover{border-color:#475569;background:#eef2f7;color:#111827;}'
                + '.artm-debug-panel{display:none;margin:10px 0;padding:10px;border:1px solid #fca5a5;background:#fff1f2;border-radius:6px;}'
                + '.artm-debug-panel h4{margin:0 0 8px 0;color:#9f1239;font-size:13px;}'
                + '.artm-debug-panel .artm-debug-help{font-size:12px;color:#4b5563;margin-bottom:8px;}'
                + '.artm-debug-panel .artm-debug-copy{margin-bottom:8px;}'
                + '.artm-debug-panel pre{max-height:280px;overflow:auto;background:#0f172a;color:#e2e8f0;padding:8px;border-radius:4px;font-size:11px;line-height:1.35;}'
                + '</style>'
            );
        }

        function buildProductEditUrl(productId) {
            var tpl = String(config.productEditUrlTemplate || '');
            var id = parseInt(productId, 10) || 0;
            if (!tpl || id <= 0) {
                return '';
            }
            return tpl.replace('__ID__', String(id));
        }

        function prettyJson(value) {
            try {
                return JSON.stringify(value || {}, null, 2);
            } catch (e) {
                return String(value || '');
            }
        }

        function ensureRowDetailModal() {
            if ($('#artm-row-detail-modal').length) {
                return;
            }
            $('body').append(
                '<div id="artm-row-detail-modal" class="artm-row-detail-modal">'
                + '<div class="artm-rd-overlay"></div>'
                + '<div class="artm-rd-dialog">'
                + '<div class="artm-rd-head"><strong id="artm-rd-title"></strong><button type="button" class="artm-rd-close">&times;</button></div>'
                + '<div class="artm-rd-body" id="artm-rd-body"></div>'
                + '</div>'
                + '</div>'
            );
            $(document).off('click.artmRdClose').on('click.artmRdClose', '#artm-row-detail-modal .artm-rd-close, #artm-row-detail-modal .artm-rd-overlay', function () {
                $('#artm-row-detail-modal').hide();
            });
        }

        function openRowDetailModal(rowId) {
            var item = rowDetailsMap[String(rowId)] || null;
            if (!item) {
                return;
            }
            ensureRowDetailModal();

            var row = item.row || {};
            var attrs = item.attrs || [];
            var tableRows = [];
            tableRows.push('<tr><td>' + escapeHtml($t('SKU')) + '</td><td>' + escapeHtml(String(row.sku || '')) + '</td></tr>');
            tableRows.push('<tr><td>' + escapeHtml($t('Product Name')) + '</td><td>' + escapeHtml(String(row.product_name || '')) + '</td></tr>');
            tableRows.push('<tr><td>' + escapeHtml($t('Status')) + '</td><td>' + escapeHtml(String(row.status || '')) + '</td></tr>');
            tableRows.push('<tr><td>' + escapeHtml($t('Operation')) + '</td><td>' + escapeHtml(String(row.operation_type || 'create')) + '</td></tr>');

            $.each(attrs, function (_, attr) {
                var code = String(attr && attr.attribute_code || '');
                if (!code) {
                    return;
                }
                var label = String(attr.frontend_label || code);
                var value = row.values && Object.prototype.hasOwnProperty.call(row.values, code) ? row.values[code] : '';
                tableRows.push('<tr><td>' + escapeHtml(label) + '</td><td>' + escapeHtml(readOnlyValue(attr, value)) + '</td></tr>');
            });

            tableRows.push('<tr><td>' + escapeHtml($t('Source Qty')) + '</td><td>' + msiText(row, item.msiSources || []) + '</td></tr>');
            if (row.error_message) {
                tableRows.push('<tr><td>' + escapeHtml($t('Error')) + '</td><td style="color:#dc2626;">' + escapeHtml(String(row.error_message)) + '</td></tr>');
            }

            var productEditUrl = buildProductEditUrl(row.magento_product_id || 0);
            if (productEditUrl) {
                tableRows.push('<tr><td>' + escapeHtml($t('Magento Product')) + '</td><td><a class="artm-ready-link-btn" href="' + escapeHtml(productEditUrl) + '" target="_blank">' + escapeHtml($t('View Product')) + '</a></td></tr>');
            }

            var payloadSource = row.input_payload || row.values || {};
            tableRows.push('<tr><td>' + escapeHtml($t('Input Payload JSON')) + '</td><td><pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:8px;border-radius:4px;max-height:320px;overflow:auto;">' + escapeHtml(prettyJson(payloadSource)) + '</pre></td></tr>');

            $('#artm-rd-title').text('Details: ' + String(row.sku || row.entity_id || ''));
            $('#artm-rd-body').html('<table><tbody>' + tableRows.join('') + '</tbody></table>');
            $('#artm-row-detail-modal').show();
        }

        function refreshTabCounts() {
            $(document).trigger('artm:massproducts:refreshTabCounts');
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showMessage(type, msg) {
            var cls = type === 'success' ? 'message-success success' : 'message-error error';
            var msgSelector = config.msgSelector || '#artm-ready-msg';
            $(msgSelector)
                .attr('class', 'message ' + cls)
                .html('<div>' + escapeHtml(msg || '') + '</div>')
                .show();
        }

        function setButtonText($btn, text) {
            if (!$btn || !$btn.length) {
                return;
            }
            var $span = $btn.find('span').first();
            if ($span.length) {
                $span.text(text);
            } else {
                $btn.text(text);
            }
        }

        function setButtonProcessing($btn, processingText) {
            if (!$btn || !$btn.length) {
                return;
            }
            if (!$btn.data('artmOriginalText')) {
                $btn.data('artmOriginalText', $.trim($btn.find('span').first().text() || $btn.text() || ''));
            }
            $btn.prop('disabled', true);
            $btn.addClass('artm-btn-processing');
            setButtonText($btn, processingText);
        }

        function setButtonCompleted($btn, completedText) {
            if (!$btn || !$btn.length) {
                return;
            }
            setButtonText($btn, completedText);
            setTimeout(function () {
                var originalText = String($btn.data('artmOriginalText') || '').trim();
                $btn.prop('disabled', false);
                $btn.removeClass('artm-btn-processing');
                if (originalText) {
                    setButtonText($btn, originalText);
                }
            }, 1200);
        }

        function resetButtonState($btn) {
            if (!$btn || !$btn.length) {
                return;
            }
            var originalText = String($btn.data('artmOriginalText') || '').trim();
            $btn.prop('disabled', false);
            $btn.removeClass('artm-btn-processing');
            if (originalText) {
                setButtonText($btn, originalText);
            }
        }

        function ensureDebugPanel() {
            var debugSelector = config.debugSelector || '#artm-ready-debug';
            if ($(debugSelector).length) {
                return $(debugSelector);
            }
            var msgSelector = config.msgSelector || '#artm-ready-msg';
            var $panel = $('<div id="' + String(debugSelector).replace('#', '') + '" class="artm-debug-panel"></div>');
            $(msgSelector).after($panel);
            return $panel;
        }

        function clearDebugPanel() {
            var debugSelector = config.debugSelector || '#artm-ready-debug';
            if ($(debugSelector).length) {
                $(debugSelector).hide().empty();
            }
        }

        function safeText(raw, maxLen) {
            var text = String(raw == null ? '' : raw);
            if (maxLen && text.length > maxLen) {
                return text.substr(0, maxLen) + ' ...[truncated]';
            }
            return text;
        }

        function getRowDebugSnapshot(id) {
            var item = rowDetailsMap[String(id)] || null;
            if (!item || !item.row) {
                return { id: id, found_in_ui_snapshot: false };
            }

            var row = item.row;
            return {
                id: parseInt(row.entity_id, 10) || id,
                found_in_ui_snapshot: true,
                sku: row.sku || '',
                status: row.status || '',
                operation_type: row.operation_type || '',
                error_message: row.error_message || '',
                msi_values: row.msi_values || {},
                media_items: row.media_items || [],
                input_payload: row.input_payload || row.values || {}
            };
        }

        function renderAddProductDebug(ids, resp, xhr) {
            var $panel = ensureDebugPanel();
            var requestIds = (ids || []).filter(function (x) { return x > 0; });
            var snapshots = [];
            $.each(requestIds, function (_, id) {
                snapshots.push(getRowDebugSnapshot(id));
            });

            var payload = {
                type: 'ADD_PRODUCT_DEBUG',
                timestamp: (new Date()).toISOString(),
                add_product_url: config.addProductUrl || '',
                request_ids: requestIds,
                server_response: resp || null,
                xhr_status: xhr && xhr.status ? xhr.status : null,
                xhr_status_text: xhr && xhr.statusText ? xhr.statusText : null,
                xhr_response_text: xhr ? safeText(xhr.responseText, 4000) : null,
                row_snapshots: snapshots,
                next_steps: [
                    'Copy this full block and paste in chat.',
                    'Include the exact row id you clicked.',
                    'If possible, retry once and copy the latest block.'
                ]
            };

            var text = JSON.stringify(payload, null, 2);
            $panel.html(
                '<h4>' + escapeHtml($t('Add Product Debug (Copy & Paste)')) + '</h4>'
                + '<div class="artm-debug-help">' + escapeHtml($t('Copy the block below and paste it in chat for faster root-cause analysis.')) + '</div>'
                + '<button type="button" class="action-secondary artm-debug-copy"><span>' + escapeHtml($t('Copy Debug Block')) + '</span></button>'
                + '<pre class="artm-debug-pre"></pre>'
            ).show();
            $panel.find('.artm-debug-pre').text(text);
            $panel.data('artmDebugText', text);
            $panel.find('.artm-debug-copy').off('click').on('click', function () {
                var debugText = String($panel.data('artmDebugText') || '');
                if (!debugText) {
                    return;
                }

                if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(debugText);
                    return;
                }

                var $tmp = $('<textarea style="position:fixed;left:-9999px;top:-9999px;"></textarea>');
                $('body').append($tmp);
                $tmp.val(debugText).focus().select();
                try {
                    document.execCommand('copy');
                } catch (e) {
                    // noop fallback
                }
                $tmp.remove();
            });
        }

        function normalizeAjaxError(xhr, fallbackMsg) {
            var fallback = fallbackMsg || $t('Request failed.');
            if (!xhr) {
                return fallback;
            }

            if (xhr.responseJSON && xhr.responseJSON.message) {
                return String(xhr.responseJSON.message);
            }

            var body = String(xhr.responseText || '');
            var bodyLower = body.toLowerCase();
            if (bodyLower.indexOf('<!doctype html') !== -1 || bodyLower.indexOf('<html') !== -1) {
                return String($t('Admin session/key seems expired. Please refresh the page and try again.'));
            }

            if (body) {
                return body;
            }

            return fallback;
        }

        function optionLabel(attr, value) {
            var val = String(value == null ? '' : value);
            if (!attr || !Array.isArray(attr.options)) {
                return val;
            }
            var label = val;
            $.each(attr.options, function (_, opt) {
                if (String(opt && opt.value != null ? opt.value : '') === val) {
                    label = String(opt && opt.label != null ? opt.label : val);
                    return false;
                }
                return true;
            });
            return label;
        }

        function readOnlyValue(attr, value) {
            var input = String((attr && attr.frontend_input) || 'text');
            var val = value == null ? '' : String(value).trim();
            if (!val) {
                return '—';
            }

            if (input === 'select' || input === 'boolean') {
                return optionLabel(attr, val) || val;
            }

            if (input === 'multiselect') {
                var pieces = val.split(',').map(function (x) { return String(x).trim(); }).filter(function (x) { return x !== ''; });
                if (!pieces.length) {
                    return '—';
                }
                return pieces.map(function (x) { return optionLabel(attr, x); }).join(', ');
            }

            return val;
        }

        function msiText(row, msiSources) {
            var values = row && row.msi_values ? row.msi_values : {};
            var sourceMap = {};
            $.each(msiSources || [], function (_, src) {
                var code = String(src && src.source_code || '');
                if (!code) {
                    return;
                }
                sourceMap[code] = String(src && src.name || code);
            });

            var lines = [];
            $.each(values, function (sourceCode, qty) {
                if (String(qty).trim() === '') {
                    return;
                }
                var label = sourceMap[sourceCode] ? (sourceMap[sourceCode] + ' (' + sourceCode + ')') : sourceCode;
                lines.push(escapeHtml(label) + ': ' + escapeHtml(String(qty)));
            });

            if (!lines.length) {
                return '—';
            }
            return lines.join('<br/>');
        }

        function applyPagination() {
            var groupsSelector = config.groupsSelector || '#artm-ready-groups';
            var $tables = $(groupsSelector).find('table.artm-ready-table');

            $tables.each(function (index) {
                var $table = $(this);
                var $tbody = $table.find('tbody').first();
                var $rows = $tbody.children('tr');
                var $singleRow = $rows.length === 1 ? $rows.eq(0) : $();
                var noDataRow = $singleRow.length && $singleRow.find('td[colspan]').length > 0 && $singleRow.find('input,button,select,textarea').length === 0;

                var $wrapper = $table.closest('.admin__fieldset-wrapper');
                var $pagerHost = $wrapper.find('.artm-ready-table-pager-host').first();
                if (!$pagerHost.length) {
                    $pagerHost = $('<div class="artm-ready-table-pager-host"></div>');
                    $table.closest('.artm-ready-table-wrap').after($pagerHost);
                }
                $pagerHost.find('.artm-table-pager').remove();

                if (!$rows.length || noDataRow) {
                    return;
                }

                var sizes = [10, 25, 50, 100];
                var perPage = parseInt($table.data('artmPerPage'), 10) || 10;
                if ($.inArray(perPage, sizes) === -1) {
                    perPage = 10;
                }

                var currentPage = parseInt($table.data('artmCurrentPage'), 10) || 1;
                var totalPages = Math.max(1, Math.ceil($rows.length / perPage));
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                var start = (currentPage - 1) * perPage;
                var end = start + perPage;

                $rows.hide().slice(start, end).show();
                $table.data('artmPerPage', perPage);
                $table.data('artmCurrentPage', currentPage);

                var pagerHtml = [];
                pagerHtml.push('<div class="artm-table-pager" data-table-index="' + index + '" style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;">');
                pagerHtml.push('<div style="display:flex;align-items:center;gap:8px;">');
                pagerHtml.push('<label>' + escapeHtml($t('Show')) + '</label>');
                pagerHtml.push('<select class="artm-pager-size admin__control-select" style="width:90px;">');
                $.each(sizes, function (_, size) {
                    pagerHtml.push('<option value="' + size + '"' + (size === perPage ? ' selected="selected"' : '') + '>' + size + '</option>');
                });
                pagerHtml.push('</select>');
                pagerHtml.push('<span>' + escapeHtml($t('per page')) + '</span>');
                pagerHtml.push('</div>');
                pagerHtml.push('<div style="display:flex;align-items:center;gap:8px;">');
                pagerHtml.push('<button type="button" class="action-secondary artm-pager-prev"' + (currentPage <= 1 ? ' disabled="disabled"' : '') + '><span>' + escapeHtml($t('Prev')) + '</span></button>');
                pagerHtml.push('<span>' + escapeHtml($t('Page')) + ' ' + currentPage + ' ' + escapeHtml($t('of')) + ' ' + totalPages + '</span>');
                pagerHtml.push('<button type="button" class="action-secondary artm-pager-next"' + (currentPage >= totalPages ? ' disabled="disabled"' : '') + '><span>' + escapeHtml($t('Next')) + '</span></button>');
                pagerHtml.push('</div>');
                pagerHtml.push('</div>');

                $pagerHost.html(pagerHtml.join(''));
            });

            $(groupsSelector).find('.artm-pager-size').off('change.artmPager').on('change.artmPager', function () {
                var $pager = $(this).closest('.artm-table-pager');
                var idx = parseInt($pager.data('table-index'), 10) || 0;
                var $table = $(groupsSelector).find('table.artm-ready-table').eq(idx);
                $table.data('artmPerPage', parseInt($(this).val(), 10) || 10);
                $table.data('artmCurrentPage', 1);
                applyPagination();
            });

            $(groupsSelector).find('.artm-pager-prev').off('click.artmPager').on('click.artmPager', function () {
                var $pager = $(this).closest('.artm-table-pager');
                var idx = parseInt($pager.data('table-index'), 10) || 0;
                var $table = $(groupsSelector).find('table.artm-ready-table').eq(idx);
                var currentPage = parseInt($table.data('artmCurrentPage'), 10) || 1;
                $table.data('artmCurrentPage', Math.max(1, currentPage - 1));
                applyPagination();
            });

            $(groupsSelector).find('.artm-pager-next').off('click.artmPager').on('click.artmPager', function () {
                var $pager = $(this).closest('.artm-table-pager');
                var idx = parseInt($pager.data('table-index'), 10) || 0;
                var $table = $(groupsSelector).find('table.artm-ready-table').eq(idx);
                var currentPage = parseInt($table.data('artmCurrentPage'), 10) || 1;
                $table.data('artmCurrentPage', currentPage + 1);
                applyPagination();
            });
        }

        function baseImage(row) {
            var items = row && row.media_items ? row.media_items : [];
            var chosen = null;

            $.each(items, function (_, item) {
                var roles = item && item.roles ? item.roles : [];
                if ($.inArray('base', roles) !== -1) {
                    chosen = item;
                    return false;
                }
                return true;
            });

            if (!chosen && items.length) {
                chosen = items[0];
            }

            return chosen;
        }

        function mediaGalleryHtml(row, allowUpload) {
            var items = row && Array.isArray(row.media_items) ? row.media_items : [];
            var html = ['<div class="artm-ready-media-gallery">'];
            $.each(items, function (_, item) {
                var url = String(item && item.url || '');
                html.push('<div class="artm-ready-media-item">' + (url ? '<img src="' + escapeHtml(url) + '" alt=""/>' : '') + '</div>');
            });
            if (allowUpload) {
                html.push('<button type="button" class="artm-ready-media-upload artm-upload-image-row" data-id="' + parseInt(row.entity_id, 10) + '" data-draft-id="' + parseInt(row.entity_id, 10) + '" data-product-id="' + parseInt(row.magento_product_id || 0, 10) + '" data-sku="' + escapeHtml(row.sku || '') + '" title="' + escapeHtml($t('Upload Image')) + '">+</button>');
            }
            html.push('</div>');
            return html.join('');
        }

        function filteredAttrs(attrs) {
            return (attrs || []).filter(function (attr) {
                var label = String(attr && (attr.frontend_label || attr.attribute_code) || '').trim().toLowerCase();
                var code = String(attr && attr.attribute_code || '').trim().toLowerCase();
                if (/^qty\s*[\(\[]/.test(label)) {
                    return false;
                }
                if (code.indexOf('msi_qty__') === 0) {
                    return false;
                }
                return true;
            });
        }

        function parseSkuList(raw) {
            return String(raw == null ? '' : raw)
                .split(',')
                .map(function (item) { return String(item || '').trim(); })
                .filter(function (item) { return item !== ''; });
        }

        function normalizeVariationAttributes(raw) {
            var list = [];
            var items = Array.isArray(raw) ? raw : String(raw || '').split(/[\n,]+/);

            $.each(items, function (_, item) {
                var code = String(item || '').trim();
                if (!code || list.indexOf(code) !== -1) {
                    return;
                }
                list.push(code);
            });

            return list;
        }

        function variationCodeLabel(code, attrs) {
            var cleanCode = String(code || '').trim();
            var label = cleanCode;

            if (!cleanCode) {
                return '';
            }

            $.each(attrs || [], function (_, attr) {
                var attrCode = String(attr && attr.attribute_code || '').trim();
                if (attrCode !== cleanCode) {
                    return true;
                }

                label = String(attr && attr.frontend_label || attrCode).trim() || attrCode;
                return false;
            });

            if (cleanCode === 'quantity_and_stock_status') {
                return 'Quantity';
            }

            return label;
        }

        function renderGroup(group) {
            var html = [];
            var rows = group && group.rows ? group.rows : [];
            var attrs = filteredAttrs(group && group.attributes ? group.attributes : []);
            var isReadOnly = config.isReadOnly || false;
            var enableSelection = typeof config.enableSelection === 'boolean' ? config.enableSelection : !isReadOnly;
            var showDraftAction = typeof config.showDraftAction === 'boolean' ? config.showDraftAction : !isReadOnly;
            var showAddAction = typeof config.showAddAction === 'boolean' ? config.showAddAction : !isReadOnly;
            var showQueueAction = typeof config.showQueueAction === 'boolean' ? config.showQueueAction : !isReadOnly;
            var showViewDetailsAction = !!config.showViewDetailsAction;
            var showViewProductAction = !!config.showViewProductAction;
            var showSkuLink = !!config.showSkuLink;
            var showUploadImageAction = !!config.showUploadImageAction;
            var showApplyImagesAction = !!config.showApplyImagesAction;
            var showProductMediaGallery = !!config.showProductMediaGallery;
            var showUploadColumnRight = !!config.showUploadColumnRight && showUploadImageAction && !!config.uploadProductImageUrl;
            console.log('[ArtM Debug] flags:', { showUploadColumnRight: showUploadColumnRight, showUploadImageAction: showUploadImageAction, uploadProductImageUrl: config.uploadProductImageUrl, configShowUploadColumnRight: config.showUploadColumnRight });
            var hasRowActions = showDraftAction || showAddAction || showQueueAction;
            hasRowActions = hasRowActions || showViewDetailsAction || showViewProductAction || (showUploadImageAction && !showUploadColumnRight);
            var rowAddLabel = config.rowAddLabel || $t('Add product');
            var rowDraftLabel = config.rowDraftLabel || $t('Draft it');
            var rowsBySku = {};
            var inferredParentBySku = {};
            var inferredChildrenBySku = {};
            var displayRows = [];
            var addedSkus = {};

            function addInferredChild(parentSku, childSku) {
                var p = String(parentSku || '').trim();
                var c = String(childSku || '').trim();

                if (!p || !c) {
                    return;
                }
                if (!inferredChildrenBySku[p]) {
                    inferredChildrenBySku[p] = [];
                }
                if (inferredChildrenBySku[p].indexOf(c) === -1) {
                    inferredChildrenBySku[p].push(c);
                }
            }

            function pushDisplayRow(rowObj, isChild, parentSku) {
                var sku = String((rowObj && rowObj.sku) || '').trim();
                if (!sku || addedSkus[sku]) {
                    return;
                }

                addedSkus[sku] = true;
                displayRows.push({
                    row: rowObj,
                    isChild: !!isChild,
                    parentSku: String(parentSku || '').trim()
                });
            }

            $.each(rows, function (_, row) {
                var sku = String((row && row.sku) || '').trim();
                var parentSku = String((row && row.parent) || '').trim();
                var childSkus = parseSkuList(row && row.child);

                if (!sku) {
                    return;
                }

                rowsBySku[sku] = row;

                if (parentSku) {
                    inferredParentBySku[sku] = parentSku;
                    addInferredChild(parentSku, sku);
                }

                $.each(childSkus, function (_, childSku) {
                    addInferredChild(sku, childSku);
                    if (!inferredParentBySku[childSku]) {
                        inferredParentBySku[childSku] = sku;
                    }
                });
            });

            // Group-wise ordering: configurable parent row followed by its child rows.
            $.each(rows, function (_, rowObj) {
                var sku = String((rowObj && rowObj.sku) || '').trim();
                var childSkus = inferredChildrenBySku[sku] || [];

                if (!sku || !childSkus.length) {
                    return;
                }

                pushDisplayRow(rowObj, false, '');

                $.each(childSkus, function (_, childSku) {
                    if (rowsBySku[childSku]) {
                        pushDisplayRow(rowsBySku[childSku], true, sku);
                    }
                });
            });

            $.each(rows, function (_, rowObj) {
                pushDisplayRow(rowObj, false, '');
            });

            var baseColumns = attrs.length + 9;
            var totalColumnCount = baseColumns
                + (enableSelection ? 1 : 0)
                + (hasRowActions ? 1 : 0)
                + (showUploadColumnRight ? 1 : 0);

            html.push('<div class="admin__fieldset-wrapper artm-ready-fieldset">');
            html.push('<div class="admin__fieldset-wrapper-title artm-table-title-row"><strong>' + escapeHtml(group.attribute_set_name || $t('Unknown Attribute Set')) + '</strong><div class="artm-table-title-actions"><button type="button" class="artm-expand-table-btn" data-expanded="0">' + escapeHtml($t('Expand')) + '</button><button type="button" class="artm-toggle-table-visibility-btn" data-collapsed="0" title="' + escapeHtml($t('Collapse table')) + '">&#9650;</button></div></div>');
            html.push('<div class="admin__fieldset-wrapper-content artm-ready-table-wrap">');
            html.push('<table class="data-grid artm-ready-table ' + (enableSelection ? 'artm-has-selection' : 'artm-no-selection') + '">');
            html.push('<thead><tr>');
            if (enableSelection) {
                html.push('<th style="width:40px;"><input type="checkbox" class="artm-ready-check-set" data-set="' + escapeHtml(String(group.attribute_set_name || '')) + '"/></th>');
            }
            html.push('<th class="artm-col-sku">' + $t('SKU') + '</th>');
            html.push('<th class="artm-col-product-url">' + $t('Product URL') + '</th>');
            html.push('<th>' + $t('Operation') + '</th>');
            html.push('<th>' + $t('Status') + '</th>');
            $.each(attrs, function (_, attr) {
                var label = attr.frontend_label || attr.attribute_code;
                html.push('<th>' + escapeHtml(label) + '</th>');
            });
            html.push('<th>' + $t('Source Qty') + '</th>');
            html.push('<th>' + $t('Parent / Variations') + '</th>');
            html.push('<th>' + $t('Child') + '</th>');
            html.push('<th>' + $t('Drafted By') + '</th>');
            html.push('<th>' + $t('Ready By') + '</th>');
            html.push('<th>' + $t('Added By') + '</th>');
            if (hasRowActions) {
                html.push('<th class="artm-col-actions' + (showUploadColumnRight ? ' artm-col-actions-offset' : '') + '">' + $t('Actions') + '</th>');
            }
            if (showUploadColumnRight) {
                html.push('<th class="artm-col-upload-right">' + $t('Upload Image') + '</th>');
            }
            html.push('</tr></thead><tbody>');

            $.each(displayRows, function (_, entry) {
                var row = entry.row;
                var rowSku = String(row.sku || '').trim();
                var rowChildren = inferredChildrenBySku[rowSku] || [];
                var isParentRow = rowChildren.length > 0;
                var isChildRow = !!entry.isChild;
                var parentSku = String(entry.parentSku || inferredParentBySku[rowSku] || row.parent || '').trim();
                var variationCodes = normalizeVariationAttributes(row.variation_attributes || []);
                var variationLabels = variationCodes.map(function (code) {
                    return variationCodeLabel(code, attrs);
                }).filter(function (label) {
                    return String(label || '').trim() !== '';
                });
                var childFromRow = parseSkuList(row.child);
                var childList = rowChildren.length ? rowChildren : childFromRow;
                var isStandaloneRow = !isParentRow && !isChildRow;
                var isLastChildOfGroup = false;

                if (isChildRow && parentSku && inferredChildrenBySku[parentSku] && inferredChildrenBySku[parentSku].length) {
                    var lastChildSku = inferredChildrenBySku[parentSku][inferredChildrenBySku[parentSku].length - 1];
                    isLastChildOfGroup = String(lastChildSku || '').trim() === rowSku;
                }

                html.push('<tr data-id="' + parseInt(row.entity_id, 10) + '" data-sku="' + escapeHtml(rowSku) + '"'
                    + (parentSku ? ' data-parent-sku="' + escapeHtml(parentSku) + '"' : '')
                    + ' class="'
                    + (isParentRow ? 'artm-row-configurable artm-row-block-start ' : '')
                    + (isChildRow ? 'artm-row-child ' : '')
                    + (isStandaloneRow ? 'artm-row-standalone artm-row-block-start artm-row-block-end ' : '')
                    + (isLastChildOfGroup ? 'artm-row-block-end' : '')
                    + '">');
                if (enableSelection) {
                    html.push('<td><input type="checkbox" class="artm-ready-row-check" data-id="' + parseInt(row.entity_id, 10) + '"/></td>');
                }
                var skuCellHtml = escapeHtml(row.sku || '');
                if (showSkuLink) {
                    var skuProductEditUrl = buildProductEditUrl(row.magento_product_id || 0);
                    if (skuProductEditUrl) {
                        skuCellHtml = '<a class="artm-ready-link-btn" href="' + escapeHtml(skuProductEditUrl) + '" target="_blank">' + escapeHtml(row.sku || '') + '</a>';
                    }
                }
                html.push('<td class="artm-col-sku">' + skuCellHtml + '</td>');
                var productUrl = window.location.origin + "/"+ (row.product_url_key || '') + '.html';
                html.push('<td class="artm-col-product-url">' + 
                    '<a href="' + productUrl + '" target="_blank" style="color:#006bb4; font-weight:bold; text-decoration:underline;">' + 
                    escapeHtml($t('View Product')) + 
                    '</a>' + 
                '</td>');
                
                html.push('<td>' + escapeHtml(row.operation_type || 'create') + '</td>');
                // Show status badge with color for each status
                var status = String(row.status || 'ready');
                var statusClass = 'artm-ready-status';
                if (status === 'queued') statusClass += ' artm-status-queued';
                if (status === 'processing') statusClass += ' artm-status-processing';
                if (status === 'success') statusClass += ' artm-status-success';
                if (status === 'failed') statusClass += ' artm-status-failed';
                html.push('<td><span class="' + statusClass + '">' + escapeHtml(status) + '</span>' + (row.error_message ? '<br/><span style="color:#dc2626;font-size:11px;">' + escapeHtml(row.error_message) + '</span>' : '') + '</td>');

                $.each(attrs, function (_, attr) {
                    var code = attr.attribute_code;
                    var value = row.values && Object.prototype.hasOwnProperty.call(row.values, code) ? row.values[code] : '';
                    html.push('<td>' + escapeHtml(readOnlyValue(attr, value)) + '</td>');
                });

                html.push('<td>' + msiText(row, group.msi_sources || []) + '</td>');
                html.push('<td>' + (isParentRow
                    ? (variationLabels.length ? escapeHtml(variationLabels.join(', ')) : '<span style="color:#94a3b8;">' + escapeHtml($t('No variation attributes')) + '</span>')
                    : (parentSku ? escapeHtml(parentSku) : '—')) + '</td>');
                html.push('<td>' + (childList.length
                    ? childList.map(function (sku) { return '<div>' + escapeHtml(sku) + '</div>'; }).join('')
                    : '—') + '</td>');
                html.push('<td>' + escapeHtml(row.drafted_by || '-') + '</td>');
                html.push('<td>' + escapeHtml(row.ready_by || '-') + '</td>');
                html.push('<td>' + escapeHtml(row.added_by || '-') + '</td>');
                if (hasRowActions) {
                    var actionButtons = [];
                    if (showDraftAction) {
                        actionButtons.push('<button type="button" class="artm-ready-btn artm-draft-btn artm-draft-row" data-id="' + parseInt(row.entity_id, 10) + '">' + escapeHtml(rowDraftLabel) + '</button>');
                    }
                    if (showAddAction) {
                        actionButtons.push('<button type="button" class="artm-ready-btn artm-add-btn artm-add-product-row" data-id="' + parseInt(row.entity_id, 10) + '">' + escapeHtml(rowAddLabel) + '</button>');
                    }
                    if (showViewDetailsAction) {
                        // actionButtons.push('<button type="button" class="artm-ready-btn artm-view-details-row" data-id="' + parseInt(row.entity_id, 10) + '">' + escapeHtml($t('View Details')) + '</button>');
                    }
                    if (showViewProductAction) {
                        var viewUrl = buildProductEditUrl(row.magento_product_id || 0);
                        if (viewUrl) {
                            // actionButtons.push('<a class="artm-ready-link-btn" href="' + escapeHtml(viewUrl) + '" target="_blank">' + escapeHtml($t('View Product')) + '</a>');
                        }
                    }
                    if (!showUploadColumnRight && showUploadImageAction && config.uploadProductImageUrl && parseInt(row.magento_product_id, 10) > 0) {
                        actionButtons.push('<button type="button" class="artm-ready-btn artm-upload-image-row" data-id="' + parseInt(row.entity_id, 10) + '" data-draft-id="' + parseInt(row.entity_id, 10) + '" data-product-id="' + parseInt(row.magento_product_id, 10) + '" data-sku="' + escapeHtml(row.sku || '') + '">' + escapeHtml($t('Upload Image')) + '</button>');
                    }
                    rowDetailsMap[String(parseInt(row.entity_id, 10))] = {
                        row: row,
                        attrs: attrs,
                        msiSources: group.msi_sources || []
                    };
                    html.push('<td class="artm-col-actions' + (showUploadColumnRight ? ' artm-col-actions-offset' : '') + '"><div class="artm-ready-links">' + actionButtons.join('') + '</div></td>');
                }
                if (row.base_image == '') {
                    var uploadCell = '-';

                    if (parseInt(row.entity_id, 10) > 0) {
                        let uploadImageHtml = `<div class="field product-field-media">
                                                    <div id="product-drag-area_${row.sku}" class="product-drag-area" style="border:3px dashed lightgray;border-radius:10px;">   
                                                        <p class="product-dragdrop" style="text-align:center;color:gray;font-weight: 700;font-size: 25px;margin: 0 auto;cursor: pointer;"> + </p> 
                                                    </div> 

                                                    <div class="control">
                                                        <input type="file"  style="display:none !important;" name="product_media[]" id="product_media_${row.sku}" accept=".png, .jpg, .jpeg" multiple="multiple" />
                                                    </div>

                                                </div>`;

                        uploadCell = '<form action="#" method="post" enctype="multipart/form-data" class="artm-upload-image-form" data-product-id="' + row.sku + '" data-sku="' + escapeHtml(row.sku || '') + '">'
                                    + ' <div style="display:flex;flex-direction:column;gap:4px;">'
                                    + uploadImageHtml
                                    + '<button type="submit" class="artm-ready-btn_ artm-apply-images-row_" data-draft-id="' + parseInt(row.entity_id, 10) + '" data-sku="' + escapeHtml(row.sku || '') + '">' + escapeHtml($t('Add In Product')) + '</button>'
                                    + '</div></form>';
                    }
                    html.push('<td class="artm-col-upload-right">' + uploadCell + '</td>');
                }else{
                    html.push('<td class="artm-col-upload-right"><div class="product-base-image"><img src="' + (row.base_image || '') + '" style="width:50px;height:50px;margin:2px;"></div></td>');
                }
                html.push('</tr>');
            });

            if (!displayRows.length) {
                html.push('<tr><td colspan="' + totalColumnCount + '" style="text-align:center;">' + $t('No products in this attribute set.') + '</td></tr>');
            }

            html.push('</tbody></table></div><div class="artm-ready-table-pager-host"></div></div>');
            return html.join('');
        }

        


        function normalizeGroups(resp) {
            if (resp && Array.isArray(resp.groups)) {
                return resp.groups;
            }

            if (!resp || !Array.isArray(resp.rows)) {
                return [];
            }

            function parseDateScore(value) {
                var raw = String(value == null ? '' : value).trim();
                if (!raw) {
                    return 0;
                }
                var normalized = raw.indexOf('T') === -1 ? raw.replace(' ', 'T') : raw;
                var ts = Date.parse(normalized);
                return isNaN(ts) ? 0 : ts;
            }

            // Newest product creation date first for Success/Failed tabs.
            resp.rows.sort(function (a, b) {
                var aScore = parseDateScore((a && a.product_created_at) || (a && a.created_at));
                var bScore = parseDateScore((b && b.product_created_at) || (b && b.created_at));
                if (aScore !== bScore) {
                    return bScore - aScore;
                }
                return (parseInt(b && b.entity_id, 10) || 0) - (parseInt(a && a.entity_id, 10) || 0);
            });

            var grouped = {};
            $.each(resp.rows, function (_, row) {
                var setName = String((row && row.attribute_set) || $t('Default')).trim() || String($t('Default'));
                var key = setName.toLowerCase();
                if (!grouped[key]) {
                    grouped[key] = {
                        attribute_set_id: parseInt(row && row.attribute_set_id, 10) || 0,
                        attribute_set_name: setName,
                        attributes: [],
                        msi_sources: resp && Array.isArray(resp.msi_sources) ? resp.msi_sources : [],
                        rows: []
                    };
                }

                var mapped = {};
                if (row && row.input_payload && typeof row.input_payload === 'object') {
                    mapped = row.input_payload;
                } else if (row && row.input_payload_json) {
                    try {
                        mapped = JSON.parse(String(row.input_payload_json));
                    } catch (e) {
                        mapped = {};
                    }
                } else if (row && row.mapped_data) {
                    try {
                        var decoded = typeof row.mapped_data === 'string' ? JSON.parse(row.mapped_data) : row.mapped_data;
                        if (decoded && typeof decoded === 'object') {
                            mapped = decoded;
                        }
                    } catch (e) {
                        mapped = {};
                    }
                }

                grouped[key].rows.push({
                    entity_id: parseInt(row && row.entity_id, 10) || 0,
                    sku: row && row.sku ? row.sku : '',
                    parent: row && row.parent ? row.parent : (row && row.parent_sku ? row.parent_sku : ''),
                    child: row && row.child ? row.child : (row && row.child_sku ? row.child_sku : ''),
                    drafted_by: row && row.drafted_by ? row.drafted_by : '',
                    ready_by: row && row.ready_by ? row.ready_by : '',
                    added_by: row && row.added_by ? row.added_by : '',
                    variation_attributes: row && row.variation_attributes ? row.variation_attributes : [],
                    product_name: row && row.product_name ? row.product_name : '',
                    operation_type: row && row.operation_type ? row.operation_type : 'create',
                    status: row && row.status ? row.status : 'ready',
                    magento_product_id: parseInt(row && row.magento_product_id, 10) || 0,
                    error_message: row && row.error_message ? row.error_message : '',
                    input_payload: mapped,
                    values: mapped,
                    msi_values: row && row.msi_values && typeof row.msi_values === 'object' ? row.msi_values : {},
                    media_items: row && Array.isArray(row.media_items) ? row.media_items : [],
                    base_image: row && row.base_image ? row.base_image : '',
                    product_url_key: row && row.product_url_key ? row.product_url_key : '',
                    product_created_at: row && row.product_created_at ? row.product_created_at : '',
                    created_at: row && row.created_at ? row.created_at : ''
                });

                $.each(Object.keys(mapped), function (_, code) {
                    var exists = grouped[key].attributes.some(function (a) {
                        return String(a.attribute_code) === String(code);
                    });
                    if (!exists) {
                        grouped[key].attributes.push({
                            attribute_code: code,
                            frontend_label: code,
                            frontend_input: 'text',
                            options: []
                        });
                    }
                });
            });

            return Object.keys(grouped).map(function (k) {
                var group = grouped[k];
                group.rows.sort(function (a, b) {
                    var aScore = parseDateScore(a.product_created_at || a.created_at);
                    var bScore = parseDateScore(b.product_created_at || b.created_at);
                    if (aScore !== bScore) {
                        return bScore - aScore;
                    }
                    return (parseInt(b.entity_id, 10) || 0) - (parseInt(a.entity_id, 10) || 0);
                });
                return group;
            });
        }

        function markRowsAfterAdd(processed) {
            if (!Array.isArray(processed) || !processed.length) {
                return;
            }

            $.each(processed, function (_, item) {
                var id = parseInt(item && item.id, 10) || 0;
                if (id <= 0) {
                    return;
                }
                var ok = !!(item && item.success);
                var $tr = $('tr[data-id="' + id + '"]');
                if (!$tr.length) {
                    return;
                }

                $tr.addClass('artm-row-moved-out');
                $tr.find('input,select,textarea,button').prop('disabled', true);
                var $status = $tr.find('.artm-ready-status').first();
                if ($status.length) {
                    $status.text(ok ? 'added' : 'failed').removeClass('artm-status-ready artm-status-queued artm-status-processing artm-status-success artm-status-failed').addClass(ok ? 'artm-status-success' : 'artm-status-failed');
                }

                var $links = $tr.find('.artm-ready-links').first();
                if ($links.length) {
                    var label = ok ? $t('Product Added') : $t('Sent To Failed Tab');
                    $links.html('<span class="artm-row-moved-label">' + escapeHtml(label) + '</span>');
                }
            });
        }

        function selectedIds() {
            return $('.artm-ready-row-check:checked').map(function () {
                return parseInt($(this).data('id'), 10) || 0;
            }).get().filter(function (x) { return x > 0; });
        }

        function markRowsMoved(ids, labelText) {
            var safeIds = (ids || []).filter(function (x) { return x > 0; });
            $.each(safeIds, function (_, id) {
                var $tr = $('tr[data-id="' + parseInt(id, 10) + '"]');
                if (!$tr.length) {
                    return;
                }
                $tr.addClass('artm-row-moved-out');
                $tr.find('input,select,textarea,button').prop('disabled', true);
                var $links = $tr.find('.artm-ready-links').first();
                if ($links.length) {
                    $links.html('<span class="artm-row-moved-label">' + escapeHtml(labelText || $t('Moved')) + '</span>');
                }
            });
        }

        function moveToDraft(ids, actionState) {
            ids = (ids || []).filter(function (x) { return x > 0; });
            if (!ids.length) {
                showMessage('error', $t('No rows selected.'));
                return;
            }

            showMessage('success', actionState && actionState.processingMessage ? actionState.processingMessage : $t('Processing...'));

            $.ajax({
                url: config.moveToDraftUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    ids: ids
                }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to move rows to draft.'));
                    resetButtonState(actionState && actionState.button ? actionState.button : $());
                    return;
                }

                // For single-row Draft action, keep the processing/success state visible
                // briefly before replacing the row actions with the moved label.
                if (actionState && actionState.rowLevel && actionState.button && actionState.button.length) {
                    setButtonText(actionState.button, actionState.doneText ? actionState.doneText : $t('Drafted'));
                    actionState.button.removeClass('artm-btn-processing').prop('disabled', true);
                    setTimeout(function () {
                        markRowsMoved(ids, config.moveToDraftMovedLabel || $t('Moved to Draft'));
                        refreshTabCounts();
                    }, 700);
                    return;
                }

                markRowsMoved(ids, config.moveToDraftMovedLabel || $t('Moved to Draft'));
                setButtonCompleted(actionState && actionState.button ? actionState.button : $(), actionState && actionState.doneText ? actionState.doneText : $t('Drafted'));
                refreshTabCounts();
            }).fail(function (xhr) {
                showMessage('error', normalizeAjaxError(xhr, $t('Unable to move rows to draft.')));
                resetButtonState(actionState && actionState.button ? actionState.button : $());
            });
        }

        function moveToQueue(ids, actionState) {
            ids = (ids || []).filter(function (x) { return x > 0; });
            if (!ids.length) {
                showMessage('error', $t('No rows selected.'));
                return;
            }

            showMessage('success', actionState && actionState.processingMessage ? actionState.processingMessage : $t('Processing...'));

            $.ajax({
                url: config.moveToQueueUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    ids: ids
                }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to add rows to queue.'));
                    resetButtonState(actionState && actionState.button ? actionState.button : $());
                    return;
                }
                markRowsMoved(ids, $t('Moved to Queue'));
                setButtonCompleted(actionState && actionState.button ? actionState.button : $(), actionState && actionState.doneText ? actionState.doneText : $t('Queued'));
                refreshTabCounts();
            }).fail(function (xhr) {
                showMessage('error', normalizeAjaxError(xhr, $t('Unable to add rows to queue.')));
                resetButtonState(actionState && actionState.button ? actionState.button : $());
            });
        }

        function addProducts(ids, actionState) {
            ids = (ids || []).filter(function (x) { return x > 0; });
            if (!ids.length) {
                showMessage('error', $t('No rows selected.'));
                return;
            }

            showMessage('success', actionState && actionState.processingMessage ? actionState.processingMessage : $t('Processing...'));

            $.ajax({
                url: config.addProductUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    ids: ids
                }
            }).done(function (resp) {
                if (resp && Array.isArray(resp.processed) && resp.processed.length) {
                    markRowsAfterAdd(resp.processed);
                    refreshTabCounts();
                }
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to add product.'));
                    renderAddProductDebug(ids, resp || null, null);
                    resetButtonState(actionState && actionState.button ? actionState.button : $());
                    return;
                }
                clearDebugPanel();
                showMessage('success', resp.message || $t('Product created.'));
                setButtonCompleted(actionState && actionState.button ? actionState.button : $(), actionState && actionState.doneText ? actionState.doneText : $t('Added'));
            }).fail(function (xhr) {
                showMessage('error', normalizeAjaxError(xhr, $t('Unable to add product.')));
                renderAddProductDebug(ids, null, xhr);
                resetButtonState(actionState && actionState.button ? actionState.button : $());
            });
        }

        function bindActions() {
            var isReadOnly = config.isReadOnly || false;
            var enableSelection = typeof config.enableSelection === 'boolean' ? config.enableSelection : !isReadOnly;
            var showDraftAction = typeof config.showDraftAction === 'boolean' ? config.showDraftAction : !isReadOnly;
            var showAddAction = typeof config.showAddAction === 'boolean' ? config.showAddAction : !isReadOnly;
            var showQueueAction = typeof config.showQueueAction === 'boolean' ? config.showQueueAction : !isReadOnly;
            var showUploadImageAction = !!config.showUploadImageAction;
            var showApplyImagesAction = !!config.showApplyImagesAction;
            var showMassDraft = typeof config.showMassDraft === 'boolean' ? config.showMassDraft : showDraftAction;
            var showMassAdd = typeof config.showMassAdd === 'boolean' ? config.showMassAdd : showAddAction;
            var showMassQueue = typeof config.showMassQueue === 'boolean' ? config.showMassQueue : showQueueAction;
            var showViewDetailsAction = !!config.showViewDetailsAction;

            if (enableSelection) {
                $('#artm-ready-select-all').off('change').on('change', function () {
                    $('.artm-ready-row-check').prop('checked', $(this).is(':checked'));
                });

                $('.artm-ready-check-set').off('change').on('change', function () {
                    var checked = $(this).is(':checked');
                    $(this).closest('table').find('.artm-ready-row-check').prop('checked', checked);
                });
            }

            if (showMassDraft && config.moveToDraftUrl) {
                $('#artm-ready-mass-draft').off('click').on('click', function () {
                    var $btn = $(this);
                    setButtonProcessing($btn, $t('Mass Drafting...'));
                    moveToDraft(selectedIds(), {
                        button: $btn,
                        processingMessage: $t('Mass draft processing...'),
                        doneText: $t('Mass Drafted')
                    });
                });
            }

            if (showMassAdd && config.addProductUrl) {
                $('#artm-ready-mass-add').off('click').on('click', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    setButtonProcessing($btn, $t('Adding...'));
                    addProducts(selectedIds(), {
                        button: $btn,
                        processingMessage: $t('Mass add processing...'),
                        doneText: $t('Added')
                    });
                });
            }

            if (showMassQueue && config.moveToQueueUrl) {
                $('#artm-ready-mass-queue').off('click').on('click', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    setButtonProcessing($btn, $t('Queueing...'));
                    moveToQueue(selectedIds(), {
                        button: $btn,
                        processingMessage: $t('Queue processing...'),
                        doneText: $t('Queued')
                    });
                });
            }

            if (showDraftAction && config.moveToDraftUrl) {
                $('.artm-draft-row').off('click').on('click', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var id = parseInt($btn.data('id'), 10) || 0;
                    if (id > 0) {
                        setButtonProcessing($btn, $t('Drafting...'));
                        moveToDraft([id], {
                            button: $btn,
                            rowLevel: true,
                            processingMessage: $t('Row move to draft in progress...'),
                            doneText: $t('Drafted')
                        });
                    }
                });
            }

            if (showQueueAction && config.moveToQueueUrl) {
                $('.artm-queue-row').off('click').on('click', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var id = parseInt($btn.data('id'), 10) || 0;
                    if (id > 0) {
                        setButtonProcessing($btn, $t('Queueing...'));
                        moveToQueue([id], {
                            button: $btn,
                            processingMessage: $t('Row queue action in progress...'),
                            doneText: $t('Queued')
                        });
                    }
                });
            }

            if (showAddAction && config.addProductUrl) {
                $('.artm-add-product-row').off('click').on('click', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var id = parseInt($btn.data('id'), 10) || 0;
                    if (id > 0) {
                        setButtonProcessing($btn, $t('Adding...'));
                        addProducts([id], {
                            button: $btn,
                            processingMessage: $t('Product add processing...'),
                            doneText: $t('Added')
                        });
                    }
                });
            }

            if (showViewDetailsAction) {
                $('.artm-view-details-row').off('click').on('click', function (e) {
                    e.preventDefault();
                    openRowDetailModal(parseInt($(this).data('id'), 10) || 0);
                });
            }

            if (showUploadImageAction && config.uploadProductImageUrl) {
                if (!$('#artm-success-image-file-input').length) {
                    $('body').append('<input type="file" id="artm-success-image-file-input" accept="image/jpeg,image/png" style="display:none;" />');
                    $('#artm-success-image-file-input').off('change').on('change', function () {
                        var file = this.files && this.files[0] ? this.files[0] : null;
                        var productId = activeUploadProductId;
                        var draftId = activeUploadDraftId;
                        var rowId = activeUploadRowId;
                        if (!file || draftId <= 0) {
                            return;
                        }

                        var formData = new FormData();
                        formData.append('form_key', window.FORM_KEY || '');
                        formData.append('product_id', productId);
                        formData.append('draft_id', draftId || rowId);
                        formData.append('sku', activeUploadSku || '');
                        formData.append('image', file);

                        $.ajax({
                            url: config.uploadProductImageUrl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        }).done(function (resp) {
                            if (!resp || !resp.success) {
                                showMessage('error', resp && resp.message ? resp.message : $t('Unable to upload image.'));
                                return;
                            }

                            showMessage('success', resp.message || $t('Image uploaded successfully.'));

                            var item = rowDetailsMap[String(rowId)] || null;
                            if (item && item.row) {
                                item.row.media_items = item.row.media_items || [];
                                item.row.media_items.unshift({
                                    url: resp.url || '',
                                    file_path: resp.file_path || '',
                                    roles: resp.roles || []
                                });
                            }
                            loadMatrix();
                        }).fail(function (xhr) {
                            showMessage('error', normalizeAjaxError(xhr, $t('Unable to upload image.')));
                        }).always(function () {
                            $('#artm-success-image-file-input').val('');
                        });
                    });
                }

                $('.artm-upload-image-row').off('click').on('click', function (e) {
                    e.preventDefault();
                    activeUploadRowId = parseInt($(this).data('id'), 10) || 0;
                    activeUploadDraftId = parseInt($(this).data('draft-id'), 10) || activeUploadRowId;
                    activeUploadProductId = parseInt($(this).data('product-id'), 10) || 0;
                    activeUploadSku = String($(this).data('sku') || '');
                    if (activeUploadDraftId <= 0) {
                        showMessage('error', $t('Missing draft id for image upload.'));
                        return;
                    }
                    $('#artm-success-image-file-input').trigger('click');
                });
            }

            if (showApplyImagesAction && config.addImagesToProductUrl) {
                $('.artm-apply-images-row').off('click').on('click', function (e) {
                    e.preventDefault();
                    var draftId = parseInt($(this).data('draft-id'), 10) || 0;
                    var productId = parseInt($(this).data('product-id'), 10) || 0;
                    var sku = String($(this).data('sku') || '');
                    if (draftId <= 0 || (productId <= 0 && !sku)) {
                        showMessage('error', $t('Missing draft/sku for image apply.'));
                        return;
                    }

                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: config.addImagesToProductUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            form_key: window.FORM_KEY || '',
                            draft_id: draftId,
                            product_id: productId,
                            sku: sku,
                            debug: 1
                        }
                    }).done(function (resp) {
                        console.log('[ArtM Debug] addImagesToProduct request:', {
                            url: config.addImagesToProductUrl,
                            draft_id: draftId,
                            product_id: productId,
                            sku: sku,
                            debug: 1
                        });
                        console.log('[ArtM Debug] addImagesToProduct response:', resp);
                        if (!resp || !resp.success) {
                            showMessage('error', resp && resp.message ? resp.message : $t('Unable to add images to product.'));
                            return;
                        }
                        showMessage('success', resp.message || $t('Images added to product.'));
                        loadMatrix();
                    }).fail(function (xhr) {
                        console.log('[ArtM Debug] addImagesToProduct fail responseText:', xhr && xhr.responseText ? xhr.responseText : '');
                        showMessage('error', normalizeAjaxError(xhr, $t('Unable to add images to product.')));
                    }).always(function () {
                        $btn.prop('disabled', false);
                    });
                });
            }

            $('.artm-expand-table-btn').off('click').on('click', function () {
                var $btn = $(this);
                var $wrapper = $btn.closest('.admin__fieldset-wrapper');
                var expanded = String($btn.attr('data-expanded') || '0') === '1';
                if (expanded) {
                    $btn.attr('data-expanded', '0').text($t('Expand'));
                    $wrapper.removeClass('artm-table-fullscreen');
                } else {
                    $btn.attr('data-expanded', '1').text($t('Exit Fullscreen'));
                    $wrapper.addClass('artm-table-fullscreen');
                }
            });

            $('.artm-toggle-table-visibility-btn').off('click').on('click', function () {
                var $btn = $(this);
                var $wrapper = $btn.closest('.admin__fieldset-wrapper');
                var collapsed = String($btn.attr('data-collapsed') || '0') === '1';
                var $content = $wrapper.children('.admin__fieldset-wrapper-content').first();

                if (collapsed) {
                    $content.show();
                    $btn.attr('data-collapsed', '0').attr('title', $t('Collapse table')).html('&#9650;');
                } else {
                    $content.hide();
                    $btn.attr('data-collapsed', '1').attr('title', $t('Expand table')).html('&#9660;');
                }
            });
        }

        function loadMatrix() {
            $.ajax({
                url: config.matrixUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to load ready products.'));
                    return;
                }

                var html = [];
                rowDetailsMap = {};
                var groups = normalizeGroups(resp);
                $.each(groups, function (_, group) {
                    html.push(renderGroup(group));
                });

                if (!html.length) {
                    html.push('<div class="message message-notice notice"><div>' + $t('No products found.') + '</div></div>');
                }

                var groupsSelector = config.groupsSelector || '#artm-ready-groups';
                var msgSelector = config.msgSelector || '#artm-ready-msg';
                $(groupsSelector).html(html.join(''));
                $(msgSelector).hide();
                clearDebugPanel();
                if ($('#artm-ready-select-all').length) {
                    $('#artm-ready-select-all').prop('checked', false);
                }
                ensureSharedStyles();
                bindActions();
                applyPagination();
            }).fail(function (xhr) {
                showMessage('error', normalizeAjaxError(xhr, $t('Unable to load ready products.')));
            });
        }

        loadMatrix();
    };
});
