define([
    'jquery',
    'jquery-ui-modules/sortable'
], function ($) {
    'use strict';

    var GROUP_COLLAPSE_STORAGE_KEY = 'artm_pg_group_collapsed_v1';

    var state = {
        selectedPage: null,
        mediaPicker: null,   // { path, groupId, selectedPaths[] }
        attributeCatalog: [],
        collapsedGroups: {}
    };

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function applyTemplate(templateId, data) {
        var template = $(templateId).html() || '';
        $.each(data, function (key, value) {
            if (key === 'actions' || /_html$/.test(key)) {
                template = template.replace(new RegExp('{{' + key + '}}', 'g'), value || '');
            } else {
                template = template.replace(new RegExp('{{' + key + '}}', 'g'), escapeHtml(value));
            }
        });
        return template;
    }

    function normalizeCode(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function loadCollapsedGroups() {
        try {
            var raw = window.localStorage.getItem(GROUP_COLLAPSE_STORAGE_KEY);
            var parsed = raw ? JSON.parse(raw) : {};
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function saveCollapsedGroups() {
        try {
            window.localStorage.setItem(GROUP_COLLAPSE_STORAGE_KEY, JSON.stringify(state.collapsedGroups || {}));
        } catch (e) {
            // no-op
        }
    }

    function isGroupCollapsed(groupId) {
        return !!(state.collapsedGroups && state.collapsedGroups[String(groupId)]);
    }

    function setGroupCollapsed(groupId, collapsed) {
        if (!state.collapsedGroups) {
            state.collapsedGroups = {};
        }
        if (collapsed) {
            state.collapsedGroups[String(groupId)] = 1;
        } else {
            delete state.collapsedGroups[String(groupId)];
        }
        saveCollapsedGroups();
    }

    function findCatalogAttributeByInput(inputValue) {
        var term = $.trim(String(inputValue || ''));
        if (!term) {
            return null;
        }

        var normalized = normalizeCode(term);
        var found = null;
        $.each(state.attributeCatalog || [], function (i, attr) {
            var attrCode = String(attr.attribute_code || '');
            var attrLabel = String(attr.label || '');
            if (
                attrCode.toLowerCase() === term.toLowerCase() ||
                attrLabel.toLowerCase() === term.toLowerCase() ||
                attrCode === normalized
            ) {
                found = attr;
                return false;
            }
        });

        return found;
    }

    function parseDateTime(value) {
        if (!value) {
            return null;
        }
        var normalized = String(value).replace(' ', 'T');
        var date = new Date(normalized);
        return isNaN(date.getTime()) ? null : date;
    }

    function getHeroIcon(name) {
        var base = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
        if (name === 'eye') {
            return base + '<path d="M2.5 12s3.8-6 9.5-6 9.5 6 9.5 6-3.8 6-9.5 6-9.5-6-9.5-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
        if (name === 'eye-slash') {
            return base + '<path d="M3 3l18 18"></path><path d="M10.6 6.3A10.8 10.8 0 0 1 12 6c5.7 0 9.5 6 9.5 6a17.5 17.5 0 0 1-3.7 4.1"></path><path d="M6.6 6.6C4.2 8.2 2.5 12 2.5 12s3.8 6 9.5 6c1.4 0 2.7-.3 3.8-.8"></path><path d="M14.1 14.1A3 3 0 0 1 9.9 9.9"></path></svg>';
        }
        if (name === 'pencil') {
            return base + '<path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z"></path><path d="M14.5 5.5l4 4"></path></svg>';
        }
        if (name === 'magnifier') {
            return base + '<circle cx="11" cy="11" r="6"></circle><path d="M20 20l-4.2-4.2"></path></svg>';
        }
        if (name === 'check') {
            return base + '<path d="M5 12.5l4.2 4.2L19 7"></path></svg>';
        }
        if (name === 'calendar') {
            return base + '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4M16 3v4M3 10h18"></path></svg>';
        }
        if (name === 'ban') {
            return base + '<circle cx="12" cy="12" r="9"></circle><path d="M6 18 18 6"></path></svg>';
        }
        return base + '<circle cx="12" cy="12" r="9"></circle></svg>';
    }

    function toDateTimeLocalValue(value) {
        var dt = parseDateTime(value);
        if (!dt) {
            return '';
        }
        var y = dt.getFullYear();
        var m = String(dt.getMonth() + 1).padStart(2, '0');
        var d = String(dt.getDate()).padStart(2, '0');
        var h = String(dt.getHours()).padStart(2, '0');
        var i = String(dt.getMinutes()).padStart(2, '0');
        return y + '-' + m + '-' + d + 'T' + h + ':' + i;
    }

    function normalizeVisibilityMode(mode) {
        mode = String(mode || 'keep').toLowerCase();
        return ['keep', 'show', 'hide'].indexOf(mode) >= 0 ? mode : 'keep';
    }

    function isInVisibilityRange(startAt, endAt) {
        var now = new Date();
        var start = parseDateTime(startAt);
        var end = parseDateTime(endAt);
        var startOk = !start || now >= start;
        var endOk = !end || now <= end;
        return startOk && endOk;
    }

    function getImageBadgeMeta(image) {
        if (parseInt(image.is_hidden || 0, 10) === 1) {
            return {label: 'Hidden', className: 'pg-badge-hidden', icon: ''};
        }

        var mode = normalizeVisibilityMode(image.visibility_mode);
        if (mode === 'keep') {
            return {label: 'Always', className: 'pg-badge-always', icon: ''};
        }

        var inRange = isInVisibilityRange(image.start_at, image.end_at);
        if (inRange) {
            return {label: 'Active Range', className: 'pg-badge-active-range', icon: ''};
        }

        return {label: 'Scheduled', className: 'pg-badge-scheduled', icon: ''};
    }

    function openImageVisibilityForm(image, config) {
        var mode = normalizeVisibilityMode(image.visibility_mode);

        var html = '';
        html += '<div class="pg-modal-inner pg-image-visibility-modal">';
        html += '<h3>Visibility Settings</h3>';
        html += '<div class="pg-image-vis-preview">';
        html += '<img src="' + escapeHtml(image.image_url || '') + '" alt="Visibility Preview">';
        html += '</div>';
        html += '<div class="pg-visibility-options">';
        html += '<label><input type="radio" name="pg_visibility_mode" value="keep"' + (mode === 'keep' ? ' checked' : '') + '> <span class="pg-option-with-icon">' + getHeroIcon('check') + '<span>Always Show</span></span></label>';
        html += '<label><input type="radio" name="pg_visibility_mode" value="show"' + (mode === 'show' ? ' checked' : '') + '> <span class="pg-option-with-icon">' + getHeroIcon('calendar') + '<span>Show Between Dates</span></span></label>';
        html += '<label><input type="radio" name="pg_visibility_mode" value="hide"' + (mode === 'hide' ? ' checked' : '') + '> <span class="pg-option-with-icon">' + getHeroIcon('calendar') + '<span>Hide Between Dates</span></span></label>';
        html += '</div>';
        html += '<div id="pg-visibility-dates" class="pg-visibility-dates"' + ((mode === 'show' || mode === 'hide') ? '' : ' style="display:none"') + '>';
        html += '<label>Start At</label>';
        html += '<input type="datetime-local" id="pg-image-start-at" value="' + escapeHtml(toDateTimeLocalValue(image.start_at)) + '">';
        html += '<label>End At</label>';
        html += '<input type="datetime-local" id="pg-image-end-at" value="' + escapeHtml(toDateTimeLocalValue(image.end_at)) + '">';
        html += '<p class="pg-visibility-note">Empty Start At means from beginning. Empty End At means infinite future.</p>';
        html += '</div>';
        html += '<div class="pg-modal-actions">';
        html += '<button type="button" id="pg-image-visibility-save" class="action-primary">Save</button>';
        html += '<button type="button" id="pg-image-visibility-cancel">Cancel</button>';
        html += '</div>';
        html += '</div>';

        $('#media-modal').html(html).show();

        $('#pg-image-visibility-cancel').off('click').on('click', function () {
            $('#media-modal').hide().empty();
        });

        $('input[name="pg_visibility_mode"]').off('change').on('change', function () {
            var selected = $(this).val();
            $('#pg-visibility-dates').toggle(selected === 'show' || selected === 'hide');
        });

        $('#pg-image-visibility-save').off('click').on('click', function () {
            var selected = String($('input[name="pg_visibility_mode"]:checked').val() || 'keep');
            var payload = {
                image_id: image.image_id,
                visibility_mode: selected,
                is_hidden: parseInt(image.is_hidden || 0, 10),
                start_at: '',
                end_at: '',
                form_key: config.formKey
            };

            if (selected === 'show' || selected === 'hide') {
                payload.start_at = $.trim($('#pg-image-start-at').val() || '');
                payload.end_at = $.trim($('#pg-image-end-at').val() || '');
            }

            $.ajax({
                url: config.groupImageSaveUrl,
                type: 'POST',
                dataType: 'json',
                data: payload
            }).done(function (response) {
                if (!response || !response.success) {
                    alert(response && response.message ? response.message : 'Unable to save visibility.');
                    return;
                }
                $('#media-modal').hide().empty();
                loadGroups(config);
            }).fail(function () {
                alert('Unable to save visibility.');
            });
        });
    }

    function getDefaultPages() {
        return [
            {page_id: 0, title: 'Home Page',     identifier: 'home-page',     sort_order: 10, is_default: true},
            {page_id: 0, title: 'Category Page', identifier: 'category-page', sort_order: 20, is_default: true},
            {page_id: 0, title: 'Brand Page',    identifier: 'brand-page',    sort_order: 30, is_default: true},
            {page_id: 0, title: 'Landing Page',  identifier: 'landing-page',  sort_order: 40, is_default: true}
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Group image grid (shown inside each group card)                     */
    /* ------------------------------------------------------------------ */

    function buildGroupImagesHtml(group) {
        var attrs = group.attributes || [];
        var images = group.images || [];
        var html = '';
        var datalistId = 'pg-attr-list-' + String(group.group_id);

        html += '<div class="pg-group-attrs" data-group-id="' + escapeHtml(group.group_id) + '">';
        html += '<div class="pg-group-attrs-row">';
        html += '<input type="text" class="pg-attr-unified-input" data-group-id="' + escapeHtml(group.group_id) + '" list="' + escapeHtml(datalistId) + '" placeholder="Type or select attribute and click Add">';
        html += '<datalist id="' + escapeHtml(datalistId) + '">';
        $.each(state.attributeCatalog || [], function (i, attr) {
            html += '<option value="' + escapeHtml(attr.label) + '"></option>';
        });
        html += '</datalist>';
        html += '<button type="button" class="pg-group-attr-apply" data-group-id="' + escapeHtml(group.group_id) + '">Add</button>';
        html += '</div>';

        html += '<div class="pg-group-attr-chips" data-group-id="' + escapeHtml(group.group_id) + '">';
        if (attrs.length) {
            $.each(attrs, function (i, attr) {
                html += '<span class="pg-group-attr-chip" data-attribute-id="' + escapeHtml(attr.attribute_id) + '" data-attribute-code="' + escapeHtml(attr.attribute_code) + '" data-label="' + escapeHtml(attr.attribute_label) + '">';
                html += '<span>' + escapeHtml(attr.attribute_label) + '</span>';
                html += '<button type="button" class="pg-group-attr-remove" title="Remove attribute">&#x2715;</button>';
                html += '</span>';
            });
        } else {
            html += '<span class="pg-group-attr-empty">No attributes attached</span>';
        }
        html += '</div>';
        html += '</div>';

        html += '<div class="pg-group-image-grid" data-group-id="' + escapeHtml(group.group_id) + '">';
        if (images.length) {
            $.each(images, function (i, img) {
                var badge = getImageBadgeMeta(img);
                var isHidden = parseInt(img.is_hidden || 0, 10) === 1;
                html += '<div class="pg-group-img-item' + (isHidden ? ' pg-group-img-item-hidden' : '') + '" data-image-id="' + escapeHtml(img.image_id) + '">';
                html += '<img src="' + escapeHtml(img.image_url) + '" class="pg-group-img-thumb" alt="">';
                if (isHidden) {
                    html += '<span class="pg-hidden-indicator" title="Hidden">' + getHeroIcon('eye-slash') + '</span>';
                }
                html += '<span class="pg-img-badge ' + escapeHtml(badge.className) + '">' + escapeHtml(badge.label) + '</span>';
                html += '<div class="pg-group-img-actions">';
                html += '<button type="button" class="pg-group-img-hide-toggle" data-image-id="' + escapeHtml(img.image_id) + '" data-is-hidden="' + (isHidden ? 1 : 0) + '" title="Hide/Unhide">' + (isHidden ? getHeroIcon('eye') : getHeroIcon('eye-slash')) + ' <span>' + (isHidden ? 'Unhide' : 'Hide') + '</span></button>';
                html += '<button type="button" class="pg-group-img-edit" data-image-id="' + escapeHtml(img.image_id) + '" title="Edit Visibility">' + getHeroIcon('pencil') + ' <span>Edit</span></button>';
                html += '<button type="button" class="pg-group-img-preview" data-image-url="' + escapeHtml(img.image_url) + '" title="Preview">' + getHeroIcon('magnifier') + ' <span>Preview</span></button>';
                html += '</div>';
                html += '<button type="button" class="pg-group-img-remove" data-image-id="' + escapeHtml(img.image_id) + '" title="Remove image">&#x2715;</button>';
                html += '<div class="pg-group-image-attrs">';
                if (attrs.length) {
                    $.each(attrs, function (j, attr) {
                        var values = img.attribute_values || {};
                        var value = values[attr.attribute_code] || '';
                        html += '<label>' + escapeHtml(attr.attribute_label) + '</label>';
                        html += '<input type="text" class="pg-image-attr-input" data-image-id="' + escapeHtml(img.image_id) + '" data-attribute-code="' + escapeHtml(attr.attribute_code) + '" value="' + escapeHtml(value) + '">';
                    });
                } else {
                    html += '<span class="pg-group-attr-empty-inline">Attach attributes to enter values.</span>';
                }
                html += '</div>';
                html += '</div>';
            });
        } else {
            html += '<div class="pg-group-img-empty">No images assigned</div>';
        }
        html += '</div>';
        return html;
    }

    function addAttributeChipToGroup(groupId, attribute) {
        var chips = $('.pg-group-attr-chips[data-group-id="' + groupId + '"]');
        chips.find('.pg-group-attr-empty').remove();
        chips.find('.pg-group-attr-chip[data-attribute-code="' + attribute.attribute_code + '"]').remove();

        var chipHtml = '';
        chipHtml += '<span class="pg-group-attr-chip" data-attribute-id="' + escapeHtml(attribute.attribute_id) + '" data-attribute-code="' + escapeHtml(attribute.attribute_code) + '" data-label="' + escapeHtml(attribute.label) + '">';
        chipHtml += '<span>' + escapeHtml(attribute.label) + '</span>';
        chipHtml += '<button type="button" class="pg-group-attr-remove" title="Remove attribute">&#x2715;</button>';
        chipHtml += '</span>';
        chips.append(chipHtml);

        $('.pg-group-image-grid[data-group-id="' + groupId + '"] .pg-group-img-item').each(function () {
            var attrsWrap = $(this).find('.pg-group-image-attrs');
            attrsWrap.find('.pg-group-attr-empty-inline').remove();
            attrsWrap.append('<label>' + escapeHtml(attribute.label) + '</label><input type="text" class="pg-image-attr-input" data-image-id="' + escapeHtml($(this).data('image-id')) + '" data-attribute-code="' + escapeHtml(attribute.attribute_code) + '" value="">');
        });
    }

    function openEmbedPopup(group) {
        if (!state.selectedPage || !state.selectedPage.identifier) {
            alert('Select a page first.');
            return;
        }

        var pageIdentifier = state.selectedPage.identifier;
        var groupTitle = group.title || '';
        var variableName = normalizeCode(groupTitle || 'group_images');
        if (!variableName) {
            variableName = 'group_images';
        }

        if (/^\d/.test(variableName)) {
            variableName = 'pg_' + variableName;
        }

        var snippet = '';
        snippet += '<?php\n';
        snippet += '$pagegroup = $pagegroup ?? \\Magento\\Framework\\App\\ObjectManager::getInstance()\n';
        snippet += '    ->get(\\ArtM\\PageGroups\\Model\\PageGroup::class);\n\n';
        snippet += '$' + variableName + ' = $pagegroup->getImages(' + JSON.stringify(pageIdentifier) + ', ' + JSON.stringify(groupTitle) + ');\n';
        snippet += '// $' + variableName + ' now contains complete filtered image array\n';
        snippet += '// print_r($' + variableName + ');\n';
        snippet += '?>';

        var html = '';
        html += '<div class="pg-modal-inner">';
        html += '<h3>PHTML Embed Code</h3>';
        html += '<p>Initialize <code>$pagegroup</code> once anywhere in the file. Then use <code>$pagegroup->getImages(pageIdentifier, groupTitle)</code> to get filtered images.</p>';
        html += '<textarea id="pg-embed-code" rows="13" style="width:100%;font-family:Consolas, monospace;">' + escapeHtml(snippet) + '</textarea>';
        html += '<div class="pg-modal-actions">';
        html += '<button type="button" id="pg-copy-embed" class="action-primary">Copy Code</button>';
        html += '<button type="button" id="pg-close-embed">Close</button>';
        html += '</div>';
        html += '</div>';

        $('#media-modal').html(html).show();
        $('#pg-close-embed').off('click').on('click', function () { $('#media-modal').hide().empty(); });
        $('#pg-copy-embed').off('click').on('click', function () {
            var code = $('#pg-embed-code').val();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function () {
                    alert('Embed code copied.');
                }, function () {
                    alert('Unable to copy. Please copy manually.');
                });
            } else {
                $('#pg-embed-code').trigger('focus').trigger('select');
                alert('Clipboard API not available. Press Ctrl+C to copy.');
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Media browser breadcrumbs                                           */
    /* ------------------------------------------------------------------ */

    function buildBreadcrumbHtml(items) {
        var html = '<div class="pg-breadcrumbs">';
        $.each(items, function (index, item) {
            html += '<button type="button" class="pg-breadcrumb-link" data-path="' + escapeHtml(item.path) + '">' + escapeHtml(item.label) + '</button>';
            if (index < items.length - 1) {
                html += '<span class="pg-breadcrumb-sep">&rsaquo;</span>';
            }
        });
        html += '</div>';
        return html;
    }

    /* ------------------------------------------------------------------ */
    /* Media picker                                                         */
    /* ------------------------------------------------------------------ */

    function openMediaPicker(config, groupId, existingImages) {
        existingImages = existingImages || [];
        state.mediaPicker = {
            path: '',
            groupId: groupId,
            selectedPaths: existingImages.slice() // Clone the array
        };
        loadMediaPicker(config, '');
    }

    function loadMediaPicker(config, path) {
        path = path || '';
        $.when(
            $.ajax({url: config.mediaFoldersUrl, type: 'GET', dataType: 'json', data: {path: path}}),
            $.ajax({url: config.mediaFilesUrl,   type: 'GET', dataType: 'json', data: {path: path}})
        ).done(function (foldersRes, filesRes) {
            var folderData = foldersRes[0] || {};
            var fileData   = filesRes[0]  || {};
            if (!folderData.success) { alert(folderData.message || 'Unable to load folders.'); return; }
            if (!fileData.success)   { alert(fileData.message   || 'Unable to load files.');   return; }
            state.mediaPicker.path = folderData.current_path || path;
            renderMediaPicker(folderData, fileData, config);
        }).fail(function () {
            alert('Unable to load media browser.');
        });
    }

    function renderMediaPicker(folderData, fileData, config) {
        var selectedPaths = state.mediaPicker.selectedPaths;

        var html = '<div class="pg-modal-inner pg-media-browser">';

        /* ---- header with close button ---- */
        html += '<div class="pg-media-header">';
        html += '<h3>Media Browser</h3>';
        html += '<button type="button" id="pg-media-close" class="pg-btn-icon" title="Close">&#x2715;</button>';
        html += '</div>';

        /* ---- breadcrumbs at top ---- */
        html += buildBreadcrumbHtml(folderData.breadcrumbs || []);

        /* ---- body columns ---- */
        html += '<div class="pg-media-columns">';

        /* folders column */
        html += '<div class="pg-media-section pg-media-section-folders">';
        html += '<h4>Folders</h4>';
        html += '<div class="pg-create-folder">';
        html += '<input type="text" id="pg-folder-name" placeholder="New folder name" maxlength="60">';
        html += '<button type="button" id="pg-create-folder-btn" class="action-secondary">Create</button>';
        html += '</div>';
        html += '<div class="pg-media-folder-list">';
        $.each(folderData.items || [], function (i, folder) {
            html += '<button type="button" class="pg-media-folder" data-path="' + escapeHtml(folder.path) + '">';
            html += '<span class="pg-media-folder-icon">&#x1F4C1;</span>';
            html += '<span class="pg-media-folder-name">' + escapeHtml(folder.name) + '</span>';
            html += '</button>';
        });
        if (!(folderData.items || []).length) {
            html += '<p class="pg-media-empty">No sub-folders</p>';
        }
        html += '</div>';
        html += '</div>';

        /* images column */
        html += '<div class="pg-media-section pg-media-section-files">';
        html += '<div class="pg-media-section-head">';
        html += '<h4>Images <small class="pg-sel-count" id="pg-sel-count">' + (selectedPaths.length ? selectedPaths.length + ' selected' : '') + '</small></h4>';
        html += '<label class="pg-upload-label">';
        html += '<svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M10 3v10m0-10L7 6m3-3l3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 14v1a2 2 0 002 2h10a2 2 0 002-2v-1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
        html += '<span>Upload</span>';
        html += '<input type="file" id="pg-media-upload" accept="image/*" multiple>';
        html += '</label>';
        html += '</div>';
        html += '<div class="pg-media-file-grid">';
        $.each(fileData.items || [], function (i, file) {
            var isSel = selectedPaths.indexOf(file.path) >= 0;
            html += '<div class="pg-media-file' + (isSel ? ' pg-media-file-selected' : '') + '" data-path="' + escapeHtml(file.path) + '" title="' + escapeHtml(file.name) + '">';
            html += '<div class="pg-media-thumb-wrap"><img src="' + escapeHtml(file.url) + '" alt="' + escapeHtml(file.name) + '" class="pg-media-thumb"></div>';
            html += '<div class="pg-media-file-actions">';
            html += '<button type="button" class="pg-media-toggle-select' + (isSel ? ' selected' : '') + '" data-path="' + escapeHtml(file.path) + '" title="' + (isSel ? 'Deselect' : 'Select') + '">' + (isSel ? '&#x2713;' : '') + '</button>';
            html += '<button type="button" class="pg-media-delete" data-path="' + escapeHtml(file.path) + '" title="Delete">&#x2715;</button>';
            html += '</div>';
            html += '</div>';
        });
        if (!(fileData.items || []).length) {
            html += '<p class="pg-media-empty">No images in this folder.</p>';
        }
        html += '</div>';
        html += '</div>';
        html += '</div>'; /* /pg-media-columns */

        /* ---- footer ---- */
        html += '<div class="pg-media-footer">';
        html += '<span id="pg-footer-count">' + (selectedPaths.length ? selectedPaths.length + ' image(s) selected' : 'Click images to select them') + '</span>';
        html += '<div class="pg-media-footer-actions">';
        html += '<button type="button" id="pg-media-cancel">Cancel</button>';
        html += '<button type="button" id="pg-media-add-selected" class="action-primary"' + (!selectedPaths.length ? ' disabled' : '') + '>Add ' + (selectedPaths.length || '') + ' to Group</button>';
        html += '</div>';
        html += '</div>';

        html += '</div>'; /* /pg-modal-inner */

        $('#media-modal').html(html).show();

        /* close / cancel */
        $('#pg-media-close, #pg-media-cancel').off('click').on('click', function () {
            state.mediaPicker = null;
            $('#media-modal').hide().empty();
        });

        /* folder navigation */
        $('#media-modal .pg-breadcrumb-link, #media-modal .pg-media-folder').off('click').on('click', function () {
            loadMediaPicker(config, $(this).data('path') || '');
        });

        /* create folder */
        $('#pg-create-folder-btn').off('click').on('click', function () {
            var name = $.trim($('#pg-folder-name').val());
            if (!name) { alert('Enter a folder name.'); return; }
            $.ajax({
                url: config.mediaCreateFolderUrl,
                type: 'POST',
                dataType: 'json',
                data: {path: state.mediaPicker.path, name: name, form_key: config.formKey}
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to create folder.'); return; }
                loadMediaPicker(config, state.mediaPicker.path);
            }).fail(function () { alert('Unable to create folder.'); });
        });

        /* multi-file upload */
        $('#pg-media-upload').off('change').on('change', function () {
            var files = this.files;
            if (!files || !files.length) { return; }
            var uploads = [];
            for (var i = 0; i < files.length; i++) {
                var fd = new FormData();
                fd.append('image', files[i]);
                fd.append('path', state.mediaPicker.path || '');
                fd.append('form_key', config.formKey);
                uploads.push($.ajax({url: config.mediaUploadUrl, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json'}));
            }
            $.when.apply($, uploads).always(function () {
                loadMediaPicker(config, state.mediaPicker.path || '');
            });
        });

        /* select / deselect image */
        $('#media-modal .pg-media-toggle-select').off('click').on('click', function (e) {
            e.stopPropagation();
            var path = $(this).data('path');
            var idx  = selectedPaths.indexOf(path);
            if (idx >= 0) {
                selectedPaths.splice(idx, 1);
                $(this).html('').removeClass('selected').attr('title', 'Select');
                $(this).closest('.pg-media-file').removeClass('pg-media-file-selected');
            } else {
                selectedPaths.push(path);
                $(this).html('&#x2713;').addClass('selected').attr('title', 'Deselect');
                $(this).closest('.pg-media-file').addClass('pg-media-file-selected');
            }
            var count = selectedPaths.length;
            $('#pg-sel-count').text(count ? count + ' selected' : '');
            $('#pg-footer-count').text(count ? count + ' image(s) selected' : 'Click images to select them');
            $('#pg-media-add-selected').prop('disabled', !count).text('Add ' + (count || '') + ' to Group');
        });

        /* delete file */
        $('#media-modal .pg-media-delete').off('click').on('click', function (e) {
            e.stopPropagation();
            var path = $(this).data('path');
            if (!confirm('Delete this image permanently?')) { return; }
            $.ajax({
                url: config.mediaDeleteUrl,
                type: 'POST',
                dataType: 'json',
                data: {path: path, form_key: config.formKey}
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to delete.'); return; }
                var idx = selectedPaths.indexOf(path);
                if (idx >= 0) { selectedPaths.splice(idx, 1); }
                loadMediaPicker(config, state.mediaPicker.path || '');
            }).fail(function () { alert('Unable to delete image.'); });
        });

        /* add selected to group */
        $('#pg-media-add-selected').off('click').on('click', function () {
            if (!selectedPaths.length) { return; }
            var groupId = state.mediaPicker.groupId;
            $.ajax({
                url: config.groupImageAddUrl,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                data: $.param({group_id: groupId, 'paths[]': selectedPaths, form_key: config.formKey}, true)
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to add images.'); return; }
                state.mediaPicker = null;
                $('#media-modal').hide().empty();
                loadGroups(config);
            }).fail(function () { alert('Unable to add images.'); });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Groups                                                              */
    /* ------------------------------------------------------------------ */

    function renderGroups(items, config) {
        var page = state.selectedPage;
        var html = '';

        if (!page || !page.page_id) {
            $('#groups-panel').html('<p>Select a saved page to load groups.</p>');
            return;
        }

        html += '<div id="groups-list" class="pg-group-cards">';
        if (!items.length) {
            html += '<p>No groups yet. Click <strong>Add Group</strong> to get started.</p>';
        }
        $.each(items, function (index, group) {
            html += applyTemplate('#pg-group-card-template', {
                group_id:    group.group_id,
                title:       group.title,
                images_html: buildGroupImagesHtml(group),
                collapsed_class: isGroupCollapsed(group.group_id) ? 'is-collapsed' : '',
                collapse_label: isGroupCollapsed(group.group_id) ? 'Expand' : 'Collapse'
            });
        });
        html += '</div>';
        $('#groups-panel').html(html);

        $('#groups-panel .pg-group-toggle').off('click').on('click', function (event) {
            event.stopPropagation();
            var groupId = parseInt($(this).data('group-id'), 10);
            var card = $('.pg-group-card[data-group-id="' + groupId + '"]');
            var nextCollapsed = !card.hasClass('is-collapsed');
            card.toggleClass('is-collapsed', nextCollapsed);
            $(this).text(nextCollapsed ? 'Expand' : 'Collapse');
            setGroupCollapsed(groupId, nextCollapsed);
        });

        function findGroupById(groupId) {
            var found = null;
            $.each(items, function (i, item) {
                if (parseInt(item.group_id, 10) === parseInt(groupId, 10)) {
                    found = item;
                    return false;
                }
            });
            return found;
        }

        /* delete group */
        $('#groups-panel .pg-group-delete').off('click').on('click', function (event) {
            event.stopPropagation();
            var groupId = $(this).data('group-id');
            if (!confirm('Delete this group and all its images?')) { return; }
            $.ajax({
                url: config.groupDeleteUrl,
                type: 'POST',
                dataType: 'json',
                data: {group_id: groupId, form_key: config.formKey}
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to delete group.'); return; }
                loadGroups(config);
            }).fail(function () { alert('Unable to delete group.'); });
        });

        /* click card body -> edit */
        $('#groups-panel .pg-group-card').off('click').on('click', function (event) {
            if ($(event.target).is('button, img, input, select, textarea, label')) { return; }
            var groupId = parseInt($(this).data('group-id'), 10);
            var group = null;
            $.each(items, function (i, item) {
                if (parseInt(item.group_id, 10) === groupId) { group = item; return false; }
            });
            if (group) { openGroupForm(group, config); }
        });

        /* remove single image */
        $('#groups-panel .pg-group-img-remove').off('click').on('click', function (event) {
            event.stopPropagation();
            var imageId = parseInt($(this).data('image-id'), 10);
            if (!confirm('Remove this image from the group?')) { return; }
            $.ajax({
                url: config.groupImageDeleteUrl,
                type: 'POST',
                dataType: 'json',
                data: {image_id: imageId, form_key: config.formKey}
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to remove image.'); return; }
                loadGroups(config);
            }).fail(function () { alert('Unable to remove image.'); });
        });

        /* quick hide/unhide */
        $('#groups-panel .pg-group-img-hide-toggle').off('click').on('click', function (event) {
            event.stopPropagation();
            var imageId = parseInt($(this).data('image-id'), 10);
            var nextHidden = parseInt($(this).data('is-hidden'), 10) === 1 ? 0 : 1;
            $.ajax({
                url: config.groupImageSaveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    image_id: imageId,
                    is_hidden: nextHidden,
                    visibility_mode: 'keep',
                    start_at: '',
                    end_at: '',
                    form_key: config.formKey
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    alert(response && response.message ? response.message : 'Unable to change visibility.');
                    return;
                }
                loadGroups(config);
            }).fail(function () {
                alert('Unable to change visibility.');
            });
        });

        /* open visibility editor */
        $('#groups-panel .pg-group-img-edit').off('click').on('click', function (event) {
            event.stopPropagation();
            var imageId = parseInt($(this).data('image-id'), 10);
            var targetImage = null;
            $.each(items, function (i, item) {
                $.each(item.images || [], function (j, img) {
                    if (parseInt(img.image_id, 10) === imageId) {
                        targetImage = img;
                        return false;
                    }
                });
                if (targetImage) {
                    return false;
                }
            });
            if (!targetImage) {
                alert('Image data not found.');
                return;
            }
            openImageVisibilityForm(targetImage, config);
        });

        /* preview image */
        $('#groups-panel .pg-group-img-preview').off('click').on('click', function (event) {
            event.stopPropagation();
            var imageUrl = String($(this).data('image-url') || '');
            if (!imageUrl) {
                return;
            }

            var html = '';
            html += '<div class="pg-modal-inner pg-image-preview-modal">';
            html += '<h3>Image Preview</h3>';
            html += '<div class="pg-image-preview-wrap"><img src="' + escapeHtml(imageUrl) + '" alt="Preview"></div>';
            html += '<div class="pg-modal-actions">';
            html += '<button type="button" id="pg-image-preview-close">Close</button>';
            html += '</div>';
            html += '</div>';

            $('#media-modal').html(html).show();
            $('#pg-image-preview-close').off('click').on('click', function () {
                $('#media-modal').hide().empty();
            });
        });

        /* open media picker */
        $('#groups-panel .pg-group-media-btn').off('click').on('click', function (event) {
            event.stopPropagation();
            var groupId = parseInt($(this).data('group-id'), 10);
            var existingImages = [];
            // Find the group and extract existing image paths
            $.each(items, function (i, item) {
                if (parseInt(item.group_id, 10) === groupId && item.images) {
                    $.each(item.images, function (j, img) {
                        existingImages.push(img.image_path);
                    });
                }
            });
            openMediaPicker(config, groupId, existingImages);
        });

        /* add attribute from single input: attach existing or auto-create */
        $('#groups-panel .pg-group-attr-apply').off('click').on('click', function (event) {
            event.stopPropagation();
            var groupId = parseInt($(this).data('group-id'), 10);
            var input = $('.pg-attr-unified-input[data-group-id="' + groupId + '"]');
            var term = $.trim(input.val());
            if (!term) {
                alert('Type or select an attribute first.');
                return;
            }

            var existing = findCatalogAttributeByInput(term);
            if (existing) {
                addAttributeChipToGroup(groupId, {
                    attribute_id: existing.attribute_id,
                    attribute_code: existing.attribute_code,
                    label: existing.label
                });
                input.val('');
                return;
            }

            $.ajax({
                url: config.attributeSaveUrl,
                type: 'POST',
                dataType: 'json',
                data: {label: term, attribute_code: normalizeCode(term), form_key: config.formKey}
            }).done(function (response) {
                if (!response || !response.success || !response.item) {
                    alert(response && response.message ? response.message : 'Unable to create attribute.');
                    return;
                }

                var existsInCatalog = false;
                $.each(state.attributeCatalog || [], function (i, item) {
                    if (parseInt(item.attribute_id, 10) === parseInt(response.item.attribute_id, 10)) {
                        existsInCatalog = true;
                        return false;
                    }
                });
                if (!existsInCatalog) {
                    state.attributeCatalog.push(response.item);
                }

                addAttributeChipToGroup(groupId, {
                    attribute_id: response.item.attribute_id,
                    attribute_code: response.item.attribute_code,
                    label: response.item.label
                });
                input.val('');
            }).fail(function (xhr) {
                alert('Unable to create attribute. Request failed with status ' + (xhr && xhr.status ? xhr.status : 'unknown') + '.');
            });
        });

        $('#groups-panel .pg-attr-unified-input').off('keydown').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var groupId = parseInt($(this).data('group-id'), 10);
                $('.pg-group-attr-apply[data-group-id="' + groupId + '"]').trigger('click');
            }
        });

        /* remove attached attribute in draft */
        $('#groups-panel .pg-group-attr-remove').off('click').on('click', function (event) {
            event.stopPropagation();
            var chip = $(this).closest('.pg-group-attr-chip');
            var code = String(chip.data('attribute-code') || '');
            var groupId = parseInt(chip.closest('.pg-group-attr-chips').data('group-id'), 10);
            chip.remove();

            $('.pg-group-image-grid[data-group-id="' + groupId + '"] .pg-image-attr-input[data-attribute-code="' + code + '"]').each(function () {
                $(this).prev('label').remove();
                $(this).remove();
            });

            var chipsWrap = $('.pg-group-attr-chips[data-group-id="' + groupId + '"]');
            if (!chipsWrap.find('.pg-group-attr-chip').length) {
                chipsWrap.append('<span class="pg-group-attr-empty">No attributes attached</span>');
                $('.pg-group-image-grid[data-group-id="' + groupId + '"] .pg-group-image-attrs').html('<span class="pg-group-attr-empty-inline">Attach attributes to enter values.</span>');
            }
        });

        /* save group metadata */
        $('#groups-panel .pg-group-save-meta').off('click').on('click', function (event) {
            event.stopPropagation();
            var btn = $(this);
            var btnSpan = btn.find('span');
            var originalText = $.trim(btnSpan.text() || btn.text() || 'Save Attributes');

            if (btn.prop('disabled')) {
                return;
            }

            btn.prop('disabled', true);
            if (btnSpan.length) {
                btnSpan.text('Saving attributes...');
            } else {
                btn.text('Saving attributes...');
            }

            var groupId = parseInt($(this).data('group-id'), 10);
            var card = $('.pg-group-card[data-group-id="' + groupId + '"]');
            var attributes = [];
            var values = {};

            card.find('.pg-group-attr-chip').each(function (index) {
                attributes.push({
                    attribute_id: parseInt($(this).data('attribute-id'), 10) || 0,
                    attribute_code: String($(this).data('attribute-code') || ''),
                    label: String($(this).data('label') || ''),
                    sort_order: (index + 1) * 10
                });
            });

            card.find('.pg-image-attr-input').each(function () {
                var imageId = parseInt($(this).data('image-id'), 10);
                var code = String($(this).data('attribute-code') || '');
                var value = $(this).val();
                if (!imageId || !code) {
                    return;
                }
                if (!values[imageId]) {
                    values[imageId] = {};
                }
                values[imageId][code] = value;
            });

            $.ajax({
                url: config.groupMetadataSaveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    group_id: groupId,
                    attributes_json: JSON.stringify(attributes),
                    values_json: JSON.stringify(values),
                    form_key: config.formKey
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    btn.prop('disabled', false);
                    if (btnSpan.length) {
                        btnSpan.text(originalText);
                    } else {
                        btn.text(originalText);
                    }
                    alert(response && response.message ? response.message : 'Unable to save attributes.');
                    return;
                }

                if (btnSpan.length) {
                    btnSpan.text('Saved attributes');
                } else {
                    btn.text('Saved attributes');
                }

                setTimeout(function () {
                    btn.prop('disabled', false);
                    if (btnSpan.length) {
                        btnSpan.text(originalText);
                    } else {
                        btn.text(originalText);
                    }
                }, 3000);
            }).fail(function () {
                btn.prop('disabled', false);
                if (btnSpan.length) {
                    btnSpan.text(originalText);
                } else {
                    btn.text(originalText);
                }
                alert('Unable to save attributes.');
            });
        });

        /* embed code popup */
        $('#groups-panel .pg-group-embed-btn').off('click').on('click', function (event) {
            event.stopPropagation();
            var groupId = parseInt($(this).data('group-id'), 10);
            var group = findGroupById(groupId);
            if (!group) {
                alert('Unable to load group context for embed.');
                return;
            }
            openEmbedPopup(group);
        });

        /* image sort within each group */
        $('#groups-panel .pg-group-image-grid').each(function () {
            var grid = $(this);
            var groupId = parseInt(grid.data('group-id'), 10);
            if (grid.find('.pg-group-img-item').length <= 1) {
                return;
            }

            grid.sortable({
                items: '.pg-group-img-item',
                update: function () {
                    var imageIds = [];
                    grid.find('.pg-group-img-item').each(function () {
                        imageIds.push(parseInt($(this).data('image-id'), 10));
                    });

                    $.ajax({
                        url: config.groupImageSortUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {group_id: groupId, image_ids: imageIds, form_key: config.formKey}
                    }).done(function (response) {
                        if (!response || !response.success) {
                            alert(response && response.message ? response.message : 'Unable to sort images.');
                            return;
                        }
                        loadGroups(config);
                    }).fail(function () {
                        alert('Unable to sort images.');
                    });
                }
            });
        });

        /* sortable */
        if ($('#groups-list .pg-group-card').length > 1) {
            $('#groups-list').sortable({
                items: '.pg-group-card',
                update: function () {
                    var ordered = [];
                    $('#groups-list .pg-group-card').each(function () { ordered.push($(this).data('group-id')); });
                    $.ajax({
                        url: config.groupSortUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {page_id: state.selectedPage.page_id, group_ids: ordered, form_key: config.formKey}
                    }).done(function (response) {
                        if (!response || !response.success) { alert(response.message || 'Unable to save sort order.'); return; }
                        loadGroups(config);
                    }).fail(function () { alert('Unable to save sort order.'); });
                }
            });
        }
    }

    function loadGroups(config) {
        if (!state.selectedPage || !state.selectedPage.page_id) {
            $('#groups-panel').html('<p>Select a saved page to load groups.</p>');
            return;
        }
        $.ajax({
            url: config.groupListUrl,
            type: 'GET',
            dataType: 'json',
            data: {page_id: state.selectedPage.page_id}
        }).done(function (response) {
            if (!response || !response.success) {
                $('#groups-panel').html('<p>' + escapeHtml(response && response.message ? response.message : 'Unable to load groups.') + '</p>');
                return;
            }
            renderGroups(response.items || [], config);
        }).fail(function () { $('#groups-panel').html('<p>Unable to load groups.</p>'); });
    }

    function loadAttributeCatalog(config, done) {
        $.ajax({
            url: config.attributeListUrl,
            type: 'GET',
            dataType: 'json'
        }).done(function (response) {
            if (response && response.success && $.isArray(response.items)) {
                state.attributeCatalog = response.items;
            } else {
                state.attributeCatalog = [];
            }
        }).fail(function () {
            state.attributeCatalog = [];
        }).always(function () {
            if (typeof done === 'function') {
                done();
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Group form                                                          */
    /* ------------------------------------------------------------------ */

    function openGroupForm(group, config) {
        if (!state.selectedPage || !state.selectedPage.page_id) {
            alert('Please select a saved page first.');
            return;
        }
        var model = $.extend({group_id: '', title: '', sort_order: 0}, group || {});
        var html = '';
        html += '<div class="pg-modal-inner">';
        html += '<h3>' + (model.group_id ? 'Edit Group' : 'Add Group') + '</h3>';
        html += '<label>Title</label>';
        html += '<input type="text" id="pg-group-title" value="' + escapeHtml(model.title) + '">';
        html += '<label>Sort Order</label>';
        html += '<input type="number" id="pg-group-sort-order" value="' + parseInt(model.sort_order || 0, 10) + '">';
        html += '<div class="pg-modal-actions">';
        html += '<button type="button" id="pg-group-save" class="action-primary">Save</button>';
        html += '<button type="button" id="pg-group-cancel">Cancel</button>';
        html += '</div>';
        html += '</div>';

        $('#media-modal').html(html).show();

        $('#pg-group-cancel').off('click').on('click', function () { $('#media-modal').hide().empty(); });

        $('#pg-group-save').off('click').on('click', function () {
            $.ajax({
                url: config.groupSaveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    group_id: model.group_id,
                    page_id: state.selectedPage.page_id,
                    title: $('#pg-group-title').val(),
                    sort_order: $('#pg-group-sort-order').val(),
                    form_key: config.formKey
                }
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to save group.'); return; }
                $('#media-modal').hide().empty();
                loadGroups(config);
            }).fail(function () { alert('Unable to save group.'); });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Pages                                                               */
    /* ------------------------------------------------------------------ */

    function renderPages(items, config) {
        var html = '<div class="pg-cards">';
        if (!items.length) { html += '<p>No pages found.</p>'; }
        $.each(items, function (index, page) {
            var actions = '';
            if (!page.is_default) {
                actions += '<div class="pg-card-actions">';
                actions += '<button type="button" class="pg-edit" data-page-id="' + page.page_id + '">Edit</button>';
                actions += '<button type="button" class="pg-delete" data-page-id="' + page.page_id + '">Delete</button>';
                actions += '</div>';
            }
            html += applyTemplate('#pg-page-card-template', {
                page_id: page.page_id, title: page.title,
                identifier: page.identifier, sort_order: page.sort_order, actions: actions
            });
        });
        html += '</div>';
        $('#pages-panel').html(html);

        $('#pages-panel .pg-card').off('click').on('click', function (event) {
            if ($(event.target).closest('.pg-card-actions').length) { return; }
            var pageId = $(this).data('page-id');
            var page = null;
            $.each(items, function (i, item) {
                if (parseInt(item.page_id, 10) === parseInt(pageId, 10)) { page = item; return false; }
            });
            if (page) {
                state.selectedPage = page;
                $('.pg-card').removeClass('active');
                $(this).addClass('active');
                loadGroups(config);
            }
        });

        $('#pages-panel .pg-edit').off('click').on('click', function () {
            var pageId = $(this).data('page-id');
            var page = null;
            $.each(items, function (i, item) {
                if (parseInt(item.page_id, 10) === parseInt(pageId, 10)) { page = item; return false; }
            });
            if (page) { openPageForm(page, config); }
        });

        $('#pages-panel .pg-delete').off('click').on('click', function () {
            var pageId = $(this).data('page-id');
            if (!confirm('Delete this page and all its groups?')) { return; }
            $.ajax({
                url: config.deleteUrl,
                type: 'POST',
                dataType: 'json',
                data: {page_id: pageId, form_key: config.formKey}
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to delete page.'); return; }
                loadPages(config);
            }).fail(function () { alert('Unable to delete page.'); });
        });
    }

    function loadPages(config) {
        $.ajax({url: config.listUrl, type: 'GET', dataType: 'json'}).done(function (response) {
            if (!response || !response.success) { renderPages(getDefaultPages(), config); return; }
            var items = response.items || [];
            if (!items.length) { items = getDefaultPages(); }
            renderPages(items, config);
        }).fail(function () { renderPages(getDefaultPages(), config); });
    }

    /* ------------------------------------------------------------------ */
    /* Page form                                                           */
    /* ------------------------------------------------------------------ */

    function openPageForm(page, config) {
        var model = page || {page_id: '', title: '', identifier: '', sort_order: 0};
        var html = '';
        html += '<div class="pg-modal-inner">';
        html += '<h3>' + (model.page_id ? 'Edit Page' : 'Add Page') + '</h3>';
        html += '<label>Title</label>';
        html += '<input type="text" id="pg-title" value="' + escapeHtml(model.title) + '">';
        html += '<label>Identifier</label>';
        html += '<input type="text" id="pg-identifier" value="' + escapeHtml(model.identifier) + '">';
        html += '<label>Sort Order</label>';
        html += '<input type="number" id="pg-sort-order" value="' + parseInt(model.sort_order, 10) + '">';
        html += '<div class="pg-modal-actions">';
        html += '<button type="button" id="pg-save" class="action-primary">Save</button>';
        html += '<button type="button" id="pg-cancel">Cancel</button>';
        html += '</div>';
        html += '</div>';

        $('#media-modal').html(html).show();

        $('#pg-cancel').off('click').on('click', function () { $('#media-modal').hide().empty(); });

        $('#pg-save').off('click').on('click', function () {
            $.ajax({
                url: config.saveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    page_id: model.page_id,
                    title: $('#pg-title').val(),
                    identifier: $('#pg-identifier').val(),
                    sort_order: $('#pg-sort-order').val(),
                    form_key: config.formKey
                }
            }).done(function (response) {
                if (!response || !response.success) { alert(response.message || 'Unable to save page.'); return; }
                $('#media-modal').hide().empty();
                loadPages(config);
            }).fail(function () { alert('Unable to save page.'); });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Bootstrap                                                           */
    /* ------------------------------------------------------------------ */

    return function (config) {
        $(function () {
            config = config || {};
            state.collapsedGroups = loadCollapsedGroups();
            $('#groups-panel').html('<p>Select a page to load groups.</p>');
            $('#media-modal').hide();
            $('#add-page-btn').on('click', function () { openPageForm(null, config); });
            $('#add-group-btn').on('click', function () {
                if (!state.selectedPage || !state.selectedPage.page_id) {
                    alert('Please select a page first.');
                    return;
                }
                openGroupForm(null, config);
            });
            loadAttributeCatalog(config, function () {
                loadPages(config);
            });
        });
    };
});
