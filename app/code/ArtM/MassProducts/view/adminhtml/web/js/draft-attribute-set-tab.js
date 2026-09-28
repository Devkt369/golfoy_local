define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (config) {
        var $root = $(config.rootSelector || '#artm-draft-products-tab-root');
        if (!$root.length || $root.data('inited')) {
            return;
        }
        $root.data('inited', true);

        var categoryTreeData = null;
        var categoryModalInit = false;
        var activeCategoryInput = null;
        var selectedCategoryMap = {};

        var activeMediaDraftId = 0;
        var mediaDebugFromConfig = !!config.mediaDebug;
        var mediaDebugFromUrl = /(?:\?|&)debug_media=1(?:&|$)/.test(window.location.search || '');
        var mediaDebug = mediaDebugFromConfig || mediaDebugFromUrl;

        window.console && window.console.log('[DRAFT TAB] mediaDebug check:', {
            configMediaDebug: config.mediaDebug,
            mediaDebugFromConfig: mediaDebugFromConfig,
            locationSearch: window.location.search,
            mediaDebugFromUrl: mediaDebugFromUrl,
            finalMediaDebug: mediaDebug
        });

        function mediaDebugLog() {
            if (!mediaDebug || !window.console || !window.console.log) {
                return;
            }
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[MassProducts Media Debug]');
            window.console.log.apply(window.console, args);
        }

        if (mediaDebug) {
            window.console && window.console.log('[DRAFT TAB] mediaDebug is TRUE, creating badge...');
            if (!$('#artm-media-debug-badge').length) {
                $('body').append(
                    '<div id="artm-media-debug-badge" style="position:fixed;top:12px;right:12px;z-index:100000;padding:8px 10px;background:#7f1d1d;color:#fff;border-radius:4px;font-size:12px;font-weight:600;">MEDIA DEBUG ON</div>'
                );
                window.console && window.console.log('[DRAFT TAB] Badge appended to body');
            }
            mediaDebugLog('Debug mode enabled', {
                location: window.location.href,
                rootFound: !!$root.length
            });
        } else {
            window.console && window.console.log('[DRAFT TAB] mediaDebug is FALSE - no badge created');
        }

        function escapeHtml(val) {
            return $('<div/>').text(val || '').html();
        }

        function operationBadge(value) {
            var v = String(value || 'create').toLowerCase();
            var bg = v === 'update' ? '#2980b9' : '#2f7d32';
            return '<span style="display:inline-block;padding:2px 8px;border-radius:12px;color:#fff;background:' + bg + ';">' + escapeHtml(v) + '</span>';
        }

        function showMessage(type, text) {
            var cls = type === 'success' ? 'message message-success success' : 'message message-error error';
            $('#artm-draft-msg').attr('class', cls).html('<div>' + escapeHtml(text) + '</div>').show();
        }

        function isCategoryAttribute(code) {
            var c = String(code || '').toLowerCase();
            return c === 'category_ids' || c === 'categories' || c === 'category';
        }

        function parseCategoryIds(val) {
            var map = {};
            var raw = String(val || '');
            if (!raw) {
                return map;
            }
            $.each(raw.split(','), function (_, item) {
                var id = parseInt(String(item || '').trim(), 10);
                if (id > 0) {
                    map[id] = true;
                }
            });
            return map;
        }

        function buildCategoryTreeHtml(nodes, depth) {
            depth = depth || 1;
            var html = ['<ul class="artm-cat-tree">'];
            $.each(nodes || [], function (_, node) {
                if (!node || !node.id) {
                    return;
                }
                var id = parseInt(node.id, 10);
                var hasChildren = !!(node.children && node.children.length);
                var expanded = hasChildren && depth <= 4;
                var checked = selectedCategoryMap[id] ? ' checked' : '';

                html.push('<li data-id="' + id + '">');
                html.push('<div class="artm-cat-row">');
                html.push('<span class="artm-cat-toggle' + (hasChildren ? '' : ' is-empty') + '" data-expanded="' + (expanded ? '1' : '0') + '">' + (hasChildren ? (expanded ? '-' : '+') : '+') + '</span>');
                html.push('<input type="checkbox" class="artm-cat-check" value="' + id + '"' + checked + ' />');
                html.push('<span>' + escapeHtml((node.name || '') + ' (' + id + ')') + '</span>');
                html.push('</div>');

                if (hasChildren) {
                    html.push('<div class="artm-cat-children" style="display:' + (expanded ? 'block' : 'none') + ';">');
                    html.push(buildCategoryTreeHtml(node.children, depth + 1));
                    html.push('</div>');
                }

                html.push('</li>');
            });
            html.push('</ul>');
            return html.join('');
        }

        function ensureCategoryModal() {
            if (categoryModalInit) {
                return;
            }

            $('body').append(
                '<div id="artm-category-picker-overlay"></div>' +
                '<div id="artm-category-picker-modal">' +
                '  <div class="artm-cat-head"><span>' + $t('Select Categories') + '</span><button type="button" class="action-secondary" id="artm-cat-close">' + $t('Close') + '</button></div>' +
                '  <div class="artm-cat-body" id="artm-cat-tree-holder"><div>' + $t('Loading...') + '</div></div>' +
                '  <div class="artm-cat-foot">' +
                '    <button type="button" class="action-secondary" id="artm-cat-cancel">' + $t('Cancel') + '</button>' +
                '    <button type="button" class="action-primary" id="artm-cat-apply">' + $t('Apply') + '</button>' +
                '  </div>' +
                '</div>'
            );

            function closeModal() {
                $('#artm-category-picker-overlay').hide();
                $('#artm-category-picker-modal').hide();
            }

            $('#artm-cat-close, #artm-cat-cancel, #artm-category-picker-overlay').off('click').on('click', function () {
                closeModal();
            });

            $('#artm-cat-apply').off('click').on('click', function () {
                if (!activeCategoryInput || !activeCategoryInput.length) {
                    closeModal();
                    return;
                }
                var ids = [];
                $.each(selectedCategoryMap, function (id, selected) {
                    if (selected) {
                        ids.push(parseInt(id, 10));
                    }
                });
                ids = ids.filter(function (v) { return v > 0; }).sort(function (a, b) { return a - b; });
                activeCategoryInput.val(ids.join(','));
                closeModal();
            });

            $('#artm-cat-tree-holder').off('click', '.artm-cat-toggle').on('click', '.artm-cat-toggle', function () {
                var $toggle = $(this);
                if ($toggle.hasClass('is-empty')) {
                    return;
                }
                var expanded = String($toggle.attr('data-expanded') || '0') === '1';
                var $children = $toggle.closest('li').children('.artm-cat-children');
                if (expanded) {
                    $toggle.attr('data-expanded', '0').text('+');
                    $children.hide();
                } else {
                    $toggle.attr('data-expanded', '1').text('-');
                    $children.show();
                }
            });

            $('#artm-cat-tree-holder').off('change', '.artm-cat-check').on('change', '.artm-cat-check', function () {
                var id = parseInt($(this).val(), 10);
                if (id <= 0) {
                    return;
                }
                if ($(this).is(':checked')) {
                    selectedCategoryMap[id] = true;
                } else {
                    delete selectedCategoryMap[id];
                }
            });

            categoryModalInit = true;
        }

        function openCategoryModal($input) {
            activeCategoryInput = $input;
            selectedCategoryMap = parseCategoryIds($input.val());
            ensureCategoryModal();

            var render = function () {
                $('#artm-cat-tree-holder').html(buildCategoryTreeHtml(categoryTreeData || [], 1));
                $('#artm-category-picker-overlay').show();
                $('#artm-category-picker-modal').show();
            };

            if (Array.isArray(categoryTreeData)) {
                render();
                return;
            }

            $('#artm-cat-tree-holder').html('<div>' + $t('Loading...') + '</div>');
            $('#artm-category-picker-overlay').show();
            $('#artm-category-picker-modal').show();

            $.ajax({
                url: config.categoryTreeUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    $('#artm-cat-tree-holder').html('<div>' + escapeHtml(resp && resp.message ? resp.message : $t('Unable to load categories.')) + '</div>');
                    return;
                }
                categoryTreeData = resp.items || [];
                render();
            }).fail(function () {
                $('#artm-cat-tree-holder').html('<div>' + $t('Unable to load categories.') + '</div>');
            });
        }

        function fieldHtml(attr, value, draftId) {
            var code = attr.attribute_code;
            var input = String(attr.frontend_input || 'text');
            var val = value == null ? '' : String(value);
            var name = 'val_' + draftId + '_' + code;

            if (isCategoryAttribute(code)) {
                return '<div class="artm-category-field" style="display:flex;align-items:center;gap:8px;min-width:280px;">'
                    + '<input type="text" class="admin__control-text artm-attr-input artm-category-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(val) + '" />'
                    + '<a href="#" class="artm-category-picker-link">' + $t('Select Categories') + '</a>'
                    + '</div>';
            }

            if (input === 'select' || input === 'boolean') {
                var opts = ['<option value="">' + $t('-- Select --') + '</option>'];
                $.each(attr.options || [], function (_, opt) {
                    var ov = String(opt.value || '');
                    var sel = (val === ov) ? ' selected="selected"' : '';
                    opts.push('<option value="' + escapeHtml(ov) + '"' + sel + '>' + escapeHtml(opt.label || ov) + '</option>');
                });
                return '<select class="admin__control-select artm-attr-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '">' + opts.join('') + '</select>';
            }

            if (input === 'multiselect') {
                return '<input type="text" class="admin__control-text artm-attr-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(val) + '" placeholder="' + escapeHtml($t('Comma separated values')) + '"/>';
            }

            if (input === 'date') {
                return '<input type="date" class="admin__control-text artm-attr-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(val) + '"/>';
            }

            if (input === 'price' || input === 'weight') {
                return '<input type="number" step="0.0001" class="admin__control-text artm-attr-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(val) + '"/>';
            }

            if (input === 'textarea') {
                return '<textarea class="admin__control-textarea artm-attr-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '">' + escapeHtml(val) + '</textarea>';
            }

            return '<input type="text" class="admin__control-text artm-attr-input" data-code="' + escapeHtml(code) + '" name="' + escapeHtml(name) + '" value="' + escapeHtml(val) + '"/>';
        }

        function msiEditorHtml(row, sources) {
            var sourceOptions = ['<option value="">' + $t('-- Select Source --') + '</option>'];
            $.each(sources || [], function (_, src) {
                var sourceCode = String(src.source_code || '');
                if (!sourceCode) {
                    return;
                }
                var sourceName = String(src.name || sourceCode);
                sourceOptions.push('<option value="' + escapeHtml(sourceCode) + '">' + escapeHtml(sourceName + ' (' + sourceCode + ')') + '</option>');
            });

            var entries = '';
            var msiValues = row.msi_values || {};
            $.each(msiValues, function (sourceCode, qty) {
                var label = String(sourceCode || '');
                $.each(sources || [], function (_, src) {
                    if (String(src.source_code || '') === label) {
                        label = String(src.name || label) + ' (' + label + ')';
                        return false;
                    }
                    return true;
                });

                entries += '<div class="artm-msi-item" data-source="' + escapeHtml(sourceCode) + '" style="display:flex;gap:8px;align-items:center;margin:4px 0;">'
                    + '<span style="min-width:160px;">' + escapeHtml(label) + '</span>'
                    + '<input type="number" step="0.0001" class="admin__control-text artm-msi-qty" value="' + escapeHtml(String(qty || '')) + '" style="max-width:120px;"/>'
                    + '<button type="button" class="action-secondary artm-msi-remove"><span>' + $t('Delete') + '</span></button>'
                    + '</div>';
            });

            return '<div class="artm-msi-wrap">'
                + '<div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">'
                + '<select class="admin__control-select artm-msi-source-select" style="min-width:220px;">' + sourceOptions.join('') + '</select>'
                + '<input type="number" step="0.0001" class="admin__control-text artm-msi-add-qty" placeholder="' + escapeHtml($t('Qty')) + '" style="max-width:110px;"/>'
                + '<button type="button" class="action-secondary artm-msi-add"><span>' + $t('Add') + '</span></button>'
                + '</div>'
                + '<div class="artm-msi-items">' + entries + '</div>'
                + '</div>';
        }

        function extractFileName(path) {
            var parts = String(path || '').split('/');
            return parts.length ? parts[parts.length - 1] : '';
        }

        function mediaItemHtml(item) {
            var mediaId = parseInt(item && item.media_id, 10) || 0;
            var url = String(item && item.url || '');
            var fileName = extractFileName(item && item.file_path || '') || $t('Image');
            var roles = item && item.roles ? item.roles : [];
            var rolesMap = {};
            $.each(roles || [], function (_, r) { rolesMap[r] = true; });

            var roleButtons = ['base', 'small', 'thumbnail'].map(function (role) {
                var labels  = { base: $t('Base'), small: $t('Small'), thumbnail: $t('Thumb') };
                var titles  = { base: $t('Base Image'), small: $t('Small Image'), thumbnail: $t('Thumbnail') };
                var active  = rolesMap[role] ? ' artm-role-active' : '';
                return '<button type="button" class="artm-role-btn' + active + '" data-media-id="' + mediaId + '" data-role="' + role + '" title="' + escapeHtml(titles[role]) + '">' + labels[role] + '</button>';
            }).join('');

            return '<div class="artm-media-item" data-media-id="' + mediaId + '">'
                + '<div class="artm-mi-img">'
                + '<img src="' + escapeHtml(url) + '" />'
                + '<button type="button" class="artm-mi-del" data-media-id="' + mediaId + '" title="' + $t('Delete') + '">&#x2715;</button>'
                + '</div>'
                + '<div class="artm-mi-name" title="' + escapeHtml(fileName) + '">' + escapeHtml(fileName) + '</div>'
                + '<div class="artm-mi-roles">' + roleButtons + '</div>'
                + '</div>';
        }

        function mediaGalleryHtml(row) {
            var draftId = parseInt(row.entity_id, 10);
            var mediaItems = row.media_items || [];
            var items = '';
            $.each(mediaItems, function (_, item) {
                items += mediaItemHtml(item);
            });
            mediaDebugLog('mediaGalleryHtml()', {
                draftId: draftId,
                mediaItemsCount: mediaItems.length,
                hasMediaItemsArray: Array.isArray(mediaItems)
            });
            items += '<div class="artm-media-item artm-media-upload-item">'
                + '<button type="button" class="artm-media-card-upload" data-draft-id="' + draftId + '">'
                + '<span class="artm-media-card-upload-plus">+</span>'
                + '<span>' + $t('Upload') + '</span>'
                + '</button>'
                + '</div>';

            return '<div class="artm-media-gallery" data-draft-id="' + draftId + '">'
                + (mediaDebug
                    ? '<div style="margin-bottom:6px;padding:4px 6px;border:1px dashed #c0392b;background:#fff5f5;color:#7f1d1d;font-size:11px;">'
                        + 'DEBUG: draft_id=' + draftId + ', media_items=' + mediaItems.length
                        + '</div>'
                    : '')
                + '<div class="artm-media-head">'
                + '<div>'
                + '<div class="artm-media-title">' + $t('Quick Media') + '</div>'
                + '<div class="artm-media-subtitle">' + $t('Upload original images to temporary storage') + '</div>'
                + '</div>'
                + '<div class="artm-media-head-right">'
                + '<span class="artm-media-count">' + mediaItems.length + ' ' + $t('images') + '</span>'
                + '<button type="button" class="action-secondary artm-media-upload-btn" data-draft-id="' + draftId + '"><span>' + $t('Upload Images') + '</span></button>'
                + '</div>'
                + '</div>'
                + '<div class="artm-media-items-wrap">'
                + '<div class="artm-media-items">' + items + '</div>'
                + '<div class="artm-media-empty' + (mediaItems.length ? ' is-hidden' : '') + '">'
                + '<button type="button" class="artm-media-empty-action" data-draft-id="' + draftId + '">'
                + '<span class="artm-media-empty-plus">+</span>'
                + '<span>' + $t('No images yet. Click to upload.') + '</span>'
                + '</button>'
                + '</div>'
                + '</div>'
                + '<div class="artm-media-help">' + $t('Pick one image each for Base, Small, and Thumbnail. Multiple uploads supported.') + '</div>'
                + '</div>';
        }

        function syncMediaGallery($gallery) {
            if (!$gallery || !$gallery.length) {
                return;
            }

            var count = $gallery.find('.artm-media-item').length;
            $gallery.find('.artm-media-count').text(count + ' ' + $t('images'));
            $gallery.find('.artm-media-empty').toggleClass('is-hidden', count > 0);
        }

        function renderGroup(group) {
            var html = [];
            var attrs = group.attributes || [];
            var rows = group.rows || [];
            var hasNameAttribute = false;
            $.each(attrs, function (_, attr) {
                var code = String((attr && attr.attribute_code) || '').toLowerCase();
                if (code === 'name' || code === 'product_name') {
                    hasNameAttribute = true;
                    return false;
                }
                return true;
            });

            var fixedColumns = 3; // SKU, Operation, Status
            if (!hasNameAttribute) {
                fixedColumns += 1; // Product Name
            }
            var queueBtn = '';
            if (group.queueable) {
                queueBtn = '<button type="button" class="action-primary artm-move-set" data-ids="' + escapeHtml(rows.map(function (r) { return r.entity_id; }).join(',')) + '"><span>' + $t('Move Attribute Set To Queue') + '</span></button>';
            }

            html.push('<div class="admin__fieldset-wrapper" style="margin-bottom:16px;">');
            html.push('<div class="admin__fieldset-wrapper-title" style="display:flex;justify-content:space-between;align-items:center;">');
            html.push('<strong>' + escapeHtml(group.attribute_set_name || 'Unknown Attribute Set') + '</strong>');
            html.push(queueBtn || '<span style="color:#c0392b;">' + $t('Fill all required attributes to enable queue') + '</span>');
            html.push('</div>');
            html.push('<div class="admin__fieldset-wrapper-content artm-draft-table-wrap" style="overflow:auto;">');
            html.push('<table class="data-grid artm-draft-table" style="min-width:1200px;">');

            html.push('<thead><tr>');
            html.push('<th class="artm-col-sku">' + $t('SKU') + '</th>');
            if (!hasNameAttribute) {
                html.push('<th>' + $t('Product Name') + '</th>');
            }
            html.push('<th>' + $t('Operation') + '</th>');
            html.push('<th>' + $t('Status') + '</th>');
            $.each(attrs, function (_, attr) {
                var label = attr.frontend_label || attr.attribute_code;
                if (attr.is_required) {
                    label += ' *';
                }
                html.push('<th>' + escapeHtml(label) + '</th>');
            });
            html.push('<th>' + $t('Source Qty') + '</th>');
            html.push('<th>' + $t('Images') + '</th>');
            html.push('<th class="artm-col-actions">' + $t('Actions') + '</th>');
            html.push('</tr></thead>');

            html.push('<tbody>');
            $.each(rows, function (_, row) {
                html.push('<tr data-id="' + parseInt(row.entity_id, 10) + '">');
                html.push('<td class="artm-col-sku">' + escapeHtml(row.sku || '') + '</td>');
                if (!hasNameAttribute) {
                    html.push('<td>' + escapeHtml(row.product_name || '') + '</td>');
                }
                html.push('<td>' + operationBadge(row.operation_type || 'create') + '</td>');
                html.push('<td>' + escapeHtml(row.status || 'incomplete') + '</td>');
                $.each(attrs, function (_, attr) {
                    var code = attr.attribute_code;
                    var value = row.values && Object.prototype.hasOwnProperty.call(row.values, code) ? row.values[code] : '';
                    html.push('<td>' + fieldHtml(attr, value, row.entity_id) + '</td>');
                });
                html.push('<td>' + msiEditorHtml(row, group.msi_sources || []) + '</td>');
                html.push('<td class="artm-media-cell">' + mediaGalleryHtml(row) + '</td>');
                html.push('<td class="artm-col-actions">'
                    + '<div style="display:flex;gap:6px;flex-wrap:wrap;">'
                    + '<button type="button" class="action-secondary artm-upload-row-images" data-draft-id="' + parseInt(row.entity_id, 10) + '"><span>' + $t('Upload Image') + '</span></button>'
                    + '<button type="button" class="action-secondary artm-save-row"><span>' + $t('Save') + '</span></button>'
                    + '<button type="button" class="action-delete artm-delete-row"><span>' + $t('Delete') + '</span></button>'
                    + '</div>'
                    + '</td>');
                html.push('</tr>');
            });
            if (!rows.length) {
                html.push('<tr><td colspan="' + (attrs.length + fixedColumns + 3) + '" style="text-align:center;">' + $t('No products in this attribute set.') + '</td></tr>');
            }
            html.push('</tbody></table></div></div>');

            return html.join('');
        }

        function bindActions() {
            // ---- Media Gallery ----
            // Ensure shared file input exists
            if (!$('#artm-media-file-input').length) {
                $('body').append('<input type="file" id="artm-media-file-input" accept="image/jpeg,image/png" multiple style="display:none;" />');
                $('#artm-media-file-input').on('change', function () {
                    var files = this.files;
                    var draftId = activeMediaDraftId;
                    mediaDebugLog('file input changed', {
                        draftId: draftId,
                        filesCount: files && files.length ? files.length : 0
                    });
                    if (!files || !files.length || draftId <= 0) {
                        return;
                    }
                    $.each(files, function (_, file) {
                        var formData = new FormData();
                        formData.append('image', file);
                        formData.append('draft_id', draftId);
                        formData.append('form_key', window.FORM_KEY || '');

                        $.ajax({
                            url: config.mediaUploadUrl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        }).done(function (resp) {
                            mediaDebugLog('upload response', resp);
                            if (!resp || !resp.success) {
                                showMessage('error', resp && resp.message ? resp.message : $t('Image upload failed.'));
                                return;
                            }
                            var $gallery = $('.artm-media-gallery[data-draft-id="' + draftId + '"]');
                            $gallery.find('.artm-media-items').append(mediaItemHtml(resp));
                            syncMediaGallery($gallery);
                            bindImageActions();
                        }).fail(function () {
                            showMessage('error', $t('Image upload failed.'));
                        });
                    });
                    // Reset so same file can be selected again
                    $(this).val('');
                });
            }

            function bindImageActions() {
                // Add image button
                $('.artm-media-upload-btn, .artm-media-empty-action, .artm-media-card-upload, .artm-upload-row-images').off('click').on('click', function () {
                    activeMediaDraftId = parseInt($(this).data('draft-id'), 10) || 0;
                    mediaDebugLog('upload trigger clicked', {
                        className: this.className,
                        draftId: activeMediaDraftId
                    });
                    if (activeMediaDraftId > 0) {
                        $('#artm-media-file-input').trigger('click');
                    }
                });

                // Delete image
                $('.artm-mi-del').off('click').on('click', function () {
                    var $btn    = $(this);
                    var mediaId = parseInt($btn.data('media-id'), 10) || 0;
                    if (!mediaId || !window.confirm($t('Delete this image?'))) {
                        return;
                    }
                    $.ajax({
                        url: config.mediaDeleteUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: { form_key: window.FORM_KEY || '', media_id: mediaId }
                    }).done(function (resp) {
                        mediaDebugLog('delete response', resp);
                        if (!resp || !resp.success) {
                            showMessage('error', resp && resp.message ? resp.message : $t('Failed to delete image.'));
                            return;
                        }
                        var $gallery = $btn.closest('.artm-media-gallery');
                        $btn.closest('.artm-media-item').remove();
                        syncMediaGallery($gallery);
                    }).fail(function () {
                        showMessage('error', $t('Failed to delete image.'));
                    });
                });

                // Toggle role
                $('.artm-role-btn').off('click').on('click', function () {
                    var $btn    = $(this);
                    var mediaId = parseInt($btn.data('media-id'), 10) || 0;
                    var role    = String($btn.data('role') || '');
                    if (!mediaId || !role) {
                        return;
                    }

                    var $item    = $btn.closest('.artm-media-item');
                    var $gallery = $item.closest('.artm-media-gallery');

                    // Collect current roles for this item, toggling clicked role
                    var currentRoles = [];
                    $item.find('.artm-role-btn.artm-role-active').each(function () {
                        currentRoles.push(String($(this).data('role') || ''));
                    });

                    var isActive = $btn.hasClass('artm-role-active');
                    var newRoles;
                    if (isActive) {
                        newRoles = currentRoles.filter(function (r) { return r !== role; });
                    } else {
                        newRoles = currentRoles.concat([role]);
                        // Visually remove role from siblings
                        $gallery.find('.artm-media-item').not($item).each(function () {
                            $(this).find('.artm-role-btn[data-role="' + role + '"]').removeClass('artm-role-active');
                        });
                    }

                    $btn.toggleClass('artm-role-active');

                    $.ajax({
                        url: config.mediaRolesUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: { form_key: window.FORM_KEY || '', media_id: mediaId, roles: newRoles }
                    }).fail(function () {
                        // Revert visual on failure
                        $btn.toggleClass('artm-role-active');
                    });
                });
            }

            bindImageActions();

            // ---- MSI ----
            $('.artm-msi-add').off('click').on('click', function () {
                var $wrap = $(this).closest('.artm-msi-wrap');
                var sourceCode = String($wrap.find('.artm-msi-source-select').val() || '');
                var qty = String($wrap.find('.artm-msi-add-qty').val() || '').trim();

                if (!sourceCode) {
                    showMessage('error', $t('Please select a source.'));
                    return;
                }

                var $items = $wrap.find('.artm-msi-items');
                var $existing = $items.find('.artm-msi-item[data-source="' + sourceCode + '"]');
                if ($existing.length) {
                    $existing.find('.artm-msi-qty').val(qty);
                } else {
                    var sourceLabel = $wrap.find('.artm-msi-source-select option:selected').text() || sourceCode;
                    var row = '<div class="artm-msi-item" data-source="' + escapeHtml(sourceCode) + '" style="display:flex;gap:8px;align-items:center;margin:4px 0;">'
                        + '<span style="min-width:160px;">' + escapeHtml(sourceLabel) + '</span>'
                        + '<input type="number" step="0.0001" class="admin__control-text artm-msi-qty" value="' + escapeHtml(qty) + '" style="max-width:120px;"/>'
                        + '<button type="button" class="action-secondary artm-msi-remove"><span>' + $t('Delete') + '</span></button>'
                        + '</div>';
                    $items.append(row);
                }
            });

            $('.artm-msi-remove').off('click').on('click', function () {
                $(this).closest('.artm-msi-item').remove();
            });

            $('.artm-category-picker-link').off('click').on('click', function (e) {
                e.preventDefault();
                var $input = $(this).closest('.artm-category-field').find('.artm-category-input');
                if (!$input.length) {
                    return;
                }
                openCategoryModal($input);
            });

            $('.artm-save-row').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                var draftId = parseInt($tr.data('id'), 10) || 0;
                var values = {};
                $tr.find('.artm-attr-input').each(function () {
                    var code = String($(this).data('code') || '').trim();
                    if (!code) {
                        return;
                    }
                    values[code] = $(this).val();
                });

                var knownMsiSources = {};
                $tr.find('.artm-msi-source-select option').each(function () {
                    var sourceCode = String($(this).val() || '');
                    if (sourceCode) {
                        knownMsiSources[sourceCode] = true;
                    }
                });

                var usedMsiSources = {};
                $tr.find('.artm-msi-item').each(function () {
                    var sourceCode = String($(this).data('source') || '').trim();
                    if (!sourceCode) {
                        return;
                    }
                    usedMsiSources[sourceCode] = true;
                    values['msi_qty__' + sourceCode] = String($(this).find('.artm-msi-qty').val() || '').trim();
                });

                $.each(knownMsiSources, function (sourceCode) {
                    if (!usedMsiSources[sourceCode]) {
                        values['msi_qty__' + sourceCode] = '';
                    }
                });

                $.ajax({
                    url: config.rowSaveUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        draft_id: draftId,
                        values: values
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to save row.'));
                        return;
                    }
                    showMessage('success', resp.message || $t('Draft row saved successfully.'));
                    loadMatrix();
                }).fail(function () {
                    showMessage('error', $t('Unable to save row.'));
                });
            });

            $('.artm-delete-row').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                var draftId = parseInt($tr.data('id'), 10) || 0;
                if (draftId <= 0) {
                    showMessage('error', $t('Invalid draft row.'));
                    return;
                }

                if (!window.confirm($t('Delete this draft row?'))) {
                    return;
                }

                $.ajax({
                    url: config.deleteUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        ids: [draftId]
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to delete row.'));
                        return;
                    }
                    showMessage('success', resp.message || $t('Draft row deleted.'));
                    loadMatrix();
                }).fail(function () {
                    showMessage('error', $t('Unable to delete row.'));
                });
            });

            $('.artm-move-set').off('click').on('click', function () {
                var ids = String($(this).data('ids') || '')
                    .split(',')
                    .map(function (x) { return parseInt(x, 10) || 0; })
                    .filter(function (x) { return x > 0; });

                if (!ids.length) {
                    showMessage('error', $t('No drafts selected.'));
                    return;
                }

                $.ajax({
                    url: config.moveSetUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        ids: ids
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to move to queue.'));
                        return;
                    }
                    showMessage('success', resp.message || $t('Selected drafts moved to queue.'));
                    loadMatrix();
                }).fail(function () {
                    showMessage('error', $t('Unable to move to queue.'));
                });
            });
        }

        function loadMatrix() {
            $.ajax({
                url: config.matrixUrl,
                type: 'GET',
                dataType: 'json'
            }).done(function (resp) {
                mediaDebugLog('tabMatrix response received', {
                    success: !!(resp && resp.success),
                    groups: resp && resp.groups ? resp.groups.length : 0
                });
                if (!resp || !resp.success) {
                    showMessage('error', resp && resp.message ? resp.message : $t('Unable to load drafts.'));
                    return;
                }

                if (mediaDebug) {
                    $.each(resp.groups || [], function (_, group) {
                        $.each(group.rows || [], function (_, row) {
                            mediaDebugLog('row debug', {
                                rowId: row.entity_id,
                                sku: row.sku,
                                mediaItemsCount: row.media_items && row.media_items.length ? row.media_items.length : 0,
                                hasGalleryAttributeValue: row.values && Object.prototype.hasOwnProperty.call(row.values, 'gallery')
                            });
                        });
                    });
                }

                var html = [];
                $.each(resp.groups || [], function (_, group) {
                    html.push(renderGroup(group));
                });
                if (!html.length) {
                    html.push('<div class="message message-notice notice"><div>' + $t('No draft records found.') + '</div></div>');
                }

                $('#artm-draft-groups').html(html.join(''));
                $('#artm-draft-msg').hide();
                bindActions();
            }).fail(function () {
                showMessage('error', $t('Unable to load drafts.'));
            });
        }

        loadMatrix();
    };
});
