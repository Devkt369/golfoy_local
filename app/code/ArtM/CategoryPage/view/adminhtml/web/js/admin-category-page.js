define(['jquery', 'mage/translate', 'jquery-ui-modules/sortable'], function ($, $t) {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    return function init($root, config) {
        var $sidebar = $root.find('[data-role="sidebar"]');
        var $content = $root.find('[data-role="content"]');
        var $manualModal = $root.find('[data-role="manual-modal"]');
        var mode = String($root.data('mode') || config.mode || 'manage');
        var sidebar = Array.isArray(config.mainCategories) ? config.mainCategories : [];
        var currentId = parseInt(config.initialCategoryId, 10) || 0;
        var currentPayload = null;

        function renderSidebar(activeId) {
            var html = [];
            $.each(sidebar, function (_, item) {
                var isActive = parseInt(item.category_id, 10) === parseInt(activeId, 10);
                html.push('<div class="artm-sidebar-item artm-sortable-category-item' + (isActive ? ' is-active' : '') + '" data-id="' + parseInt(item.category_id, 10) + '" data-entity-id="' + parseInt(item.category_id, 10) + '" data-scope="config">');
                html.push(item.image ? '<img loading="lazy" src="' + escapeHtml(item.image) + '" alt=""/>' : '<div style="width:40px;height:40px;border-radius:10px;background:#e2e8f0;"></div>');
                html.push('<div style="min-width:0;"><div style="font-weight:700;line-height:1.2;">' + escapeHtml(item.label || '') + '</div><div style="font-size:12px;color:#64748b;">' + escapeHtml(String(item.category_id || '')) + '</div></div>');
                html.push('</div>');
            });
            $sidebar.html(html.join(''));
        }

        function renderLoading() {
            $content.html('<div class="artm-note">' + escapeHtml($t('Loading category data...')) + '</div>');
        }

        function imageField(label, name, folder, value) {
            var html = [];
            html.push('<div class="artm-field">');
            html.push('<label>' + escapeHtml(label) + '</label>');
            html.push('<input type="hidden" name="' + name + '" value="' + escapeHtml(value || '') + '"/>');
            html.push('<input type="file" class="artm-upload-input" data-field="' + name + '" data-folder="' + folder + '" accept="image/*"/>');
            html.push('<div class="artm-preview">' + (value ? '<img src="/media/' + escapeHtml(value) + '" alt="" style="max-width:100%;max-height:120px;border-radius:12px;border:1px solid #dbe2ea;margin-top:6px;"/>' : '') + '</div>');
            html.push('</div>');
            return html.join('');
        }

        function renderManage(resp) {
            var html = [];
            var configRow = resp.config || {};
            var groups = resp.groups || [];
            // console.log('Rendering manage with configRow:', configRow, 'and groups:', groups);
            // console.log('Current payload:', resp.label);
            html.push('<div>');
            html.push('<div style="margin-bottom:12px;"><h2>' + escapeHtml($t('Category Configuration')) + '</h2></div>');
            html.push('<div class="artm-field"><label>' + escapeHtml($t('Category Name')) + '</label><input type="text" name="custom_label" value="' + escapeHtml(configRow.custom_label || resp.label) + '"/></div>');
            html.push('<div class="artm-field"><label>' + escapeHtml($t('Sort Order')) + '</label><input type="number" name="sort_order" value="' + parseInt(configRow.sort_order || 0, 10) + '"/></div>');
            html.push('<div class="artm-field"><label>' + escapeHtml($t('Enabled')) + '</label><select name="is_enabled"><option value="1"' + ((parseInt(configRow.is_enabled, 10) === 1) ? ' selected' : '') + '>' + escapeHtml($t('Yes')) + '</option><option value="0"' + ((parseInt(configRow.is_enabled, 10) === 0) ? ' selected' : '') + '>' + escapeHtml($t('No')) + '</option></select></div>');
            html.push('<div class="artm-form-grid" style="margin-top:16px;">');
            html.push(imageField($t('Category Thumbnail'), 'category_image', 'categories', configRow.category_image || ''));
            html.push(imageField($t('Banner Image'), 'banner_image', 'banners', configRow.banner_image || ''));
            html.push('</div>');
            html.push('<div class="artm-actions"><button type="button" class="action-primary artm-save-config">' + escapeHtml($t('Save Category Settings')) + '</button><button type="button" class="action-secondary artm-save-sort">' + escapeHtml($t('Save Sort Order')) + '</button><button type="button" class="action-secondary artm-reset-category">' + escapeHtml($t('Reset To Default')) + '</button></div>');
            html.push('<div style="margin-top:18px;"><h3>' + escapeHtml($t('Grouped Categories')) + '</h3>');

            $.each(groups, function (_, group) {
                var headingId = parseInt(group.heading_category_id || 0, 10);
                var headingEnabled = group && group.is_enabled != null ? parseInt(group.is_enabled, 10) : 1;
                headingEnabled = isNaN(headingEnabled) ? 1 : headingEnabled;
                var headingToggleIcon = headingEnabled === 1 ? '&#128065;' : '&#128683;';
                var headingToggleTitle = headingEnabled === 1 ? $t('Hide group') : $t('Unhide group');

                var headingHtml = '<strong>';
                if (Math.abs(headingId) > 0) {
                    headingHtml += '<button type="button" class="artm-group-visibility-toggle" data-heading-id="' + headingId + '" title="' + escapeHtml(headingToggleTitle) + '" style="margin-right:8px;">' + headingToggleIcon + '</button>';
                    headingHtml += '<button type="button" class="artm-group-edit-toggle" data-heading-id="' + headingId + '" data-heading-label="' + escapeHtml(group.heading || '') + '" data-heading-sort-order="' + parseInt(group.sort_order || 0, 10) + '" title="' + escapeHtml($t('Edit group')) + '" style="margin-right:8px;">&#9998;</button>';
                    headingHtml += '<button type="button" class="artm-group-delete-toggle" data-heading-id="' + headingId + '" data-heading-label="' + escapeHtml(group.heading || '') + '" title="' + escapeHtml($t('Delete group')) + '" style="margin-right:8px;">&#128465;</button>';
                }
                headingHtml += escapeHtml(group.heading || '') + '</strong>';

                html.push('<div class="artm-card artm-sortable-group' + (headingEnabled !== 1 ? ' artm-is-hidden' : '') + '" data-heading-id="' + headingId + '">' + headingHtml);
                html.push('<div class="artm-grid" style="margin-top:10px;">');

                $.each(group.items || [], function (_, item) {
                    var itemScope = item.manual ? 'manual' : 'config';
                    var itemConfig = item.config || {};
                    var manualData = item.manual_data || {};
                    var itemEnabled = parseInt(itemConfig.is_enabled != null ? itemConfig.is_enabled : item.is_enabled, 10);
                    itemEnabled = isNaN(itemEnabled) ? 1 : itemEnabled;
                    var visibilityIcon = itemEnabled === 1 ? '&#128065;' : '&#128683;';
                    var visibilityTitle = itemEnabled === 1 ? $t('Hide card') : $t('Unhide card');
                    var manualAttrs = '';
                    if (item.manual) {
                        manualAttrs += ' data-manual-title="' + escapeHtml(String(manualData.title || item.label || '')) + '"';
                        manualAttrs += ' data-manual-url="' + escapeHtml(String(manualData.url || item.url || '')) + '"';
                        manualAttrs += ' data-manual-sort-order="' + parseInt(manualData.sort_order != null ? manualData.sort_order : item.sort_order || 0, 10) + '"';
                        manualAttrs += ' data-manual-image="' + escapeHtml(String(manualData.image || '')) + '"';
                        manualAttrs += ' data-parent-category-id="' + parseInt(manualData.parent_category_id || currentId || 0, 10) + '"';
                        manualAttrs += ' data-group-category-id="' + parseInt(manualData.group_category_id || group.heading_category_id || 0, 10) + '"';
                    }

                    html.push('<div class="artm-card artm-sortable-item' + (itemEnabled === 1 ? '' : ' artm-is-hidden') + '" style="position:relative;" data-enabled="' + itemEnabled + '" data-entity-id="' + parseInt(item.entity_id || item.category_id || 0, 10) + '" data-category-id="' + parseInt(item.category_id || 0, 10) + '" data-scope="' + itemScope + '"' + manualAttrs + '>');
                    html.push('<button type="button" class="artm-visibility-toggle" title="' + escapeHtml(visibilityTitle) + '">' + visibilityIcon + '</button>');
                    if (item.manual) {
                        html.push('<button type="button" class="artm-card-edit-toggle" title="' + escapeHtml($t('Edit card')) + '" style="position:absolute;top:8px;right:50px;width:34px;height:34px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;cursor:pointer;font-size:16px;line-height:1;display:inline-flex;align-items:center;justify-content:center;">&#9998;</button>');
                    }

                    item.image.includes("https://")
                        ? item.image
                        : `/media/${item.image}`
                    html.push('<div style="font-weight:700;margin-bottom:8px;">' + escapeHtml(item.label || '') + '</div>');
                    html.push('<div class="artm-field">');
                    html.push('<label>' + escapeHtml($t('Card Label')) + '</label>');
                    html.push('<input type="text" class="artm-item-custom-label" value="' + escapeHtml(itemConfig.custom_label || item.label || '') + '"/>');
                    html.push('</div>');
                    html.push('<div class="artm-field" style="margin-top:8px;">');
                    html.push('<label>' + escapeHtml($t('Card Image')) + '</label>');
                    html.push('<input type="hidden" class="artm-item-category-image" value="' + escapeHtml(item.manual ? (manualData.image || '') : (itemConfig.category_image || '')) + '"/>');
                    html.push('<input type="file" class="artm-upload-input" data-field="' + escapeHtml(item.manual ? 'image' : 'category_image') + '" data-folder="' + escapeHtml(item.manual ? 'manual' : 'categories') + '" accept="image/*"/>');
                    html.push('<div class="artm-preview">' + (item.image ? '<img src="' + escapeHtml(item.image) + '" alt="" style="max-width:100%;max-height:120px;border-radius:12px;border:1px solid #dbe2ea;margin-top:6px;"/>' : '') + '</div>');
                    html.push('</div>');
                    html.push('<div style="font-size:12px;color:#64748b;margin-top:8px;word-break:break-word;">' + escapeHtml(item.url || '') + '</div>');
                    // Only show Save Card for config cards, not manual cards
                    if (!item.manual && parseInt(item.category_id || 0, 10) > 0) {
                        html.push('<div class="artm-actions" style="margin-top:10px;"><button type="button" class="action-primary artm-save-item-config">' + escapeHtml($t('Save Card')) + '</button></div>');
                    }
                    html.push('</div>');
                });

                if (Math.abs(parseInt(group.heading_category_id || 0, 10)) > 0) {
                    html.push('<div class="artm-card artm-card-add"><button type="button" class="action-primary artm-open-manual-modal" data-heading-id="' + parseInt(group.heading_category_id || 0, 10) + '" data-heading-label="' + escapeHtml(group.heading || '') + '">' + escapeHtml($t('Add Card')) + '</button></div>');
                }

                html.push('</div></div>');
            });

            html.push('<div class="artm-note" style="margin-top:12px;">');
            html.push('<button type="button" class="action-primary artm-open-add-group-modal" style="margin-top:8px;">' + escapeHtml($t('+ Add Group')) + '</button>');
            html.push('</div>');

            html.push('</div>');
            $content.html(html.join(''));
            $sidebar.sortable({ items: '.artm-sortable-category-item', axis: 'y', placeholder: 'artm-sort-placeholder' });
            $content.find('.artm-grid').sortable({ items: '.artm-sortable-item', placeholder: 'artm-sort-placeholder' });
            $content.find('.artm-sortable-group').sortable({ items: '.artm-sortable-item', cancel: 'strong,.artm-grid' });
        }

        function closeManualModal() {
            $manualModal.removeClass('is-open');
        }

        function openManualModal(headingId, cardData) {
            // console.log("called openManualModal");
            var groupId = parseInt(headingId, 10) || 0;
            var data = cardData || {};
            var isEdit = parseInt(data.entity_id || 0, 10) > 0;
            var parentCategoryId = parseInt(data.parent_category_id || currentId || 0, 10) || currentId || 0;
            $manualModal.find('input[name="manual_entity_id"]').val(String(parseInt(data.entity_id || 0, 10)));
            $manualModal.find('input[name="manual_parent_category_id"]').val(String(parentCategoryId));
            $manualModal.find('input[name="manual_group_category_id"]').val(String(parseInt(data.group_category_id != null ? data.group_category_id : groupId, 10)));
            $manualModal.find('input[name="manual_title"]').val(String(data.title || ''));
            $manualModal.find('input[name="manual_url"]').val(String(data.url || ''));
            $manualModal.find('input[name="manual_sort_order"]').val(String(parseInt(data.sort_order || 0, 10)));
            $manualModal.find('select[name="manual_is_enabled"]').val(String(parseInt(data.is_enabled != null ? data.is_enabled : 1, 10)));
            $manualModal.find('input[name="manual_image"]').val(String(data.image || ''));
            var $preview = $manualModal.find('.artm-preview').empty();
            if (data.image) {
                $preview.html('<img src="/media/' + escapeHtml(String(data.image)) + '" alt="" style="max-width:100%;max-height:120px;border-radius:12px;border:1px solid #dbe2ea;margin-top:6px;"/>' );
            }
            $manualModal.find('[data-role="manual-modal-title"]').text(isEdit ? $t('Edit Manual Card') : $t('Add Manual Card'));
            $manualModal.find('[data-role="manual-modal-subtitle"]').text(isEdit ? $t('Update the card details below.') : $t('This card will be added to the selected group.'));
            $manualModal.find('[data-role="manual-save"]').text(isEdit ? $t('Update Card') : $t('Save Card'));
            $manualModal.addClass('is-open');
        }

        function closeGroupEditModal() {
            var $groupEditModal = $root.find('[data-role="group-edit-modal"]');
            $groupEditModal.removeClass('is-open');
        }

        function openGroupEditModal(headingId, label, sortOrder) {
            var $groupEditModal = $root.find('[data-role="group-edit-modal"]');
            $groupEditModal.find('input[name="group_edit_heading_id"]').val(String(parseInt(headingId, 10) || 0));
            $groupEditModal.find('input[name="group_edit_label"]').val(String(label || ''));
            $groupEditModal.find('input[name="group_edit_sort_order"]').val(String(parseInt(sortOrder, 10) || 0));
            $groupEditModal.addClass('is-open');
        }

        function closeAddGroupModal() {
            var $addGroupModal = $root.find('[data-role="add-group-modal"]');
            $addGroupModal.removeClass('is-open');
        }

        function openAddGroupModal() {
            var $addGroupModal = $root.find('[data-role="add-group-modal"]');
            $addGroupModal.find('input[name="add_group_label"]').val('');
            $addGroupModal.find('input[name="add_group_sort_order"]').val('0');
            $addGroupModal.addClass('is-open');
        }

        function saveGroupEdit() {
            var $groupEditModal = $root.find('[data-role="group-edit-modal"]');
            var headingId = parseInt($groupEditModal.find('input[name="group_edit_heading_id"]').val(), 10) || 0;
            var label = String($groupEditModal.find('input[name="group_edit_label"]').val() || '').trim();
            var sortOrder = parseInt($groupEditModal.find('input[name="group_edit_sort_order"]').val(), 10) || 0;

            if (headingId === 0) {
                alert($t('Invalid group.'));
                return;
            }

            var isManualGroup = headingId < 0;
            var actualGroupId = isManualGroup ? -headingId : headingId;

            if (isManualGroup) {
                $.ajax({
                    url: config.saveManualGroupUrl || config.saveManualUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        data: {
                            entity_id: actualGroupId,
                            category_id: currentId,
                            label: label,
                            sort_order: sortOrder,
                            is_manual_group: 1
                        }
                    }
                }).done(function (resp) {
                    alert(resp && resp.message ? resp.message : $t('Group saved.'));
                    closeGroupEditModal();
                    loadCategory(currentId);
                });
                return;
            }

            $.ajax({
                url: config.saveConfigUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    data: {
                        category_id: actualGroupId,
                        custom_label: label,
                        sort_order: sortOrder
                    }
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Group saved.'));
                closeGroupEditModal();
                loadCategory(currentId);
            });
        }

        function saveAddGroup() {
            var $addGroupModal = $root.find('[data-role="add-group-modal"]');
            var label = String($addGroupModal.find('input[name="add_group_label"]').val() || '').trim();
            var sortOrder = parseInt($addGroupModal.find('input[name="add_group_sort_order"]').val(), 10) || 0;

            if (currentId <= 0 || !label) {
                alert($t('Please enter a group label.'));
                return;
            }

            $.ajax({
                url: config.saveManualGroupUrl || config.saveManualUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    data: {
                        category_id: currentId,
                        label: label,
                        sort_order: sortOrder,
                        is_manual_group: 1
                    }
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Group created.'));
                closeAddGroupModal();
                loadCategory(currentId);
            });
        }

        function uploadImage(file, folder, field, $preview) {
            var formData = new FormData();
            formData.append(field, file);
            formData.append('field', field);
            formData.append('folder', folder);
            formData.append('form_key', window.FORM_KEY || '');
            return $.ajax({
                url: config.uploadImageUrl,
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: false,
                data: formData
            }).done(function (resp) {
                if (resp && resp.success && resp.file && resp.file.path) {
                    $preview.closest('.artm-field').find('input[type="hidden"]').first().val(resp.file.path);
                    $preview.html('<img src="' + escapeHtml(resp.file.url || '') + '" alt="" style="max-width:100%;max-height:120px;border-radius:12px;border:1px solid #dbe2ea;margin-top:6px;"/>');
                } else {
                    alert(resp && resp.message ? resp.message : $t('Upload failed.'));
                }
            });
        }

        function collectFieldData() {
            var data = {};
            $content.find('input[name], select[name], textarea[name]').each(function () {
                var $field = $(this);
                data[$field.attr('name')] = $field.val();
            });
            data.category_id = currentId;
            return data;
        }

        function saveConfig() {
            var data = collectFieldData();
            $.ajax({
                url: config.saveConfigUrl,
                type: 'POST',
                dataType: 'json',
                data: { form_key: window.FORM_KEY || '', data: data }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Saved.'));
                loadCategory(currentId);
            });
        }

        function saveItemConfig($card) {
            var categoryId = parseInt($card.data('category-id'), 10) || 0;
            if (categoryId <= 0) {
                alert($t('Invalid category.'));
                return;
            }

            $.ajax({
                url: config.saveConfigUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    data: {
                        category_id: categoryId,
                        custom_label: String($card.find('.artm-item-custom-label').val() || '').trim(),
                        category_image: String($card.find('.artm-item-category-image').val() || '').trim()
                    }
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Saved.'));
                loadCategory(currentId);
            });
        }

        function toggleGroupVisibility(headingId) {
            var $group = $content.find('[data-heading-id="' + headingId + '"]');
            var currentEnabled = $group.hasClass('artm-is-hidden') ? 0 : 1;
            var nextEnabled = currentEnabled === 1 ? 0 : 1;
            var hid = parseInt(headingId, 10) || 0;
            var isManualGroup = hid < 0;

            if (isManualGroup) {
                $.ajax({
                    url: config.saveManualGroupUrl || config.saveManualUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        data: {
                            entity_id: -hid,
                            category_id: currentId,
                            is_enabled: nextEnabled,
                            is_manual_group: 1
                        }
                    }
                }).done(function (resp) {
                    alert(resp && resp.message ? resp.message : $t('Visibility updated.'));
                    loadCategory(currentId);
                });
                return;
            }

            $.ajax({
                url: config.saveConfigUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    data: {
                        category_id: hid,
                        is_enabled: nextEnabled
                    }
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Visibility updated.'));
                loadCategory(currentId);
            });
        }

        function deleteGroup(headingId, label) {
            var hid = parseInt(headingId, 10) || 0;
            var groupLabel = String(label || '').trim();
            var isManualGroup = hid < 0;
            var confirmMessage = isManualGroup
                ? $t('Are you sure you want to delete this group and its cards?')
                : $t('Are you sure you want to remove this group from the category page?');

            if (hid === 0) {
                alert($t('Invalid group.'));
                return;
            }

            if (groupLabel) {
                confirmMessage += '\n\n' + groupLabel;
            }

            if (!window.confirm(confirmMessage)) {
                return;
            }

            if (isManualGroup) {
                $.ajax({
                    url: config.deleteManualGroupUrl || config.saveManualGroupUrl || config.saveManualUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        data: {
                            entity_id: -hid,
                            category_id: currentId,
                            is_manual_group: 1
                        }
                    }
                }).done(function (resp) {
                    alert(resp && resp.message ? resp.message : $t('Group deleted.'));
                    loadCategory(currentId);
                });
                return;
            }

            $.ajax({
                url: config.saveConfigUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    data: {
                        category_id: hid,
                        is_enabled: 0
                    }
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Group removed from category page.'));
                loadCategory(currentId);
            });
        }

        function toggleCardVisibility($card) {
            var scope = String($card.data('scope') || 'config');
            var currentEnabled = parseInt($card.data('enabled'), 10);
            var nextEnabled = currentEnabled === 1 ? 0 : 1;

            if (scope === 'manual') {
                var manualPayload = {
                    entity_id: parseInt($card.data('entity-id'), 10) || 0,
                    parent_category_id: parseInt($card.data('parent-category-id'), 10) || currentId,
                    group_category_id: parseInt($card.data('group-category-id'), 10) || 0,
                    title: String($card.data('manual-title') || $card.find('.artm-item-custom-label').val() || '').trim(),
                    url: String($card.data('manual-url') || '').trim(),
                    sort_order: parseInt($card.data('manual-sort-order'), 10) || 0,
                    is_enabled: nextEnabled,
                    image: String($card.find('.artm-item-category-image').val() || $card.data('manual-image') || '').trim()
                };

                $.ajax({
                    url: config.saveManualUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { form_key: window.FORM_KEY || '', data: manualPayload }
                }).done(function (resp) {
                    alert(resp && resp.message ? resp.message : $t('Visibility updated.'));
                    loadCategory(currentId);
                });

                return;
            }

            $.ajax({
                url: config.saveConfigUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    data: {
                        category_id: parseInt($card.data('category-id'), 10) || 0,
                        is_enabled: nextEnabled
                    }
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Visibility updated.'));
                loadCategory(currentId);
            });
        }

        function saveManual() {
            var parentId = parseInt($manualModal.find('input[name="manual_parent_category_id"]').val(), 10) || 0;
            if (!parentId && typeof currentId !== 'undefined' && currentId > 0) {
                parentId = currentId;
                $manualModal.find('input[name="manual_parent_category_id"]').val(String(parentId));
            }
            var manualTitle = String($manualModal.find('input[name="manual_title"]').val() || '').trim();
            // console.log('manual_title input value:', manualTitle);
            if (!manualTitle) {
                alert($t('Title is required. Please enter a value.'));
                return;
            }
            var data = {
                entity_id: parseInt($manualModal.find('input[name="manual_entity_id"]').val(), 10) || 0,
                parent_category_id: parentId,
                group_category_id: parseInt($manualModal.find('input[name="manual_group_category_id"]').val(), 10) || 0,
                title: manualTitle,
                url: String($manualModal.find('input[name="manual_url"]').val() || '').trim(),
                sort_order: parseInt($manualModal.find('input[name="manual_sort_order"]').val(), 10) || 0,
                is_enabled: String($manualModal.find('select[name="manual_is_enabled"]').val() || '1'),
                image: String($manualModal.find('input[name="manual_image"]').val() || '').trim()
            };
            if (!data.parent_category_id || data.parent_category_id <= 0) {
                alert($t('Parent category is missing or invalid. Please reload the page and try again.'));
                return;
            }
            // console.log('Saving manual card with parent_category_id:', data.parent_category_id, 'and title:', data.title);
            $.ajax({
                url: config.saveManualUrl,
                type: 'POST',
                dataType: 'json',
                data: { form_key: window.FORM_KEY || '', data: data }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Saved.'));
                closeManualModal();
                loadCategory(currentId);
            });
        }

        function saveSort(scope, items) {
            return $.ajax({
                url: config.saveSortUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    scope: scope,
                    items: items
                }
            });
        }

        function resetCategory() {
            if (!currentId || currentId <= 0) {
                alert($t('Invalid category.'));
                return;
            }

            var confirmMessage = $t('This will reset this category to default values, remove manual cards/groups, and clear hide/unhide/custom edits for its subcategories. Continue?');
            if (!window.confirm(confirmMessage)) {
                return;
            }

            $.ajax({
                url: config.resetCategoryUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY || '',
                    category_id: currentId
                }
            }).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Category reset complete.'));
                loadCategory(currentId);
            });
        }

        function loadCategory(categoryId) {
            currentId = parseInt(categoryId, 10) || 0;
            renderSidebar(currentId);
            renderLoading();
            $.ajax({
                url: config.loadUrl,
                type: 'GET',
                dataType: 'json',
                data: { category_id: currentId, store_id: config.storeId || 0 }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    $content.html('<div class="artm-note">' + escapeHtml(resp && resp.message ? resp.message : $t('Unable to load category.')) + '</div>');
                    return;
                }
                currentPayload = resp;
                renderSidebar(currentId);
                renderManage(resp);
            }).fail(function () {
                $content.html('<div class="artm-note">' + escapeHtml($t('Failed to load category.')) + '</div>');
            });
        }

        $sidebar.off('click.artmCategoryPage').on('click.artmCategoryPage', '.artm-sidebar-item', function (e) {
            e.preventDefault();
            loadCategory($(this).data('id'));
        });

        $content.off('click.artmCategoryPageConfig', '.artm-save-config').on('click.artmCategoryPageConfig', '.artm-save-config', saveConfig);
        $content.off('click.artmCategoryPageReset', '.artm-reset-category').on('click.artmCategoryPageReset', '.artm-reset-category', resetCategory);
        $content.off('click.artmCategoryPageManual', '.artm-save-manual').on('click.artmCategoryPageManual', '.artm-save-manual', saveManual);
        $content.off('click.artmCategoryPageOpenManual', '.artm-open-manual-modal').on('click.artmCategoryPageOpenManual', '.artm-open-manual-modal', function () {
            openManualModal($(this).data('heading-id'));
        });
        $content.off('click.artmCategoryPageOpenAddGroup', '.artm-open-add-group-modal').on('click.artmCategoryPageOpenAddGroup', '.artm-open-add-group-modal', function (e) {
            e.preventDefault();
            openAddGroupModal();
        });
        $content.off('click.artmCategoryPageItemConfig', '.artm-save-item-config').on('click.artmCategoryPageItemConfig', '.artm-save-item-config', function () {
            saveItemConfig($(this).closest('.artm-sortable-item'));
        });
        $content.off('click.artmCategoryPageGroupEdit', '.artm-group-edit-toggle').on('click.artmCategoryPageGroupEdit', '.artm-group-edit-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var headingId = $(this).data('heading-id');
            var label = $(this).data('heading-label');
            var sortOrder = $(this).data('heading-sort-order');
            openGroupEditModal(headingId, label, sortOrder);
        });
        $content.off('click.artmCategoryPageGroupVisibility', '.artm-group-visibility-toggle').on('click.artmCategoryPageGroupVisibility', '.artm-group-visibility-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            toggleGroupVisibility($(this).data('heading-id'));
        });
        $content.off('click.artmCategoryPageGroupDelete', '.artm-group-delete-toggle').on('click.artmCategoryPageGroupDelete', '.artm-group-delete-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            deleteGroup($(this).data('heading-id'), $(this).data('heading-label'));
        });
        $content.off('click.artmCategoryPageVisibility', '.artm-visibility-toggle').on('click.artmCategoryPageVisibility', '.artm-visibility-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            toggleCardVisibility($(this).closest('.artm-sortable-item'));
        });
        $content.off('click.artmCategoryPageCardEdit', '.artm-card-edit-toggle').on('click.artmCategoryPageCardEdit', '.artm-card-edit-toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var $card = $(this).closest('.artm-sortable-item');
            var groupId = parseInt($card.data('group-category-id'), 10) || 0;
            var imageInputVal = String($card.find('.artm-item-category-image').val() || '').trim();
            var customLabel = String($card.find('.artm-item-custom-label').val() || '').trim();
            openManualModal(groupId, {
                entity_id: parseInt($card.data('entity-id'), 10) || 0,
                parent_category_id: parseInt($card.data('parent-category-id'), 10) || currentId,
                group_category_id: groupId,
                title: customLabel || String($card.data('manual-title') || ''),
                url: String($card.data('manual-url') || ''),
                sort_order: parseInt($card.data('manual-sort-order'), 10) || 0,
                is_enabled: parseInt($card.data('enabled'), 10) || 0,
                image: imageInputVal || String($card.data('manual-image') || '')
            });
        });
        $content.off('click.artmCategoryPageSort', '.artm-save-sort').on('click.artmCategoryPageSort', '.artm-save-sort', function () {
            var hasGroupedCards = $content.find('.artm-sortable-group').length > 0;
            if (hasGroupedCards) {
                var configItems = [];
                var manualItems = [];

                $content.find('.artm-sortable-group').each(function () {
                    $(this).find('.artm-sortable-item').each(function (index) {
                        var $item = $(this);
                        var entityId = parseInt($item.data('entity-id'), 10) || 0;
                        var categoryId = parseInt($item.data('category-id'), 10) || 0;
                        if (entityId <= 0) {
                            return;
                        }

                        if (String($item.data('scope') || '') === 'manual') {
                            manualItems.push({ entity_id: entityId, sort_order: index });
                        } else {
                            if (categoryId <= 0) {
                                return;
                            }
                            configItems.push({ category_id: categoryId, sort_order: index });
                        }
                    });
                });

                var requests = [];
                if (configItems.length > 0) {
                    requests.push(saveSort('config', configItems));
                }
                if (manualItems.length > 0) {
                    requests.push(saveSort('manual', manualItems));
                }

                if (requests.length === 0) {
                    alert($t('Nothing to sort.'));
                    return;
                }

                $.when.apply($, requests).done(function () {
                    alert($t('Sorting saved.'));
                    loadCategory(currentId);
                });

                return;
            }

            var items = [];
            var selector = mode === 'manual' ? '.artm-sortable-item[data-scope="manual"]' : '.artm-sidebar-item[data-scope="config"]';
            $(selector).each(function (index) {
                var entityId = parseInt($(this).data('entity-id'), 10) || 0;
                if (entityId > 0) {
                    items.push({ entity_id: entityId, sort_order: index });
                }
            });

            if (items.length === 0) {
                alert($t('Nothing to sort.'));
                return;
            }

            saveSort(mode === 'manual' ? 'manual' : 'config', items).done(function (resp) {
                alert(resp && resp.message ? resp.message : $t('Sorting saved.'));
                loadCategory(currentId);
            });
        });
        $content.off('change.artmCategoryPage').on('change.artmCategoryPage', '.artm-upload-input', function () {
            var file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                return;
            }
            uploadImage(file, $(this).data('folder') || 'categories', $(this).data('field') || 'image', $(this).closest('.artm-field').find('.artm-preview'));
        });
        
        $manualModal.off('change.artmCategoryPage').on('change.artmCategoryPage', '.artm-upload-input', function () {
            var file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                return;
            }
            uploadImage(file, $(this).data('folder') || 'categories', $(this).data('field') || 'image', $(this).closest('.artm-field').find('.artm-preview'));
        });

        
        $manualModal.off('click.artmCategoryPageClose', '[data-role="manual-close"]').on('click.artmCategoryPageClose', '[data-role="manual-close"]', closeManualModal);
        $manualModal.off('click.artmCategoryPageSave', '[data-role="manual-save"]').on('click.artmCategoryPageSave', '[data-role="manual-save"]', saveManual);

        var $groupEditModal = $root.find('[data-role="group-edit-modal"]');
        $groupEditModal.off('click.artmCategoryPageGroupEditClose', '[data-role="group-edit-close"]').on('click.artmCategoryPageGroupEditClose', '[data-role="group-edit-close"]', closeGroupEditModal);
        $groupEditModal.off('click.artmCategoryPageGroupEditSave', '[data-role="group-edit-save"]').on('click.artmCategoryPageGroupEditSave', '[data-role="group-edit-save"]', saveGroupEdit);

        var $addGroupModal = $root.find('[data-role="add-group-modal"]');
        $addGroupModal.off('click.artmCategoryPageAddGroupClose', '[data-role="add-group-close"]').on('click.artmCategoryPageAddGroupClose', '[data-role="add-group-close"]', closeAddGroupModal);
        $addGroupModal.off('click.artmCategoryPageAddGroupSave', '[data-role="add-group-save"]').on('click.artmCategoryPageAddGroupSave', '[data-role="add-group-save"]', saveAddGroup);

        renderSidebar(currentId);
        if (currentId > 0) {
            loadCategory(currentId);
        } else {
            loadCategory(config.initialCategoryId || 0);
        }
    };
});
