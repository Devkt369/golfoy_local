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

        function refreshTabCounts() {
            $(document).trigger('artm:massproducts:refreshTabCounts');
        }

        var categoryTreeData = null;
        var categoryModalInit = false;
        var activeCategoryInput = null;
        var selectedCategoryMap = {};

        var activeMediaDraftId = 0;
        var disableDraftImages = !!config.disableDraftImages;
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

        var searchableSelectMinOptions = 20;
        var searchableSelectPanelId = 'artm-searchable-select-panel';
        var searchableSelectState = {
            $activeInput: $(),
            suppressBlurClose: false
        };

        function getSearchableSelectOptions($select) {
            var options = [];
            $select.find('option').each(function () {
                options.push({
                    value: String($(this).attr('value') || ''),
                    label: String($(this).text() || '')
                });
            });
            return options;
        }

        function getSearchableSelectSelectedLabel($select) {
            var $selected = $select.find('option:selected').first();
            if (!$selected.length) {
                return '';
            }
            return String($selected.text() || '');
        }

        function syncSearchableInputFromSelect($select) {
            var $wrap;
            var $input;
            var label;
            if (!$select || !$select.length || !$select.hasClass('artm-searchable-select-value')) {
                return;
            }

            $wrap = $select.closest('.artm-searchable-select');
            $input = $wrap.find('.artm-searchable-select-input').first();
            if (!$input.length) {
                return;
            }

            label = getSearchableSelectSelectedLabel($select);
            $input.val(label);
            $input.attr('title', label);
        }

        function ensureSearchableSelectPanel() {
            if ($('#' + searchableSelectPanelId).length) {
                return;
            }

            $('body').append(
                '<div id="' + searchableSelectPanelId + '" class="artm-searchable-select-panel" style="display:none;">'
                + '<div class="artm-searchable-select-panel-list"></div>'
                + '</div>'
            );
        }

        function closeSearchableSelectPanel() {
            searchableSelectState.$activeInput = $();
            $('#' + searchableSelectPanelId).hide().removeAttr('data-open-for');
        }

        function positionSearchableSelectPanel($input) {
            var panelWidth = 290;
            var panelMargin = 8;
            var rect;
            var top;
            var left;

            if (!$input || !$input.length || !$input[0]) {
                return;
            }

            rect = $input[0].getBoundingClientRect();
            top = Math.max(8, rect.top);
            left = rect.right + panelMargin;

            if (left + panelWidth > window.innerWidth - 8) {
                left = Math.max(8, rect.left - panelWidth - panelMargin);
            }

            $('#' + searchableSelectPanelId).css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                width: panelWidth + 'px'
            });
        }

        function renderSearchableSelectPanel($input, query) {
            var $wrap;
            var $select;
            var options;
            var selectedValue;
            var normalizedQuery;
            var html = [];
            var matchCount = 0;

            if (!$input || !$input.length) {
                return;
            }

            $wrap = $input.closest('.artm-searchable-select');
            $select = $wrap.find('.artm-searchable-select-value').first();
            if (!$select.length) {
                return;
            }

            options = getSearchableSelectOptions($select);
            selectedValue = String($select.val() || '');
            normalizedQuery = String(query || '').toLowerCase().trim();

            $.each(options, function (_, option) {
                var label = String(option.label || '');
                var value = String(option.value || '');
                var isSelected = selectedValue === value;

                if (normalizedQuery && label.toLowerCase().indexOf(normalizedQuery) === -1) {
                    return;
                }

                html.push('<button type="button" class="artm-searchable-select-option' + (isSelected ? ' is-selected' : '') + '" data-value="' + escapeHtml(value) + '">' + escapeHtml(label || $t('-- Select --')) + '</button>');
                matchCount += 1;
            });

            if (!matchCount) {
                html.push('<div class="artm-searchable-select-empty">' + escapeHtml($t('No matching options found.')) + '</div>');
            }

            ensureSearchableSelectPanel();
            positionSearchableSelectPanel($input);
            $('#' + searchableSelectPanelId).find('.artm-searchable-select-panel-list').html(html.join(''));
            $('#' + searchableSelectPanelId)
                .attr('data-open-for', String($wrap.data('searchableId') || ''))
                .show();
            searchableSelectState.$activeInput = $input;
        }

        function normalizeSearchableSelectInput($input) {
            var $wrap;
            var $select;
            var typed;
            var matched = false;

            if (!$input || !$input.length) {
                return;
            }

            $wrap = $input.closest('.artm-searchable-select');
            $select = $wrap.find('.artm-searchable-select-value').first();
            if (!$select.length) {
                return;
            }

            typed = String($input.val() || '').trim();
            if (!typed) {
                if (String($select.val() || '') !== '') {
                    $select.val('').trigger('change');
                }
                syncSearchableInputFromSelect($select);
                return;
            }

            $select.find('option').each(function () {
                var $opt = $(this);
                if (String($opt.text() || '').trim().toLowerCase() === typed.toLowerCase()) {
                    if (String($select.val() || '') !== String($opt.val() || '')) {
                        $select.val(String($opt.val() || '')).trigger('change');
                    }
                    matched = true;
                    return false;
                }
                return true;
            });

            if (!matched) {
                syncSearchableInputFromSelect($select);
            }
        }

        function bindSearchableSelectActions() {
            $(document)
                .off('focus.artmSearchableSelect', '.artm-searchable-select-input')
                .on('focus.artmSearchableSelect', '.artm-searchable-select-input', function () {
                    renderSearchableSelectPanel($(this), '');
                })
                .off('input.artmSearchableSelect', '.artm-searchable-select-input')
                .on('input.artmSearchableSelect', '.artm-searchable-select-input', function () {
                    renderSearchableSelectPanel($(this), $(this).val());
                })
                .off('keydown.artmSearchableSelect', '.artm-searchable-select-input')
                .on('keydown.artmSearchableSelect', '.artm-searchable-select-input', function (e) {
                    var $input = $(this);
                    var $panel = $('#' + searchableSelectPanelId);
                    var $firstOption;
                    if (e.key === 'Escape') {
                        closeSearchableSelectPanel();
                        return;
                    }
                    if (e.key === 'ArrowDown' && (!$panel.is(':visible') || searchableSelectState.$activeInput[0] !== $input[0])) {
                        renderSearchableSelectPanel($input, $input.val());
                        return;
                    }
                    if (e.key === 'Enter' && $panel.is(':visible') && searchableSelectState.$activeInput[0] === $input[0]) {
                        $firstOption = $panel.find('.artm-searchable-select-option').first();
                        if ($firstOption.length) {
                            e.preventDefault();
                            $firstOption.trigger('click');
                        }
                    }
                })
                .off('blur.artmSearchableSelect', '.artm-searchable-select-input')
                .on('blur.artmSearchableSelect', '.artm-searchable-select-input', function () {
                    var $input = $(this);
                    setTimeout(function () {
                        if (searchableSelectState.suppressBlurClose) {
                            return;
                        }
                        normalizeSearchableSelectInput($input);
                        closeSearchableSelectPanel();
                    }, 120);
                })
                .off('mousedown.artmSearchableSelect', '.artm-searchable-select-option')
                .on('mousedown.artmSearchableSelect', '.artm-searchable-select-option', function () {
                    searchableSelectState.suppressBlurClose = true;
                    setTimeout(function () {
                        searchableSelectState.suppressBlurClose = false;
                    }, 180);
                })
                .off('click.artmSearchableSelect', '.artm-searchable-select-option')
                .on('click.artmSearchableSelect', '.artm-searchable-select-option', function (e) {
                    var $option = $(this);
                    var $panel = $('#' + searchableSelectPanelId);
                    var panelOwner = String($panel.attr('data-open-for') || '');
                    var $input = searchableSelectState.$activeInput;
                    var $wrap;
                    var $select;
                    var value;

                    e.preventDefault();

                    if (!$input.length) {
                        closeSearchableSelectPanel();
                        return;
                    }

                    $wrap = $input.closest('.artm-searchable-select');
                    if (panelOwner && panelOwner !== String($wrap.data('searchableId') || '')) {
                        return;
                    }

                    $select = $wrap.find('.artm-searchable-select-value').first();
                    value = String($option.attr('data-value') || '');
                    if (!$select.length) {
                        closeSearchableSelectPanel();
                        return;
                    }

                    if (String($select.val() || '') !== value) {
                        $select.val(value).trigger('change');
                    }
                    syncSearchableInputFromSelect($select);
                    closeSearchableSelectPanel();
                    $input.trigger('blur');
                })
                .off('click.artmSearchableSelectOpen', '.artm-searchable-select, .artm-searchable-select-caret')
                .on('click.artmSearchableSelectOpen', '.artm-searchable-select, .artm-searchable-select-caret', function (e) {
                    var $wrap = $(this).closest('.artm-searchable-select');
                    var $input = $wrap.find('.artm-searchable-select-input').first();
                    if (!$input.length || $(e.target).closest('#' + searchableSelectPanelId).length) {
                        return;
                    }
                    $input.trigger('focus');
                })
                .off('click.artmSearchableSelectOutside')
                .on('click.artmSearchableSelectOutside', function (e) {
                    var $target = $(e.target);
                    if ($target.closest('.artm-searchable-select, #' + searchableSelectPanelId).length) {
                        return;
                    }
                    closeSearchableSelectPanel();
                })
                .off('change.artmSearchableSelectSync', '.artm-searchable-select-value')
                .on('change.artmSearchableSelectSync', '.artm-searchable-select-value', function () {
                    syncSearchableInputFromSelect($(this));
                });

            $(window)
                .off('resize.artmSearchableSelect scroll.artmSearchableSelect')
                .on('resize.artmSearchableSelect scroll.artmSearchableSelect', function () {
                    if (searchableSelectState.$activeInput && searchableSelectState.$activeInput.length) {
                        positionSearchableSelectPanel(searchableSelectState.$activeInput);
                        return;
                    }
                    closeSearchableSelectPanel();
                });
        }

        function searchableSelectFieldHtml(code, name, val, options) {
            var safeCode = escapeHtml(code);
            var safeName = escapeHtml(name);
            var currentValue = String(val == null ? '' : val);
            var selectOptions = ['<option value="">' + escapeHtml($t('-- Select --')) + '</option>'];
            var selectedLabel = '';
            var instanceId = 'artm-searchable-' + safeCode + '-' + Math.random().toString(36).slice(2, 10);

            $.each(options || [], function (_, opt) {
                var ov = String(opt && opt.value || '');
                var label = String(opt && (opt.label || ov) || ov);
                var selected = currentValue === ov ? ' selected="selected"' : '';
                if (selected) {
                    selectedLabel = label;
                }
                selectOptions.push('<option value="' + escapeHtml(ov) + '"' + selected + '>' + escapeHtml(label) + '</option>');
            });

            return '<div class="artm-searchable-select" data-searchable-id="' + escapeHtml(instanceId) + '">'
                + '<input type="text" class="admin__control-text artm-searchable-select-input" value="' + escapeHtml(selectedLabel) + '" autocomplete="off" spellcheck="false" title="' + escapeHtml(selectedLabel) + '" />'
                + '<span class="artm-searchable-select-caret" aria-hidden="true">&#9662;</span>'
                + '<select class="admin__control-select artm-attr-input artm-searchable-select-value" data-code="' + safeCode + '" name="' + safeName + '" style="display:none;">'
                + selectOptions.join('')
                + '</select>'
                + '</div>';
        }

        function operationBadge(value) {
            var v = String(value || 'create').toLowerCase();
            var bg = v === 'update' ? '#2980b9' : '#2f7d32';
            return '<span style="display:inline-block;padding:2px 8px;border-radius:12px;color:#fff;background:' + bg + ';">' + escapeHtml(v) + '</span>';
        }

        function rowHasAllRequired(row, requiredCodes) {
            var codes = Array.isArray(requiredCodes) ? requiredCodes : [];
            var values = row && row.values ? row.values : {};

            if (row && typeof row.missing_attributes_count !== 'undefined' && parseInt(row.missing_attributes_count, 10) > 0) {
                return false;
            }

            if (!String(row && row.sku || '').trim()) {
                return false;
            }

            $.each(codes, function (_, code) {
                var value = Object.prototype.hasOwnProperty.call(values, code) ? values[code] : '';
                if (!String(value == null ? '' : value).trim()) {
                    values = null;
                    return false;
                }
                return true;
            });

            return values !== null;
        }

        function showMessage(type, text) {
            var cls = type === 'success' ? 'message message-success success' : 'message message-error error';
            $('#artm-draft-msg').attr('class', cls).html('<div>' + escapeHtml(text) + '</div>').show();
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

        function setActionProcessing($btn, processingText) {
            if (!$btn || !$btn.length) {
                return;
            }
            if (!$btn.data('artmOriginalText')) {
                $btn.data('artmOriginalText', $.trim($btn.find('span').first().text() || $btn.text() || ''));
            }
            $btn.prop('disabled', true);
            if (processingText) {
                setButtonText($btn, processingText);
            }
        }

        function setActionDone($btn, doneText) {
            if (!$btn || !$btn.length) {
                return;
            }
            if (doneText) {
                setButtonText($btn, doneText);
            }
            setTimeout(function () {
                var original = String($btn.data('artmOriginalText') || '').trim();
                $btn.prop('disabled', false);
                if (original) {
                    setButtonText($btn, original);
                }
            }, 1200);
        }

        function resetActionButton($btn) {
            if (!$btn || !$btn.length) {
                return;
            }
            var original = String($btn.data('artmOriginalText') || '').trim();
            $btn.prop('disabled', false);
            if (original) {
                setButtonText($btn, original);
            }
        }

        function normalizeVariationAttributes(raw) {
            var list = [];
            $.each(Array.isArray(raw) ? raw : String(raw || '').split(/[\n,]+/), function (_, item) {
                var code = String(item || '').trim();
                if (!code || list.indexOf(code) !== -1) {
                    return;
                }
                list.push(code);
            });
            return list;
        }

        function normalizeSkuValue(value) {
            return String(value || '').trim().toLowerCase();
        }

        function findDuplicateDraftSkuRow($tr, sku) {
            var targetSku = normalizeSkuValue(sku);
            var currentDraftId = parseInt($tr.data('id'), 10) || 0;
            var $duplicate = $();

            if (!targetSku) {
                return $duplicate;
            }

            $('.artm-draft-row').each(function () {
                var $row = $(this);
                var rowDraftId = parseInt($row.data('id'), 10) || 0;
                var rowSku = String($row.attr('data-sku') || '').trim();
                var $rowSkuInput = $row.find('.artm-attr-input[data-code="sku"]').first();

                if ($rowSkuInput.length) {
                    rowSku = String($rowSkuInput.val() || '').trim();
                }

                if (rowDraftId !== currentDraftId && normalizeSkuValue(rowSku) === targetSku) {
                    $duplicate = $row;
                    return false;
                }

                return true;
            });

            return $duplicate;
        }

        function validateDraftSkuUniqueness($tr, sku) {
            var cleanSku = String(sku || '').trim();
            var $skuInput = $tr.find('.artm-attr-input[data-code="sku"]').first();

            if (!$skuInput.length || !cleanSku) {
                return true;
            }

            $skuInput.removeClass('artm-field-error');
            if (!findDuplicateDraftSkuRow($tr, cleanSku).length) {
                return true;
            }

            $skuInput.addClass('artm-field-error');
            showMessage('error', $t('SKU "') + cleanSku + $t('" already exists in Draft.'));
            return false;
        }

        function applyPagination() {
            var $tables = $('#artm-draft-groups').find('table.artm-draft-table');

            $tables.each(function (index) {
                var $table = $(this);
                var $tbody = $table.find('tbody').first();
                var $rows = $tbody.children('tr');
                var $dataRows = $tbody.children('tr.artm-draft-row');
                var $filteredRows = $dataRows.filter(function () {
                    return String($(this).attr('data-filter-hidden') || '0') !== '1';
                });
                var hasActiveFilter = $table.find('.artm-column-search').filter(function () {
                    return String($(this).val() || '').trim() !== '';
                }).length > 0;
                var $singleRow = $rows.length === 1 ? $rows.eq(0) : $();
                var noDataRow = $singleRow.length && $singleRow.find('td[colspan]').length > 0 && $singleRow.find('input,button,select,textarea').length === 0;

                var $wrapper = $table.closest('.admin__fieldset-wrapper');
                var $pagerHost = $wrapper.find('.artm-draft-table-pager-host').first();
                if (!$pagerHost.length) {
                    $pagerHost = $('<div class="artm-draft-table-pager-host"></div>');
                    $table.closest('.artm-draft-table-wrap').after($pagerHost);
                }
                $pagerHost.find('.artm-table-pager').remove();

                if (!$rows.length || noDataRow) {
                    return;
                }

                if ($dataRows.length) {
                    $tbody.find('tr.artm-filter-empty-row').remove();
                    if ($filteredRows.length === 0) {
                        var colCount = $table.find('thead tr').first().children('th').length || 1;
                        $tbody.append('<tr class="artm-filter-empty-row"><td colspan="' + colCount + '" style="text-align:center;">' + escapeHtml($t('No matching products found.')) + '</td></tr>');
                    }
                }

                var sizes = [10, 25, 50, 100];
                var perPage = parseInt($table.data('artmPerPage'), 10) || 10;
                if ($.inArray(perPage, sizes) === -1) {
                    perPage = 10;
                }

                var currentPage = parseInt($table.data('artmCurrentPage'), 10) || 1;
                var totalPages = Math.max(1, Math.ceil($filteredRows.length / perPage));
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                var start = (currentPage - 1) * perPage;
                var end = start + perPage;

                $rows.hide();
                $tbody.children('tr.artm-filter-empty-row').show();

                var $pageRows = $filteredRows.slice(start, end);
                $pageRows.show();

                $tbody.children('tr.artm-group-header-row').each(function () {
                    var $header = $(this);
                    var parentSku = String($header.attr('data-parent-sku') || '').trim();
                    if (!parentSku) {
                        $header.show();
                        return;
                    }
                    var hasVisibleGroupRows = $pageRows.filter(function () {
                        var $row = $(this);
                        var rowSku = String($row.attr('data-sku') || '').trim();
                        var rowParentSku = String($row.attr('data-parent-sku') || '').trim();
                        return rowSku === parentSku || rowParentSku === parentSku;
                    }).length > 0;
                    if (hasVisibleGroupRows) {
                        $header.show();
                    }
                });

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
                pagerHtml.push('<span>' + escapeHtml($t('Page')) + ' ' + currentPage + ' ' + escapeHtml($t('of')) + ' ' + totalPages + (hasActiveFilter ? ' (' + escapeHtml($t('filtered')) + ')' : '') + '</span>');
                pagerHtml.push('<button type="button" class="action-secondary artm-pager-next"' + (currentPage >= totalPages ? ' disabled="disabled"' : '') + '><span>' + escapeHtml($t('Next')) + '</span></button>');
                pagerHtml.push('</div>');
                pagerHtml.push('</div>');

                $pagerHost.html(pagerHtml.join(''));
            });

            $('#artm-draft-groups .artm-pager-size').off('change.artmPager').on('change.artmPager', function () {
                var $pager = $(this).closest('.artm-table-pager');
                var idx = parseInt($pager.data('table-index'), 10) || 0;
                var $table = $('#artm-draft-groups').find('table.artm-draft-table').eq(idx);
                $table.data('artmPerPage', parseInt($(this).val(), 10) || 10);
                $table.data('artmCurrentPage', 1);
                applyPagination();
            });

            $('#artm-draft-groups .artm-pager-prev').off('click.artmPager').on('click.artmPager', function () {
                var $pager = $(this).closest('.artm-table-pager');
                var idx = parseInt($pager.data('table-index'), 10) || 0;
                var $table = $('#artm-draft-groups').find('table.artm-draft-table').eq(idx);
                var currentPage = parseInt($table.data('artmCurrentPage'), 10) || 1;
                $table.data('artmCurrentPage', Math.max(1, currentPage - 1));
                applyPagination();
            });

            $('#artm-draft-groups .artm-pager-next').off('click.artmPager').on('click.artmPager', function () {
                var $pager = $(this).closest('.artm-table-pager');
                var idx = parseInt($pager.data('table-index'), 10) || 0;
                var $table = $('#artm-draft-groups').find('table.artm-draft-table').eq(idx);
                var currentPage = parseInt($table.data('artmCurrentPage'), 10) || 1;
                $table.data('artmCurrentPage', currentPage + 1);
                applyPagination();
            });
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
            if (depth > 5) {
                return '';
            }

            var html = ['<ul class="artm-cat-tree">'];
            $.each(nodes || [], function (_, node) {
                if (!node || !node.id) {
                    return;
                }
                var id = parseInt(node.id, 10);
                var hasChildren = !!(node.children && node.children.length);
                var expanded = hasChildren && depth <= 5;
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
                if ((attr.options || []).length > searchableSelectMinOptions) {
                    return searchableSelectFieldHtml(code, name, val, attr.options || []);
                }

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
            var roles = item && item.roles ? item.roles : [];
            var rolesMap = {};
            $.each(roles || [], function (_, r) { rolesMap[r] = true; });

            var roleButtons = ['base', 'small', 'thumbnail'].map(function (role) {
                var labels = { base: 'B', small: 'S', thumbnail: 'T' };
                var titles = { base: $t('Base Image'), small: $t('Small Image'), thumbnail: $t('Thumbnail') };
                var active = rolesMap[role] ? ' artm-role-active' : '';
                return '<button type="button" class="artm-role-btn' + active + '" data-media-id="' + mediaId + '" data-role="' + role + '" title="' + escapeHtml(titles[role]) + '">' + labels[role] + '</button>';
            }).join('');

            return '<div class="artm-media-item" data-media-id="' + mediaId + '">'
                + '<div class="artm-mi-img">'
                + '<img src="' + escapeHtml(url) + '" />'
                + '<button type="button" class="artm-mi-del" data-media-id="' + mediaId + '" title="' + $t('Delete') + '">&#x2715;</button>'
                + '</div>'
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
                + '<button type="button" class="artm-media-card-upload" data-draft-id="' + draftId + '" title="' + escapeHtml($t('Upload Images')) + '">'
                + '<span class="artm-media-card-upload-plus">+</span>'
                + '</button>'
                + '</div>';

            return '<div class="artm-media-gallery" data-draft-id="' + draftId + '">'
                + (mediaDebug
                    ? '<div style="margin-bottom:6px;padding:4px 6px;border:1px dashed #c0392b;background:#fff5f5;color:#7f1d1d;font-size:11px;">'
                        + 'DEBUG: draft_id=' + draftId + ', media_items=' + mediaItems.length
                        + '</div>'
                    : '')
                + '<div class="artm-media-items">' + items + '</div>'
                + '</div>';
        }

        function syncMediaGallery($gallery) {
            if (!$gallery || !$gallery.length) {
                return;
            }

            return;
        }

        function renderGroup(group) {
            var html = [];

            function searchInputHtml(colIndex, placeholder, allowSearch) {
                if (!allowSearch) {
                    return '<th></th>';
                }
                return '<th><input type="text" class="admin__control-text artm-column-search" data-col-index="' + colIndex + '" placeholder="' + escapeHtml(placeholder || $t('Search')) + '" style="min-width:120px;width:100%;" /></th>';
            }

            function parseSkuList(raw) {
                var seen = {};
                var list = [];
                String(raw || '').split(/[\n,]+/).forEach(function (token) {
                    var sku = String(token || '').trim();
                    if (!sku || seen[sku]) {
                        return;
                    }
                    seen[sku] = true;
                    list.push(sku);
                });
                return list;
            }

            function skuOptionsHtml(excludeSku) {
                var opts = ['<option value=""></option>'];
                var exclude = String(excludeSku || '').trim();
                var seen = {};
                $.each(rows, function (_, r) {
                    var sku = String((r && r.sku) || '').trim();
                    if (!sku || sku === exclude || seen[sku]) {
                        return;
                    }
                    seen[sku] = true;
                    opts.push('<option value="' + escapeHtml(sku) + '">' + escapeHtml(sku) + '</option>');
                });
                return opts.join('');
            }

            function parentEditorHtml(rowSku, parentSku, datalistId) {
                return '<div class="artm-parent-editor" style="display:flex;flex-direction:column;gap:4px;min-width:190px;">'
                    + '<input type="text" class="admin__control-text artm-parent-input" list="' + escapeHtml(datalistId) + '" value="' + escapeHtml(parentSku) + '" placeholder="' + escapeHtml($t('Parent SKU')) + '" />'
                    + '<select class="admin__control-select artm-parent-select">'
                    + skuOptionsHtml(rowSku)
                    + '</select>'
                    + '</div>';
            }

            function childTagsHtml(childSkus) {
                var tags = [];
                $.each(childSkus || [], function (_, childSku) {
                    var sku = String(childSku || '').trim();
                    if (!sku) {
                        return;
                    }
                    tags.push('<span class="artm-child-tag" data-sku="' + escapeHtml(sku) + '">'
                        + '<span>' + escapeHtml(sku) + '</span>'
                        + '<button type="button" class="artm-child-remove">&times;</button>'
                        + '</span>');
                });
                return tags.join('');
            }

            function childEditorHtml(rowSku, childSkus, datalistId) {
                return '<div class="artm-child-editor" style="display:flex;flex-direction:column;gap:4px;min-width:220px;">'
                    + '<div class="artm-child-tags" style="display:flex;flex-wrap:wrap;gap:4px;">' + childTagsHtml(childSkus) + '</div>'
                    + '<div style="display:flex;gap:4px;align-items:center;">'
                    + '<input type="text" class="admin__control-text artm-child-input" list="' + escapeHtml(datalistId) + '" placeholder="' + escapeHtml($t('Child SKU')) + '" />'
                    + '<button type="button" class="action-secondary artm-child-add"><span>' + escapeHtml($t('Add')) + '</span></button>'
                    + '</div>'
                    + '<select class="admin__control-select artm-child-select">'
                    + skuOptionsHtml(rowSku)
                    + '</select>'
                    + '<datalist id="' + escapeHtml(datalistId) + '">' + skuOptionsHtml(rowSku) + '</datalist>'
                    + '</div>';
            }

            var attrs = (group.attributes || []).filter(function (attr) {
                // Remove per-source MSI qty columns (e.g. "Qty (Default)", "Qty (Warehouse)")
                var label = String(attr.frontend_label || attr.attribute_code || '').trim().toLowerCase();
                if (/^qty\s*[\(\[]/.test(label)) {
                    return false;
                }
                // Also exclude any attribute_code starting with msi_qty__
                var code = String(attr.attribute_code || '').toLowerCase();
                if (code.indexOf('msi_qty__') === 0) {
                    return false;
                }
                return true;
            });
            var variationCandidateAttrs = attrs.filter(function (attr) {
                var code = String((attr && attr.attribute_code) || '').toLowerCase();
                var input = String((attr && attr.frontend_input) || 'text').toLowerCase();
                var rawConfigurable = attr ? attr.is_configurable : undefined;
                var rawGlobal = attr ? attr.is_global : undefined;
                var hasConfigurableFlag = rawConfigurable !== undefined && rawConfigurable !== null && String(rawConfigurable).trim() !== '';
                var hasGlobalFlag = rawGlobal !== undefined && rawGlobal !== null && String(rawGlobal).trim() !== '';
                var isConfigurable = parseInt((attr && attr.is_configurable) || 0, 10) === 1;
                var isGlobal = parseInt((attr && attr.is_global) || 0, 10) === 1;
                if (!code || [
                    'sku',
                    'name',
                    'product_name',
                    'qty',
                    'quantity_and_stock_status',
                    'price',
                    'special_price',
                    'url_key',
                    'description',
                    'short_description',
                    'meta_title',
                    'meta_keywords',
                    'meta_description'
                ].indexOf(code) !== -1) {
                    return false;
                }
                if (['textarea', 'media_image', 'gallery', 'date'].indexOf(input) !== -1) {
                    return false;
                }
                if (input !== 'select') {
                    return false;
                }
                // Some environments do not expose these flags in matrix payload.
                // Enforce only when flags are actually present.
                if (hasConfigurableFlag && !isConfigurable) {
                    return false;
                }
                if (hasGlobalFlag && !isGlobal) {
                    return false;
                }
                return true;
            });
            var rows = group.rows || [];
            var inferredParentBySku = {};
            var inferredChildrenBySku = {};

            function variationExcludeReason(attr) {
                var code = String((attr && attr.attribute_code) || '').toLowerCase();
                var input = String((attr && attr.frontend_input) || 'text').toLowerCase();
                var rawConfigurable = attr ? attr.is_configurable : undefined;
                var rawGlobal = attr ? attr.is_global : undefined;
                var hasConfigurableFlag = rawConfigurable !== undefined && rawConfigurable !== null && String(rawConfigurable).trim() !== '';
                var hasGlobalFlag = rawGlobal !== undefined && rawGlobal !== null && String(rawGlobal).trim() !== '';
                var isConfigurable = parseInt((attr && attr.is_configurable) || 0, 10) === 1;
                var isGlobal = parseInt((attr && attr.is_global) || 0, 10) === 1;

                if (!code) {
                    return 'missing_code';
                }
                if ([
                    'sku',
                    'name',
                    'product_name',
                    'qty',
                    'quantity_and_stock_status',
                    'price',
                    'special_price',
                    'url_key',
                    'description',
                    'short_description',
                    'meta_title',
                    'meta_keywords',
                    'meta_description'
                ].indexOf(code) !== -1) {
                    return 'blacklisted_code';
                }
                if (['textarea', 'media_image', 'gallery', 'date'].indexOf(input) !== -1) {
                    return 'unsupported_input';
                }
                if (input !== 'select') {
                    return 'not_select_input';
                }
                if (hasConfigurableFlag && !isConfigurable) {
                    return 'not_configurable_flag';
                }
                if (hasGlobalFlag && !isGlobal) {
                    return 'not_global_flag';
                }
                return '';
            }

            function variationDebugHtml() {
                var bits = [];
                bits.push('<div style="margin-top:6px;padding:6px;border:1px dashed #ef4444;background:#fff7f7;color:#7f1d1d;font-size:11px;line-height:1.35;">');
                bits.push('<div><b>' + escapeHtml($t('Variation Debug')) + '</b></div>');
                bits.push('<div>' + escapeHtml($t('Total attrs:')) + ' ' + attrs.length + ', ' + escapeHtml($t('eligible:')) + ' ' + variationCandidateAttrs.length + '</div>');
                $.each(attrs, function (_, attr) {
                    var code = String((attr && attr.attribute_code) || '');
                    var label = String((attr && attr.frontend_label) || code || '-');
                    var input = String((attr && attr.frontend_input) || '');
                    var reason = variationExcludeReason(attr);
                    var isConfigurable = String((attr && attr.is_configurable) || 'n/a');
                    var isGlobal = String((attr && attr.is_global) || 'n/a');
                    bits.push('<div>- ' + escapeHtml(label) + ' [' + escapeHtml(code) + '] input=' + escapeHtml(input)
                        + ', is_configurable=' + escapeHtml(isConfigurable)
                        + ', is_global=' + escapeHtml(isGlobal)
                        + ', reason=' + escapeHtml(reason || 'eligible')
                        + '</div>');
                });
                bits.push('</div>');
                return bits.join('');
            }

            function variationEditorHtml(selectedCodes) {
                var selected = normalizeVariationAttributes(selectedCodes);
                var htmlBits = [];
                htmlBits.push('<div class="artm-variation-editor">');
                htmlBits.push('<div class="artm-variation-editor-label">' + escapeHtml($t('Variation Attributes')) + '</div>');

                if (!variationCandidateAttrs.length) {
                    htmlBits.push('<div class="artm-variation-empty">' + escapeHtml($t('No eligible attributes available')) + '</div>');
                    htmlBits.push(variationDebugHtml());
                } else {
                    htmlBits.push('<select class="admin__control-select artm-variation-select">');
                    htmlBits.push('<option value="">' + escapeHtml($t('Select attribute')) + '</option>');
                    $.each(variationCandidateAttrs, function (_, attr) {
                        var code = String((attr && attr.attribute_code) || '');
                        var label = String((attr && attr.frontend_label) || code);
                        var isDisabled = selected.indexOf(code) !== -1 ? ' disabled="disabled"' : '';
                        htmlBits.push('<option value="' + escapeHtml(code) + '"' + isDisabled + '>' + escapeHtml(label) + '</option>');
                    });
                    htmlBits.push('</select>');

                    htmlBits.push('<div class="artm-variation-tags">');
                    $.each(selected, function (_, code) {
                        var label = code;
                        $.each(variationCandidateAttrs, function (__, attr) {
                            if (String((attr && attr.attribute_code) || '') === code) {
                                label = String((attr && attr.frontend_label) || code);
                                return false;
                            }
                            return true;
                        });
                        htmlBits.push('<span class="artm-variation-tag" data-code="' + escapeHtml(code) + '">'
                            + '<span>' + escapeHtml(label) + '</span>'
                            + '<button type="button" class="artm-variation-remove" title="' + escapeHtml($t('Remove')) + '">&times;</button>'
                            + '</span>');
                    });
                    htmlBits.push('</div>');
                }

                htmlBits.push('</div>');
                return htmlBits.join('');
            }

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

            $.each(rows, function (_, r) {
                var sku = String((r && r.sku) || '').trim();
                var parent = String((r && r.parent) || '').trim();
                var rawChildList = parseSkuList((r && r.child) || '');

                if (!sku) {
                    return;
                }

                if (parent) {
                    inferredParentBySku[sku] = parent;
                    addInferredChild(parent, sku);
                }
                $.each(rawChildList, function (_, childSku) {
                    addInferredChild(sku, childSku);
                    if (!inferredParentBySku[childSku]) {
                        inferredParentBySku[childSku] = sku;
                    }
                });
            });

            var rowsBySku = {};
            $.each(rows, function (_, r) {
                var sku = String((r && r.sku) || '').trim();
                if (sku) {
                    rowsBySku[sku] = r;
                }
            });

            function getRowVariationAttributes(rowObj) {
                return normalizeVariationAttributes(rowObj && rowObj.variation_attributes);
            }

            function valueIsFilled(value) {
                return String(value == null ? '' : value).trim() !== '';
            }

            function rowHasVariationValues(rowObj, variationCodes) {
                var values = rowObj && rowObj.values ? rowObj.values : {};
                var allFilled = true;
                $.each(variationCodes || [], function (_, code) {
                    if (!valueIsFilled(values[code])) {
                        allFilled = false;
                        return false;
                    }
                    return true;
                });
                return allFilled;
            }

            function rowMeetsVariationRequirements(rowObj) {
                var rowSku = String((rowObj && rowObj.sku) || '').trim();
                var rowChildren = inferredChildrenBySku[rowSku] || [];
                var parentSku = String((rowObj && rowObj.parent) || inferredParentBySku[rowSku] || '').trim();
                var variationCodes = getRowVariationAttributes(rowObj);
                var allChildrenMeet = true;

                if (rowChildren.length > 0) {
                    if (!variationCodes.length) {
                        return false;
                    }

                    $.each(rowChildren, function (_, childSku) {
                        var childRow = rowsBySku[String(childSku || '').trim()];
                        if (!childRow || !rowHasAllRequired(childRow, requiredCodes) || !rowHasVariationValues(childRow, variationCodes)) {
                            allChildrenMeet = false;
                            return false;
                        }
                        return true;
                    });

                    return rowChildren.length > 0 && allChildrenMeet;
                }

                if (parentSku !== '') {
                    var parentRow = rowsBySku[parentSku];
                    if (!parentRow) {
                        return false;
                    }
                    variationCodes = getRowVariationAttributes(parentRow);
                    if (!variationCodes.length) {
                        return false;
                    }
                    return rowHasVariationValues(rowObj, variationCodes);
                }

                return true;
            }

            function isDraftRowReadyData(rowObj) {
                var rowSku = String((rowObj && rowObj.sku) || '').trim();
                var rowChildren = inferredChildrenBySku[rowSku] || [];

                // Configurable parent readiness is based on variation rules and child completeness.
                // Parent row should not be blocked by simple-only required fields (for example qty).
                if (rowChildren.length > 0) {
                    return rowMeetsVariationRequirements(rowObj);
                }

                return rowHasAllRequired(rowObj, requiredCodes) && rowMeetsVariationRequirements(rowObj);
            }

            var displayRows = [];
            var addedSkus = {};

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

            // Render each configurable row followed by all its children.
            $.each(rows, function (_, rowObj) {
                var sku = String((rowObj && rowObj.sku) || '').trim();
                if (!sku) {
                    return;
                }
                var hasChildren = !!(inferredChildrenBySku[sku] && inferredChildrenBySku[sku].length);
                if (!hasChildren) {
                    return;
                }

                pushDisplayRow(rowObj, false, '');

                $.each(inferredChildrenBySku[sku], function (_, childSku) {
                    var child = rowsBySku[childSku];
                    if (child) {
                        pushDisplayRow(child, true, sku);
                    }
                });
            });

            // Add remaining standalone/orphan rows.
            $.each(rows, function (_, rowObj) {
                pushDisplayRow(rowObj, false, '');
            });

            var hasNameAttribute = false;
            $.each(attrs, function (_, attr) {
                var code = String((attr && attr.attribute_code) || '').toLowerCase();
                if (code === 'name' || code === 'product_name') {
                    hasNameAttribute = true;
                    return false;
                }
                return true;
            });

            var fixedColumns = 3; // Select, SKU, Operation, Status
            if (!hasNameAttribute) {
                fixedColumns += 1; // Product Name
            }
            var tableColumnCount = 12 + (hasNameAttribute ? 0 : 1) + attrs.length + (disableDraftImages ? 0 : 1);
            var requiredCodes = [];
            $.each(attrs, function (_, attr) {
                if (attr.is_required) {
                    requiredCodes.push(attr.attribute_code);
                }
            });
            var requiredCodesStr = requiredCodes.join(',');

            html.push('<div class="admin__fieldset-wrapper" style="margin-bottom:16px;">');
            html.push('<div class="admin__fieldset-wrapper-title" style="display:flex;justify-content:space-between;align-items:center;gap:10px;">');
            html.push('<div style="display:flex;align-items:center;gap:8px;flex:1;">'
                + '<button type="button" class="action-secondary artm-move-selected-btn artm-requires-multi-select" style="display:none;"><span>' + escapeHtml($t('Move Selected To Ready')) + '</span></button>'
                + '<button type="button" class="action-secondary artm-delete-selected-btn artm-requires-multi-select" style="display:none;"><span>' + escapeHtml($t('Delete Selected')) + '</span></button>'
                + '<button type="button" class="action-secondary artm-move-completed-btn"><span>' + escapeHtml($t('Move All Completed products to ready')) + '</span></button>'
                + '</div>');
            html.push('<div style="flex:0 0 auto;text-align:center;">'
                + '<strong>' + escapeHtml(group.attribute_set_name || 'Unknown Attribute Set') + '</strong>'
                + '</div>');
            html.push('<div style="display:flex;align-items:center;gap:8px;flex:1;justify-content:flex-end;">'
                + '<button type="button" class="artm-collapse-groups-btn"><span style="display:inline-flex;align-items:center;gap:5px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 3 4 3 4 7"/><polyline points="16 3 20 3 20 7"/><polyline points="8 21 4 21 4 17"/><polyline points="16 21 20 21 20 17"/><line x1="8" y1="12" x2="16" y2="12"/></svg><span>' + escapeHtml($t('Collapse Relations')) + '</span></span></button>'
                + '<button type="button" class="artm-expand-groups-btn"><span style="display:inline-flex;align-items:center;gap:5px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 3 4 3 4 7"/><polyline points="16 3 20 3 20 7"/><polyline points="8 21 4 21 4 17"/><polyline points="16 21 20 21 20 17"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg><span>' + escapeHtml($t('Expand Relations')) + '</span></span></button>'
                + '<button type="button" class="artm-expand-table-btn" data-expanded="0"><span style="display:inline-flex;align-items:center;gap:5px;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg><span>' + escapeHtml($t('Expand')) + '</span></span></button>'
                + '<button type="button" class="artm-toggle-table-visibility-btn" data-collapsed="0" title="' + escapeHtml($t('Collapse table')) + '" style="font-size:11px;padding:4px 8px;border:1px solid #94a3b8;background:#f8fafc;border-radius:4px;cursor:pointer;line-height:1;">&#9650;</button>'
                + '</div>');
            html.push('</div>');
            html.push('<div class="admin__fieldset-wrapper-content artm-draft-table-wrap" style="overflow:auto;">');
            html.push('<table class="data-grid artm-draft-table" style="min-width:1200px;">');

            html.push('<thead><tr>');
            html.push('<th style="width:36px;"><input type="checkbox" class="artm-select-all-rows" title="' + escapeHtml($t('Select all')) + '" /></th>');
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
            if (!disableDraftImages) {
                html.push('<th>' + $t('Images') + '</th>');
            }
            html.push('<th>' + $t('Parent') + '</th>');
            html.push('<th>' + $t('Child') + '</th>');
            html.push('<th>' + $t('Uploaded/Drafted By') + '</th>');
            html.push('<th>' + $t('Ready By') + '</th>');
            html.push('<th>' + $t('Added By') + '</th>');
            html.push('<th class="artm-col-actions">' + $t('Actions') + '</th>');
            html.push('</tr>');

            html.push('<tr class="artm-search-row">');
            var searchColIndex = 0;
            html.push(searchInputHtml(searchColIndex, '', false));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, $t('Search SKU'), true));
            searchColIndex += 1;
            if (!hasNameAttribute) {
                html.push(searchInputHtml(searchColIndex, $t('Search Name'), true));
                searchColIndex += 1;
            }
            html.push(searchInputHtml(searchColIndex, $t('Search Operation'), true));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, $t('Search Status'), true));
            searchColIndex += 1;
            $.each(attrs, function () {
                html.push(searchInputHtml(searchColIndex, $t('Search'), true));
                searchColIndex += 1;
            });
            html.push(searchInputHtml(searchColIndex, $t('Search Source Qty'), true));
            searchColIndex += 1;
            if (!disableDraftImages) {
                html.push(searchInputHtml(searchColIndex, $t('Search Images'), true));
                searchColIndex += 1;
            }
            html.push(searchInputHtml(searchColIndex, $t('Search Parent'), true));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, $t('Search Child'), true));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, $t('Search Drafted By'), true));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, $t('Search Ready By'), true));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, $t('Search Added By'), true));
            searchColIndex += 1;
            html.push(searchInputHtml(searchColIndex, '', false));
            html.push('</tr></thead>');

            html.push('<tbody>');
            $.each(displayRows, function (_, entry) {
                var row = entry.row;
                var rowStatus = row.status || 'incomplete';
                var rowSku = String(row.sku || '').trim();
                var rowChildren = inferredChildrenBySku[rowSku] || [];
                var isParentRow = rowChildren.length > 0;
                var isChildRow = !!entry.isChild;
                var childParentSku = String(entry.parentSku || inferredParentBySku[rowSku] || '').trim();
                var isStandaloneRow = !isParentRow && !isChildRow;
                var isRowReadyForActions = isDraftRowReadyData(row);

                if (isParentRow && isRowReadyForActions) {
                    rowStatus = 'ready';
                }

                var rowReadyClass = isRowReadyForActions ? ' artm-row-ready' : '';
                var isLastChildOfGroup = false;
                if (isChildRow && childParentSku && inferredChildrenBySku[childParentSku] && inferredChildrenBySku[childParentSku].length) {
                    var lastChildSku = inferredChildrenBySku[childParentSku][inferredChildrenBySku[childParentSku].length - 1];
                    isLastChildOfGroup = String(lastChildSku || '').trim() === rowSku;
                }
                var moveDisplay = isRowReadyForActions ? '' : 'display:none;';

                // Group header row must be pushed BEFORE the main <tr data-id> is opened,
                // otherwise the browser auto-closes the data-id row (leaving it empty) and
                // wraps all <td> cells in a new implicit <tr> with no data-id.
                if (isParentRow) {
                    var groupRows = [row];
                    var groupMoveIds = [parseInt(row.entity_id, 10) || 0];
                    $.each(rowChildren, function (_, childSku) {
                        if (rowsBySku[childSku]) {
                            groupRows.push(rowsBySku[childSku]);
                            groupMoveIds.push(parseInt(rowsBySku[childSku].entity_id, 10) || 0);
                        }
                    });

                    groupMoveIds = groupMoveIds.filter(function (id) {
                        return id > 0;
                    });

                    var groupIncomplete = 0;
                    var groupOther = 0;
                    $.each(groupRows, function (idx, groupRow) {
                        var gStatus = isDraftRowReadyData(groupRow) ? 'ready' : String((groupRow && groupRow.status) || 'incomplete').toLowerCase();

                        if (gStatus === 'incomplete') {
                            groupIncomplete++;
                        } else if (gStatus !== 'ready') {
                            groupOther++;
                        }
                    });

                    var groupHeaderClass = 'artm-group-header-row' + (groupIncomplete === 0 && groupOther === 0 ? ' artm-group-header-row--ready' : '');
                    html.push('<tr class="' + groupHeaderClass + '" data-parent-sku="' + escapeHtml(rowSku) + '">'
                        + '<td colspan="' + tableColumnCount + '">'
                        + '<div class="artm-group-header-inner">'
                        + '<div class="artm-group-header-left">'
                        + '<button type="button" class="artm-group-header-toggle" data-parent-sku="' + escapeHtml(rowSku) + '" data-collapsed="0" title="' + escapeHtml($t('Collapse/Expand children')) + '">-</button>'
                        + '<strong>' + escapeHtml($t('Group')) + ': ' + escapeHtml(rowSku) + '</strong>'
                        + (isRowReadyForActions && groupMoveIds.length
                            ? '<button type="button" class="action-secondary artm-move-set artm-group-move-set" data-ids="' + escapeHtml(groupMoveIds.join(',')) + '"><span>' + escapeHtml($t('Move Group To Ready')) + '</span></button>'
                            : '')
                        + '<span class="artm-group-sub">(' + (groupRows.length) + ' ' + escapeHtml($t('rows')) + ')</span>'
                        + '</div>'
                        + '</div>'
                        + '</td>'
                        + '</tr>');
                }

                html.push('<tr data-id="' + parseInt(row.entity_id, 10) + '"'
                    + ' data-sku="' + escapeHtml(String(row.sku || '')) + '"'
                    + (isChildRow && childParentSku ? ' data-parent-sku="' + escapeHtml(childParentSku) + '"' : '')
                    + ' data-status="' + escapeHtml(rowStatus) + '"'
                    + ' data-required-codes="' + escapeHtml(requiredCodesStr) + '"'
                    + ' class="artm-draft-row' + rowReadyClass
                    + (isParentRow ? ' artm-row-configurable artm-row-block-start' : '')
                    + (isChildRow ? ' artm-row-child' : '')
                    + (isStandaloneRow ? ' artm-row-standalone artm-row-block-start artm-row-block-end' : '')
                    + (isLastChildOfGroup ? ' artm-row-block-end' : '')
                    + '">');

                // SKU display: show product type and parent relationship clearly
                var skuCell = '';
                var parentSku = String(row.parent || inferredParentBySku[rowSku] || '').trim();
                var childSkus = inferredChildrenBySku[rowSku] || [];
                var parentCellHtml = '';
                var childCellHtml = '';
                var skuListId = 'artm-rel-skus-' + parseInt(row.entity_id, 10);

                if (parentSku) {
                    // Child product under a configurable parent
                    skuCell = '<div><b>' + escapeHtml($t('Child')) + '</b></div>'
                            + '<div><span class="artm-live-sku">' + escapeHtml(row.sku || '') + '</span></div>';
                    parentCellHtml = escapeHtml(parentSku);
                    childCellHtml = escapeHtml(rowSku);
                } else if (childSkus.length > 0) {
                    // Configurable product
                    skuCell = '<div class="artm-configurable-sku-head' + (rowStatus === 'ready' ? ' artm-configurable-sku-head--ready' : '') + '" style="display:flex;align-items:center;gap:6px;">'
                        + '<b>Configurable</b>'
                        + '</div>'
                            + '<div class="artm-configurable-sku-value' + (rowStatus === 'ready' ? ' artm-configurable-sku-value--ready' : '') + '"><span class="artm-live-sku">' + escapeHtml(row.sku || '') + '</span></div>';

                    parentCellHtml = escapeHtml(rowSku);
                    childCellHtml = childSkus.map(function (childSku) {
                        return '<div>' + escapeHtml(childSku) + '</div>';
                    }).join('');
                } else {
                    // Standalone simple product
                    skuCell = isChildRow
                        ? '<div><b>' + escapeHtml($t('Child')) + '</b></div><div><span class="artm-live-sku">' + escapeHtml(row.sku || '') + '</span></div>'
                        : '<span class="artm-live-sku">' + escapeHtml(row.sku || '') + '</span>';
                }
                html.push('<td><input type="checkbox" class="artm-row-select" data-id="' + parseInt(row.entity_id, 10) + '" /></td>');
                html.push('<td class="artm-col-sku">'
                    + '<div class="artm-sku-cell-wrap" style="position:relative;display:block;padding-right:20px;">'
                    + skuCell
                    + '<button type="button" class="artm-copy-row-associated" title="' + escapeHtml($t('Copy this row values to associated products (excluding variation attributes)')) + '" style="position:absolute;top:2px;right:2px;display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border:1px solid #cbd5e1;border-radius:3px;background:#fff;color:#334155;cursor:pointer;line-height:1;font-size:11px;opacity:0;pointer-events:none;">⧉</button>'
                    + '</div>'
                    + '</td>');

                if (!hasNameAttribute) {
                    html.push('<td>' + escapeHtml(row.product_name || '') + '</td>');
                }
                html.push('<td>' + operationBadge(row.operation_type || 'create') + '</td>');
                html.push('<td class="artm-status-cell">' + escapeHtml(rowStatus) + '</td>');
                $.each(attrs, function (_, attr) {
                    var code = attr.attribute_code;
                    var label = attr.frontend_label || code;
                    var value = row.values && Object.prototype.hasOwnProperty.call(row.values, code) ? row.values[code] : '';
                    html.push('<td data-attr-code="' + escapeHtml(code) + '" data-attr-label="' + escapeHtml(label) + '">'
                        + '<div class="artm-attr-cell-wrap" style="position:relative;display:block;padding-right:20px;">'
                        + '<div style="min-width:0;">' + fieldHtml(attr, value, row.entity_id) + '</div>'
                        + '<button type="button" class="artm-copy-cell-value" title="' + escapeHtml($t('Copy this value to associated products')) + '" style="position:absolute;top:2px;right:2px;display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border:1px solid #cbd5e1;border-radius:3px;background:#fff;color:#334155;cursor:pointer;line-height:1;font-size:11px;opacity:0;pointer-events:none;">⧉</button>'
                        + '</div>'
                        + '</td>');
                });
                html.push('<td>' + (isParentRow
                    ? '<span class="artm-muted-cell">' + escapeHtml($t('N/A for configurable')) + '</span>'
                    : msiEditorHtml(row, group.msi_sources || [])) + '</td>');
                if (!disableDraftImages) {
                    html.push('<td class="artm-media-cell">' + mediaGalleryHtml(row) + '</td>');
                }
                html.push('<td>' + (isParentRow
                    ? variationEditorHtml(getRowVariationAttributes(row))
                    : parentEditorHtml(rowSku, String(row.parent || inferredParentBySku[rowSku] || '').trim(), skuListId)) + '</td>');
                html.push('<td>' + (isChildRow
                    ? '<span class="artm-muted-cell">' + escapeHtml($t('N/A — child product')) + '</span>'
                    : childEditorHtml(rowSku, childSkus.length ? childSkus : parseSkuList(row.child || ''), skuListId)) + '</td>');
                html.push('<td>' + escapeHtml(row.drafted_by || '-') + '</td>');
                html.push('<td>' + escapeHtml(row.ready_by || '-') + '</td>');
                html.push('<td>' + escapeHtml(row.added_by || '-') + '</td>');
                html.push('<td class="artm-col-actions">'
                    + '<div class="artm-action-btns">'
                    + '<button type="button" class="artm-icon-btn artm-icon-btn--danger artm-delete-row" title="' + escapeHtml($t('Delete')) + '">' 
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>'
                    + '</button>'
                    + '<button type="button" class="artm-icon-btn artm-icon-btn--view artm-view-product-btn" title="' + escapeHtml($t('View all fields')) + '">' 
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
                    + '</button>'
                    + '<button type="button" class="artm-icon-btn artm-save-row" title="' + escapeHtml($t('Save')) + '">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>'
                    + '</button>'
                    + '<button type="button" class="artm-icon-btn artm-icon-btn--ready artm-move-row-btn" style="' + moveDisplay + '" title="' + escapeHtml($t('Move to Ready')) + '">' 
                    + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
                    + '</button>'
                    + '</div>'
                    + '</td>');
                html.push('</tr>');
            });
            if (!rows.length) {
                html.push('<tr><td colspan="' + tableColumnCount + '" style="text-align:center;">' + $t('No products in this attribute set.') + '</td></tr>');
            }
            html.push('</tbody></table></div><div class="artm-draft-table-pager-host"></div></div>');

            return html.join('');
        }

        function bindActions() {
            bindSearchableSelectActions();

            function updateBulkSelectionButtons($table) {
                var $targetTable = $table && $table.length ? $table : $();
                var $wrapper;
                var selectedCount;
                var showSelectedActions;

                if (!$targetTable.length) {
                    return;
                }

                $wrapper = $targetTable.closest('.admin__fieldset-wrapper');
                selectedCount = $targetTable.find('.artm-row-select:checked').length;
                showSelectedActions = selectedCount >= 2;
                $wrapper.find('.artm-requires-multi-select').toggle(showSelectedActions);
            }

            function getCellSearchText($cell) {
                var bits = [];
                $cell.find('input,textarea,select').each(function () {
                    var $input = $(this);
                    var tag = String($input.prop('tagName') || '').toLowerCase();
                    if (tag === 'select') {
                        bits.push(String($input.find('option:selected').text() || ''));
                    } else {
                        bits.push(String($input.val() || ''));
                    }
                });
                bits.push(String($cell.text() || ''));
                return bits.join(' ').toLowerCase();
            }

            function applyTableColumnFilters($table) {
                if (!$table || !$table.length) {
                    return;
                }

                var filters = {};
                $table.find('.artm-column-search').each(function () {
                    var value = String($(this).val() || '').trim().toLowerCase();
                    var colIndex = parseInt($(this).attr('data-col-index'), 10);
                    if (!value || isNaN(colIndex)) {
                        return;
                    }
                    filters[colIndex] = value;
                });

                $table.find('tbody tr.artm-draft-row').each(function () {
                    var $row = $(this);
                    var matched = true;

                    $.each(filters, function (colIndex, term) {
                        var $cell = $row.children('td').eq(parseInt(colIndex, 10));
                        var haystack = getCellSearchText($cell);
                        if (haystack.indexOf(String(term || '')) === -1) {
                            matched = false;
                            return false;
                        }
                        return true;
                    });

                    $row.attr('data-filter-hidden', matched ? '0' : '1');
                });

                $table.data('artmCurrentPage', 1);
            }

            function hasUnsavedRows() {
                return $root.find('.artm-draft-row[data-dirty="1"]').length > 0;
            }

            function confirmLeaveWithUnsaved() {
                if (!hasUnsavedRows()) {
                    return true;
                }
                return window.confirm($t('You have unsaved changes in draft rows. Please save before leaving. Continue anyway?'));
            }

            $(window).off('beforeunload.artmDraftUnsaved').on('beforeunload.artmDraftUnsaved', function (e) {
                if (!hasUnsavedRows()) {
                    return undefined;
                }
                var msg = $t('You have unsaved changes in draft rows.');
                e.preventDefault();
                e.returnValue = msg;
                return msg;
            });

            $(document).off('click.artmDraftUnsavedNav').on('click.artmDraftUnsavedNav', '.artm-parent-child-tab, a[href]', function (e) {
                var $el = $(this);
                var href = String($el.attr('href') || '');
                if ($el.is('a') && (!href || href === '#' || href.indexOf('javascript:') === 0)) {
                    return;
                }
                if (!confirmLeaveWithUnsaved()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
                return true;
            });

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
                var $pagerHost = $wrapper.children('.artm-draft-table-pager-host');

                if (collapsed) {
                    $content.show();
                    $pagerHost.show();
                    $btn.attr('data-collapsed', '0').attr('title', $t('Collapse table')).html('&#9650;');
                } else {
                    $content.hide();
                    $pagerHost.hide();
                    $btn.attr('data-collapsed', '1').attr('title', $t('Expand table')).html('&#9660;');
                }
            });

            function setParentChildrenVisibility($table, parentSku, collapse) {
                var $parentRows = $table.find('tr.artm-row-configurable[data-sku="' + parentSku + '"]');
                var $headerToggle = $table.find('tr.artm-group-header-row[data-parent-sku="' + parentSku + '"] .artm-group-header-toggle').first();
                var $childRows = $table.find('tr.artm-row-child[data-parent-sku="' + parentSku + '"]');

                if (collapse) {
                    $childRows.hide();
                    $headerToggle.attr('data-collapsed', '1').text('+');
                } else {
                    $childRows.show();
                    $headerToggle.attr('data-collapsed', '0').text('-');
                }
            }

            function buildGroupMoveButtonHtml(ids) {
                return '<button type="button" class="action-secondary artm-move-set artm-group-move-set" data-ids="'
                    + escapeHtml(ids.join(','))
                    + '"><span>' + escapeHtml($t('Move Group To Ready')) + '</span></button>';
            }

            function syncConfigurableGroupState($table, parentSku) {
                var sku = String(parentSku || '').trim();
                var $parentRow;
                var $groupRow;
                var $childRows;
                var parentIsMoved;
                var allChildrenReady;
                var ids = [];
                var $groupLeft;
                var $groupMoveBtn;
                var $groupSub;

                if (!$table || !$table.length || !sku) {
                    return;
                }

                $parentRow = $table.find('tr.artm-row-configurable[data-sku="' + sku + '"]').first();
                $groupRow = $table.find('tr.artm-group-header-row[data-parent-sku="' + sku + '"]').first();
                $childRows = $table.find('tr.artm-row-child[data-parent-sku="' + sku + '"]');

                if (!$parentRow.length || !$groupRow.length || !$childRows.length) {
                    return;
                }

                parentIsMoved = String($parentRow.attr('data-moved') || '0') === '1';
                allChildrenReady = !parentIsMoved
                    && collectVariationAttributes($parentRow).length > 0
                    && $childRows.length > 0
                    && $childRows.filter(function () {
                    var $row = $(this);
                    return String($row.attr('data-moved') || '0') === '1' || !$row.hasClass('artm-row-ready');
                }).length === 0;

                $parentRow.toggleClass('artm-row-ready', allChildrenReady);
                if (allChildrenReady) {
                    $parentRow.attr('data-status', 'ready');
                }

                $parentRow.find('.artm-configurable-sku-head').toggleClass('artm-configurable-sku-head--ready', allChildrenReady);
                $parentRow.find('.artm-configurable-sku-value').toggleClass('artm-configurable-sku-value--ready', allChildrenReady);

                $groupRow.toggleClass('artm-group-header-row--ready', allChildrenReady);

                $groupLeft = $groupRow.find('.artm-group-header-left').first();
                $groupMoveBtn = $groupLeft.find('.artm-group-move-set').first();
                $groupSub = $groupLeft.find('.artm-group-sub').first();

                if (!allChildrenReady) {
                    $groupMoveBtn.remove();
                    return;
                }

                ids.push(parseInt($parentRow.data('id'), 10) || 0);
                $childRows.each(function () {
                    ids.push(parseInt($(this).data('id'), 10) || 0);
                });
                ids = ids.filter(function (id) {
                    return id > 0;
                });

                if (!ids.length) {
                    $groupMoveBtn.remove();
                    return;
                }

                if ($groupMoveBtn.length) {
                    $groupMoveBtn.attr('data-ids', ids.join(','));
                    return;
                }

                if ($groupSub.length) {
                    $(buildGroupMoveButtonHtml(ids)).insertBefore($groupSub);
                } else {
                    $groupLeft.append(buildGroupMoveButtonHtml(ids));
                }
            }

            function syncRelatedConfigurableGroupState($tr) {
                var $table = $tr.closest('table.artm-draft-table');
                var parentSku = String($tr.attr('data-parent-sku') || '').trim();

                if (parentSku) {
                    syncConfigurableGroupState($table, parentSku);
                    return;
                }

                if ($tr.hasClass('artm-row-configurable')) {
                    syncConfigurableGroupState($table, String($tr.attr('data-sku') || '').trim());
                }
            }

            $('.artm-group-header-toggle').off('click').on('click', function () {
                var $btn = $(this);
                var parentSku = String($btn.data('parentSku') || '').trim();
                if (!parentSku) {
                    return;
                }
                var collapsed = String($btn.attr('data-collapsed') || '0') === '1';
                setParentChildrenVisibility($btn.closest('table'), parentSku, !collapsed);
            });

            $('.artm-collapse-groups-btn').off('click').on('click', function () {
                var $table = $(this).closest('.admin__fieldset-wrapper').find('table.artm-draft-table').first();
                $table.find('tr.artm-row-configurable').each(function () {
                    var parentSku = String($(this).data('sku') || '').trim();
                    if (parentSku) {
                        setParentChildrenVisibility($table, parentSku, true);
                    }
                });
            });

            $('.artm-expand-groups-btn').off('click').on('click', function () {
                var $table = $(this).closest('.admin__fieldset-wrapper').find('table.artm-draft-table').first();
                $table.find('tr.artm-row-configurable').each(function () {
                    var parentSku = String($(this).data('sku') || '').trim();
                    if (parentSku) {
                        setParentChildrenVisibility($table, parentSku, false);
                    }
                });
            });

            function saveIconHtml() {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>';
            }

            function savingIconHtml() {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" opacity="0.35"/><path d="M12 3a9 9 0 0 1 9 9"/></svg>';
            }

            function savedIconHtml() {
                return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            }

            function markRowDirty($tr) {
                $tr.attr('data-dirty', '1');
                $tr.find('.artm-col-actions').addClass('artm-row-dirty');
                var $saveBtn = $tr.find('.artm-save-row').first();
                if ($saveBtn.length && !$saveBtn.hasClass('artm-icon-btn--saving')) {
                    $saveBtn.removeClass('artm-icon-btn--saved');
                    $saveBtn.attr('title', $t('Unsaved changes - click Save'));
                }
            }

            function clearRowDirty($tr) {
                $tr.attr('data-dirty', '0');
                $tr.find('.artm-col-actions').removeClass('artm-row-dirty');
            }

            function markRowMovedToReady($tr) {
                if (!$tr || !$tr.length) {
                    return;
                }
                clearRowDirty($tr);
                $tr.attr('data-moved', '1').addClass('artm-row-moved-ready');
                $tr.find('input,select,textarea,button').prop('disabled', true);
                $tr.find('.artm-action-btns').html('<span class="artm-row-moved-label">' + escapeHtml($t('Moved to Ready')) + '</span>');
                $tr.find('.artm-status-cell').text('moved_to_ready');
                syncRelatedConfigurableGroupState($tr);
            }

            function setSaveButtonState($btn, state) {
                if (!$btn || !$btn.length) {
                    return;
                }

                if (state === 'saving') {
                    $btn.removeClass('artm-icon-btn--saved').addClass('artm-icon-btn--saving');
                    $btn.prop('disabled', true);
                    $btn.attr('title', $t('Saving...'));
                    $btn.html(savingIconHtml());
                    return;
                }

                if (state === 'saved') {
                    $btn.removeClass('artm-icon-btn--saving').addClass('artm-icon-btn--saved');
                    $btn.prop('disabled', false);
                    $btn.attr('title', $t('Saved'));
                    $btn.html(savedIconHtml());
                    setTimeout(function () {
                        if (!$btn.hasClass('artm-icon-btn--saving')) {
                            $btn.removeClass('artm-icon-btn--saved');
                            $btn.attr('title', $t('Save'));
                            $btn.html(saveIconHtml());
                        }
                    }, 1200);
                    return;
                }

                $btn.removeClass('artm-icon-btn--saving artm-icon-btn--saved');
                $btn.prop('disabled', false);
                $btn.attr('title', $t('Save'));
                $btn.html(saveIconHtml());
            }

            function persistRowNow($tr) {
                var draftId = parseInt($tr.attr('data-id'), 10) || 0;
                var $saveBtn;

                console.log('[ARTM DEBUG] persistRowNow called. draftId=', draftId, '$tr.attr(data-id)=', $tr.attr('data-id'));

                if (draftId <= 0) {
                    console.log('[ARTM DEBUG] persistRowNow: draftId is 0/invalid, aborting.');
                    showMessage('error', $t('Invalid draft row.'));
                    return;
                }

                $saveBtn = $tr.find('.artm-save-row').first();
                console.log('[ARTM DEBUG] persistRowNow: .artm-save-row found?', $saveBtn.length);
                if ($saveBtn.length) {
                    saveDraftRow($tr, $saveBtn);
                    return;
                }

                console.log('[ARTM DEBUG] persistRowNow: no save btn found, falling back to scheduleAutoSave');
                scheduleAutoSave($tr, 0);
            }

            function buildRowSavePayload($tr) {
                var values = {};
                var knownMsiSources = {};
                var usedMsiSources = {};
                var parentSku;
                var childSkus;
                var variationAttributes;

                $tr.find('.artm-attr-input').each(function () {
                    var code = String($(this).data('code') || '').trim();
                    if (!code) {
                        return;
                    }
                    values[code] = $(this).val();
                });

                if (Object.prototype.hasOwnProperty.call(values, 'sku') && !validateDraftSkuUniqueness($tr, values.sku)) {
                    return null;
                }

                $tr.find('.artm-msi-source-select option').each(function () {
                    var sourceCode = String($(this).val() || '');
                    if (sourceCode) {
                        knownMsiSources[sourceCode] = true;
                    }
                });

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

                parentSku = String($tr.find('.artm-parent-input').val() || '').trim();
                childSkus = collectChildSkus($tr);
                variationAttributes = collectVariationAttributes($tr);

                return {
                    values: values,
                    parent_sku: parentSku,
                    child_skus: childSkus,
                    variation_attributes: variationAttributes
                };
            }

            if (!disableDraftImages && !$('#artm-media-file-input').length) {
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
                    $(this).val('');
                });
            }

            function bindImageActions() {
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

                $('.artm-mi-del').off('click').on('click', function () {
                    var $btn = $(this);
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

                $('.artm-role-btn').off('click').on('click', function () {
                    var $btn = $(this);
                    var mediaId = parseInt($btn.data('media-id'), 10) || 0;
                    var role = String($btn.data('role') || '');
                    if (!mediaId || !role) {
                        return;
                    }

                    var $item = $btn.closest('.artm-media-item');
                    var $gallery = $item.closest('.artm-media-gallery');
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
                        $btn.toggleClass('artm-role-active');
                    });
                });
            }

            if (!disableDraftImages) {
                bindImageActions();
            }

            $('.artm-msi-add').off('click').on('click', function () {
                var $wrap = $(this).closest('.artm-msi-wrap');
                var $tr = $(this).closest('tr');
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

                markRowDirty($tr);
            });

            $('.artm-msi-remove').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                $(this).closest('.artm-msi-item').remove();
                markRowDirty($tr);
            });

            $('.artm-draft-table').off('input.artmDirty change.artmDirty', '.artm-msi-qty, .artm-msi-source-select, .artm-msi-add-qty').on('input.artmDirty change.artmDirty', '.artm-msi-qty, .artm-msi-source-select, .artm-msi-add-qty', function () {
                markRowDirty($(this).closest('tr'));
            });

            $('.artm-category-picker-link').off('click').on('click', function (e) {
                e.preventDefault();
                var $input = $(this).closest('.artm-category-field').find('.artm-category-input');
                if (!$input.length) {
                    return;
                }
                openCategoryModal($input);
            });

            function collectChildSkus($tr) {
                var seen = {};
                var list = [];
                $tr.find('.artm-child-tag').each(function () {
                    var sku = String($(this).data('sku') || '').trim();
                    if (!sku || seen[sku]) {
                        return;
                    }
                    seen[sku] = true;
                    list.push(sku);
                });
                return list;
            }

            function removeChildTagBySku($tr, childSku) {
                var cleanSku = String(childSku || '').trim();
                var removed = false;

                if (!cleanSku) {
                    return removed;
                }

                $tr.find('.artm-child-tag').each(function () {
                    if (String($(this).data('sku') || '').trim() === cleanSku) {
                        $(this).remove();
                        removed = true;
                    }
                });

                return removed;
            }

            function setParentInputValue($tr, parentSku) {
                var cleanParentSku = String(parentSku || '').trim();
                var $parentInput = $tr.find('.artm-parent-input').first();
                var $parentSelect = $tr.find('.artm-parent-select').first();

                $tr.attr('data-parent-sku', cleanParentSku);
                if ($parentInput.length) {
                    $parentInput.val(cleanParentSku);
                }
                if ($parentSelect.length) {
                    $parentSelect.val(cleanParentSku);
                }
            }

            function replaceChildTagSku($tr, oldSku, newSku) {
                var cleanOldSku = String(oldSku || '').trim();
                var cleanNewSku = String(newSku || '').trim();
                var updated = false;

                if (!cleanOldSku || !cleanNewSku || cleanOldSku === cleanNewSku) {
                    return updated;
                }

                $tr.find('.artm-child-tag').each(function () {
                    var $tag = $(this);
                    if (String($tag.data('sku') || '').trim() !== cleanOldSku) {
                        return;
                    }

                    $tag.attr('data-sku', cleanNewSku).data('sku', cleanNewSku);
                    $tag.find('span').first().text(cleanNewSku);
                    updated = true;
                });

                return updated;
            }

            function propagateSkuRenameReferences($tr, oldSku, newSku) {
                var cleanOldSku = String(oldSku || '').trim();
                var cleanNewSku = String(newSku || '').trim();
                var $table = $tr.closest('table.artm-draft-table');

                if (!cleanOldSku || !cleanNewSku || cleanOldSku === cleanNewSku || !$table.length) {
                    return;
                }

                $table.find('tr.artm-draft-row').each(function () {
                    var $row = $(this);
                    var rowChanged = false;

                    if ($row.is($tr)) {
                        return;
                    }

                    if (String($row.attr('data-parent-sku') || '').trim() === cleanOldSku) {
                        $row.attr('data-parent-sku', cleanNewSku);
                        setParentInputValue($row, cleanNewSku);
                        rowChanged = true;
                    }

                    if (String($row.find('.artm-parent-input').val() || '').trim() === cleanOldSku) {
                        setParentInputValue($row, cleanNewSku);
                        rowChanged = true;
                    }

                    if (replaceChildTagSku($row, cleanOldSku, cleanNewSku)) {
                        rowChanged = true;
                    }

                    if (rowChanged) {
                        markRowDirty($row);
                        scheduleAutoSave($row, 150);
                    }
                });

                $table.find('tr.artm-group-header-row').each(function () {
                    var $groupRow = $(this);
                    if (String($groupRow.attr('data-parent-sku') || '').trim() !== cleanOldSku) {
                        return;
                    }

                    $groupRow.attr('data-parent-sku', cleanNewSku);
                    $groupRow.find('.artm-group-header-toggle').attr('data-parent-sku', cleanNewSku).data('parentSku', cleanNewSku);
                    $groupRow.find('.artm-group-header-left strong').first().text($t('Group') + ': ' + cleanNewSku);
                });
            }

            function syncParentChildEditors($sourceTr, relationType, relatedSku) {
                var cleanRelatedSku = String(relatedSku || '').trim();
                var sourceSku = String($sourceTr.data('sku') || '').trim();

                if (!sourceSku) {
                    return;
                }

                if (relationType === 'child') {
                    $('.artm-draft-row').each(function () {
                        var $row = $(this);
                        var rowSku = String($row.data('sku') || '').trim();

                        if (!rowSku || rowSku === sourceSku) {
                            return;
                        }

                        if (rowSku !== cleanRelatedSku) {
                            if (removeChildTagBySku($row, sourceSku)) {
                                markRowDirty($row);
                                persistRowNow($row);
                            }
                            return;
                        }

                        if (!$row.find('.artm-child-tag').filter(function () {
                            return String($(this).data('sku') || '').trim() === sourceSku;
                        }).length) {
                            $row.find('.artm-child-tags').append('<span class="artm-child-tag" data-sku="' + escapeHtml(sourceSku) + '"><span>' + escapeHtml(sourceSku) + '</span><button type="button" class="artm-child-remove">&times;</button></span>');
                            markRowDirty($row);
                            persistRowNow($row);
                        }
                    });
                    return;
                }

                if (relationType === 'parent') {
                    $('.artm-draft-row').each(function () {
                        var $row = $(this);
                        var rowSku = String($row.data('sku') || '').trim();

                        if (!rowSku || rowSku !== cleanRelatedSku) {
                            return;
                        }

                        setParentInputValue($row, sourceSku);
                        markRowDirty($row);
                        persistRowNow($row);
                    });
                }
            }

            function addChildTag($tr, sku) {
                var cleanSku = String(sku || '').trim();
                var rowSku = String($tr.data('sku') || '').trim();
                var isConfigurableChild = false;
                if (!cleanSku) {
                    return;
                }
                if (rowSku && cleanSku === rowSku) {
                    showMessage('error', $t('A product cannot be its own child.'));
                    return;
                }

                $('.artm-draft-row').each(function () {
                    var $candidateRow = $(this);
                    var candidateSku = String($candidateRow.attr('data-sku') || '').trim();

                    if (!candidateSku || candidateSku !== cleanSku) {
                        return true;
                    }

                    if ($candidateRow.hasClass('artm-row-configurable') || collectChildSkus($candidateRow).length > 0) {
                        isConfigurableChild = true;
                    }

                    return false;
                });

                if (isConfigurableChild) {
                    showMessage('error', $t('Configurable SKU cannot be added as a child product.'));
                    return;
                }

                var exists = false;
                $tr.find('.artm-child-tag').each(function () {
                    if (String($(this).data('sku') || '').trim() === cleanSku) {
                        exists = true;
                        return false;
                    }
                    return true;
                });
                if (exists) {
                    return;
                }

                var tagHtml = '<span class="artm-child-tag" data-sku="' + escapeHtml(cleanSku) + '">'
                    + '<span>' + escapeHtml(cleanSku) + '</span>'
                    + '<button type="button" class="artm-child-remove">&times;</button>'
                    + '</span>';
                $tr.find('.artm-child-tags').append(tagHtml);
                syncParentChildEditors($tr, 'parent', cleanSku);
                markRowDirty($tr);
                persistRowNow($tr);
            }

            $('.artm-parent-select').off('change').on('change', function () {
                var $tr = $(this).closest('tr');
                var value = String($(this).val() || '').trim();
                $tr.find('.artm-parent-input').val(value);
                syncParentChildEditors($tr, 'child', value);
                markRowDirty($tr);
            });

            $('.artm-draft-table').off('input.artmRelParent', '.artm-parent-input').on('input.artmRelParent', '.artm-parent-input', function () {
                markRowDirty($(this).closest('tr'));
            });

            $('.artm-draft-table').off('change.artmRelParentSync blur.artmRelParentSync', '.artm-parent-input').on('change.artmRelParentSync blur.artmRelParentSync', '.artm-parent-input', function () {
                var $tr = $(this).closest('tr');
                syncParentChildEditors($tr, 'child', $(this).val());
            });

            $('.artm-child-add').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                var $input = $tr.find('.artm-child-input').first();
                var sku = String($input.val() || '').trim();
                addChildTag($tr, sku);
                $input.val('');
            });

            $('.artm-child-select').off('change').on('change', function () {
                var $tr = $(this).closest('tr');
                var sku = String($(this).val() || '').trim();
                addChildTag($tr, sku);
                $(this).val('');
            });

            $('.artm-draft-table').off('keydown.artmRelChild', '.artm-child-input').on('keydown.artmRelChild', '.artm-child-input', function (e) {
                if (e.key !== 'Enter') {
                    return;
                }
                e.preventDefault();
                var $tr = $(this).closest('tr');
                addChildTag($tr, $(this).val());
                $(this).val('');
            });

            $('.artm-draft-table').off('click.artmRelChildRemove', '.artm-child-remove').on('click.artmRelChildRemove', '.artm-child-remove', function () {
                var $tr = $(this).closest('tr');
                var $tag = $(this).closest('.artm-child-tag');
                var removedSku = String($tag.data('sku') || '').trim();
                var parentSku = String($tr.data('sku') || '').trim();

                $tag.remove();

                if (removedSku && parentSku) {
                    $('.artm-draft-row').each(function () {
                        var $childRow = $(this);
                        var childRowSku = String($childRow.data('sku') || '').trim();

                        if (!childRowSku || childRowSku !== removedSku) {
                            return;
                        }

                        if (String($childRow.find('.artm-parent-input').val() || '').trim() !== parentSku) {
                            return;
                        }

                        setParentInputValue($childRow, '');
                        markRowDirty($childRow);
                        checkRowCompleteness($childRow);
                        persistRowNow($childRow);
                    });
                }

                markRowDirty($tr);
                checkRowCompleteness($tr);
                persistRowNow($tr);
            });

            function updateLiveSkuInRow($tr) {
                var $skuInput = $tr.find('.artm-attr-input[data-code="sku"]').first();
                var oldSku = String($tr.attr('data-sku') || '').trim();
                if (!$skuInput.length) {
                    return;
                }
                var newSku = String($skuInput.val() || '').trim();
                if (!newSku) {
                    return;
                }
                propagateSkuRenameReferences($tr, oldSku, newSku);
                $tr.attr('data-sku', newSku);
                $tr.find('.artm-live-sku').text(newSku);
            }

            function getAssociatedRelationRows($tr) {
                var $table = $tr.closest('table.artm-draft-table');
                var rowSku = String($tr.attr('data-sku') || '').trim();
                var parentSku = '';
                var $groupRows = $();

                if (!$table.length) {
                    return $tr;
                }

                if ($tr.hasClass('artm-row-configurable')) {
                    parentSku = rowSku;
                } else {
                    parentSku = String($tr.attr('data-parent-sku') || '').trim();
                    if (!parentSku) {
                        parentSku = String($tr.find('.artm-parent-input').val() || '').trim();
                    }
                }

                if (!parentSku && rowSku && $tr.find('.artm-child-tag').length) {
                    parentSku = rowSku;
                }

                if (!parentSku) {
                    return $tr;
                }

                $groupRows = $groupRows.add($table.find('tr.artm-draft-row').filter(function () {
                    return String($(this).attr('data-sku') || '').trim() === parentSku;
                }).first());

                $groupRows = $groupRows.add($table.find('tr.artm-draft-row').filter(function () {
                    return String($(this).attr('data-parent-sku') || '').trim() === parentSku;
                }));

                if (!$groupRows.length) {
                    return $tr;
                }
                if (!$groupRows.filter($tr).length) {
                    $groupRows = $groupRows.add($tr);
                }

                return $groupRows;
            }

            function copyAttrValueToAssociatedRows($sourceRow, attrCode, value) {
                var $rows = getAssociatedRelationRows($sourceRow);
                var copiedCount = 0;
                var code = String(attrCode || '').trim();

                if (!code) {
                    return 0;
                }

                $rows.each(function () {
                    var $row = $(this);
                    var $targetInput = $row.find('td[data-attr-code="' + code + '"] .artm-attr-input[data-code="' + code + '"]').first();

                    if (!$targetInput.length) {
                        return;
                    }

                    $targetInput.val(value);
                    syncSearchableInputFromSelect($targetInput);
                    $targetInput.trigger('input');
                    $targetInput.trigger('change');
                    markRowDirty($row);
                    checkRowCompleteness($row);
                    persistRowNow($row);
                    copiedCount += 1;
                });

                return copiedCount;
            }

            function getVariationCodesForAssociatedGroup($sourceRow) {
                var $table = $sourceRow.closest('table.artm-draft-table');
                var rowSku = String($sourceRow.attr('data-sku') || '').trim();
                var parentSku = '';
                var $parentRow = $();

                if ($sourceRow.hasClass('artm-row-configurable')) {
                    return normalizeVariationAttributes(collectVariationAttributes($sourceRow));
                }

                parentSku = String($sourceRow.attr('data-parent-sku') || '').trim();
                if (!parentSku) {
                    parentSku = String($sourceRow.find('.artm-parent-input').val() || '').trim();
                }
                if (!parentSku && rowSku && $sourceRow.find('.artm-child-tag').length) {
                    parentSku = rowSku;
                }

                if (!parentSku || !$table.length) {
                    return [];
                }

                $parentRow = $table.find('tr.artm-row-configurable').filter(function () {
                    return String($(this).attr('data-sku') || '').trim() === parentSku;
                }).first();

                if (!$parentRow.length) {
                    return [];
                }

                return normalizeVariationAttributes(collectVariationAttributes($parentRow));
            }

            function copyRowValuesToAssociatedRows($sourceRow) {
                var $rows = getAssociatedRelationRows($sourceRow);
                var variationCodes = getVariationCodesForAssociatedGroup($sourceRow);
                var variationMap = {};
                var sourceValues = {};
                var updatedRows = 0;
                var fieldCount = 0;

                $.each(variationCodes, function (_, code) {
                    variationMap[String(code || '').trim()] = true;
                });

                $sourceRow.find('.artm-attr-input').each(function () {
                    var $input = $(this);
                    var code = String($input.data('code') || '').trim();

                    if (!code || code === 'sku' || variationMap[code]) {
                        return;
                    }
                    if (Object.prototype.hasOwnProperty.call(sourceValues, code)) {
                        return;
                    }

                    sourceValues[code] = $input.val();
                    fieldCount += 1;
                });

                if (!fieldCount) {
                    return { rows: 0, fields: 0 };
                }

                $rows.each(function () {
                    var $row = $(this);
                    var rowChanged = false;

                    $.each(sourceValues, function (code, val) {
                        var $targetInput = $row.find('td[data-attr-code="' + code + '"] .artm-attr-input[data-code="' + code + '"]').first();
                        if (!$targetInput.length) {
                            return;
                        }
                        $targetInput.val(val);
                        syncSearchableInputFromSelect($targetInput);
                        $targetInput.trigger('input');
                        $targetInput.trigger('change');
                        rowChanged = true;
                    });

                    if (rowChanged) {
                        markRowDirty($row);
                        checkRowCompleteness($row);
                        persistRowNow($row);
                        updatedRows += 1;
                    }
                });

                return { rows: updatedRows, fields: fieldCount };
            }

            function saveDraftRow($tr, $btn) {
                var draftId = parseInt($tr.attr('data-id'), 10) || 0;
                var payload;
                var savingStartedAt = parseInt($tr.attr('data-saving-at'), 10) || 0;

                console.log('[ARTM DEBUG] saveDraftRow called. draftId=', draftId, 'saving=', $tr.data('saving'), 'rowSaveUrl=', config.rowSaveUrl);

                if (draftId <= 0) {
                    console.log('[ARTM DEBUG] saveDraftRow: draftId invalid, aborting.');
                    showMessage('error', $t('Invalid draft row.'));
                    return;
                }

                if ($tr.data('saving') === 1) {
                    if (savingStartedAt > 0 && (Date.now() - savingStartedAt) > 5000) {
                        console.log('[ARTM DEBUG] saveDraftRow: stale saving flag, resetting.');
                        $tr.data('saving', 0);
                        $tr.removeAttr('data-saving-at');
                    } else {
                        console.log('[ARTM DEBUG] saveDraftRow: already saving, queuing pendingSave.');
                        $tr.data('pendingSave', 1);
                        return;
                    }
                }

                try {
                    payload = buildRowSavePayload($tr);
                } catch (e) {
                    setSaveButtonState($btn, 'default');
                    showMessage('error', e && e.message ? e.message : $t('Unable to prepare row save.'));
                    return;
                }

                if (!payload) {
                    setSaveButtonState($btn, 'default');
                    return;
                }

                if (!config.rowSaveUrl) {
                    console.log('[ARTM DEBUG] saveDraftRow: rowSaveUrl missing!');
                    setSaveButtonState($btn, 'default');
                    showMessage('error', $t('Row save URL is missing.'));
                    return;
                }

                console.log('[ARTM DEBUG] saveDraftRow: FIRING AJAX to', config.rowSaveUrl, 'payload=', JSON.stringify(payload));
                $tr.data('saving', 1);
                $tr.attr('data-saving-at', String(Date.now()));
                setSaveButtonState($btn, 'saving');

                try {
                    $.ajax({
                        url: config.rowSaveUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            form_key: window.FORM_KEY || '',
                            draft_id: draftId,
                            values: payload.values,
                            parent_sku: payload.parent_sku,
                            child_skus: payload.child_skus,
                            variation_attributes: payload.variation_attributes
                        }
                    }).done(function (resp) {
                        if (!resp || !resp.success) {
                            setSaveButtonState($btn, 'default');
                            showMessage('error', resp && resp.message ? resp.message : $t('Unable to save row.'));
                            return;
                        }
                        var newStatus = String(resp.status || '').trim();
                        if (newStatus) {
                            $tr.attr('data-status', newStatus);
                            $tr.find('.artm-status-cell').text(newStatus);
                        }
                        updateLiveSkuInRow($tr);
                        clearRowDirty($tr);
                        checkRowCompleteness($tr);
                        setSaveButtonState($btn, 'saved');
                        if ($btn && $btn.length) {
                            showMessage('success', resp.message || $t('Draft row saved successfully.'));
                        }
                    }).fail(function () {
                        setSaveButtonState($btn, 'default');
                        showMessage('error', $t('Unable to save row.'));
                    }).always(function () {
                        $tr.data('saving', 0);
                        $tr.removeAttr('data-saving-at');
                        if ($tr.data('pendingSave') === 1) {
                            $tr.data('pendingSave', 0);
                            saveDraftRow($tr, $btn);
                        }
                    });

                    $('.artm-draft-table').off('mouseenter.artmCopyHover mouseleave.artmCopyHover', 'td[data-attr-code]').on('mouseenter.artmCopyHover', 'td[data-attr-code]', function () {
                        $(this).find('.artm-copy-cell-value').css({ opacity: 1, 'pointer-events': 'auto' });
                    }).on('mouseleave.artmCopyHover', 'td[data-attr-code]', function () {
                        $(this).find('.artm-copy-cell-value').css({ opacity: 0, 'pointer-events': 'none' });
                    });

                    $('.artm-draft-table').off('mouseenter.artmCopyRowHover mouseleave.artmCopyRowHover', 'tr.artm-draft-row td.artm-col-sku').on('mouseenter.artmCopyRowHover', 'tr.artm-draft-row td.artm-col-sku', function () {
                        $(this).find('.artm-copy-row-associated').css({ opacity: 1, 'pointer-events': 'auto' });
                    }).on('mouseleave.artmCopyRowHover', 'tr.artm-draft-row td.artm-col-sku', function () {
                        $(this).find('.artm-copy-row-associated').css({ opacity: 0, 'pointer-events': 'none' });
                    });

                    $('.artm-draft-table').off('click.artmCopyCellValue', '.artm-copy-cell-value').on('click.artmCopyCellValue', '.artm-copy-cell-value', function (e) {
                        var $btn = $(this);
                        var $td = $btn.closest('td[data-attr-code]');
                        var $tr = $btn.closest('tr.artm-draft-row');
                        var attrCode = String($td.attr('data-attr-code') || '').trim();
                        var $input = $td.find('.artm-attr-input[data-code="' + attrCode + '"]').first();
                        var copiedCount;

                        e.preventDefault();
                        e.stopPropagation();

                        if (!$tr.length || !attrCode || !$input.length) {
                            showMessage('error', $t('Unable to copy this value.'));
                            return;
                        }

                        copiedCount = copyAttrValueToAssociatedRows($tr, attrCode, $input.val());
                        showMessage('success', $t('Copied value to associated products: ') + copiedCount);
                    });

                    $('.artm-draft-table').off('click.artmCopyRowAssociated', '.artm-copy-row-associated').on('click.artmCopyRowAssociated', '.artm-copy-row-associated', function (e) {
                        var $btn = $(this);
                        var $tr = $btn.closest('tr.artm-draft-row');
                        var result;

                        e.preventDefault();
                        e.stopPropagation();

                        if (!$tr.length) {
                            showMessage('error', $t('Unable to copy row values.'));
                            return;
                        }

                        result = copyRowValuesToAssociatedRows($tr);
                        if (!result.fields) {
                            showMessage('error', $t('No eligible values found to copy from this row.'));
                            return;
                        }

                        showMessage('success', $t('Copied ') + result.fields + ' ' + $t('fields to associated products: ') + result.rows);
                    });
                } catch (e) {
                    $tr.data('saving', 0);
                    $tr.removeAttr('data-saving-at');
                    setSaveButtonState($btn, 'default');
                    showMessage('error', e && e.message ? e.message : $t('Unable to save row.'));
                }
            }

            $('.artm-draft-table').off('click.artmSaveRow', '.artm-save-row').on('click.artmSaveRow', '.artm-save-row', function () {
                var $btn = $(this);
                var $tr = $(this).closest('tr');
                saveDraftRow($tr, $btn);
            });

            var autoSaveTimers = {};
            function scheduleAutoSave($tr, delayMs) {
                var draftId = parseInt($tr.attr('data-id'), 10) || 0;
                if (draftId <= 0) {
                    return;
                }
                if (autoSaveTimers[draftId]) {
                    clearTimeout(autoSaveTimers[draftId]);
                }
                autoSaveTimers[draftId] = setTimeout(function () {
                    saveDraftRow($tr, $());
                }, delayMs || 350);
            }

            $('.artm-draft-table')
                .off('blur.artmAutoSave', 'input.artm-attr-input, textarea.artm-attr-input, .artm-parent-input')
                .on('blur.artmAutoSave', 'input.artm-attr-input, textarea.artm-attr-input, .artm-parent-input', function () {
                    var $tr = $(this).closest('tr');
                    updateLiveSkuInRow($tr);
                    scheduleAutoSave($tr, 250);
                });

            $('.artm-draft-table')
                .off('change.artmAutoSave', 'select.artm-attr-input, .artm-parent-select, .artm-child-select')
                .on('change.artmAutoSave', 'select.artm-attr-input, .artm-parent-select, .artm-child-select, .artm-variation-select', function () {
                    var $tr = $(this).closest('tr');
                    updateLiveSkuInRow($tr);
                    scheduleAutoSave($tr, 150);
                });

            $('.artm-select-all-rows').off('change').on('change', function () {
                var checked = $(this).is(':checked');
                var $table = $(this).closest('table');
                $table.find('.artm-row-select').prop('checked', checked);
                updateBulkSelectionButtons($table);
            });

            $('.artm-draft-table').off('input.artmColumnSearch change.artmColumnSearch', '.artm-column-search').on('input.artmColumnSearch change.artmColumnSearch', '.artm-column-search', function () {
                var $table = $(this).closest('table.artm-draft-table');
                applyTableColumnFilters($table);
                applyPagination();
            });

            $('.artm-draft-table').each(function () {
                applyTableColumnFilters($(this));
            });

            $('.artm-row-select').off('change').on('change', function () {
                var $table = $(this).closest('table');
                var total = $table.find('.artm-row-select').length;
                var checked = $table.find('.artm-row-select:checked').length;
                $table.find('.artm-select-all-rows').prop('checked', total > 0 && total === checked);
                updateBulkSelectionButtons($table);
            });

            $('.artm-draft-table').each(function () {
                updateBulkSelectionButtons($(this));
            });

            function selectedIdsInWrapper($btn) {
                return $btn.closest('.admin__fieldset-wrapper').find('.artm-row-select:checked').map(function () {
                    return parseInt($(this).data('id'), 10) || 0;
                }).get().filter(function (id) { return id > 0; });
            }

            $('.artm-move-selected-btn').off('click').on('click', function () {
                var $btn = $(this);
                var ids = selectedIdsInWrapper($(this));
                if (!ids.length) {
                    showMessage('error', $t('Please select rows to move.'));
                    return;
                }
                setActionProcessing($btn, $t('Moving...'));
                showMessage('success', $t('Moving selected rows to Ready...'));
                $.ajax({
                    url: config.moveToReadyUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { form_key: window.FORM_KEY || '', ids: ids }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to move selected rows.'));
                        resetActionButton($btn);
                        return;
                    }
                    $.each(ids, function (_, draftId) {
                        markRowMovedToReady($('.artm-draft-row[data-id="' + parseInt(draftId, 10) + '"]'));
                    });
                    setActionDone($btn, $t('Moved'));
                    refreshTabCounts();
                }).fail(function () {
                    showMessage('error', $t('Unable to move selected rows.'));
                    resetActionButton($btn);
                });
            });

            $('.artm-delete-selected-btn').off('click').on('click', function () {
                var ids = selectedIdsInWrapper($(this));
                if (!ids.length) {
                    showMessage('error', $t('Please select rows to delete.'));
                    return;
                }
                if (!window.confirm($t('Delete selected draft rows?'))) {
                    return;
                }
                $.ajax({
                    url: config.deleteUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { form_key: window.FORM_KEY || '', ids: ids }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to delete selected rows.'));
                        return;
                    }
                    showMessage('success', resp.message || $t('Selected rows deleted.'));
                    refreshTabCounts();
                    loadMatrix();
                }).fail(function () {
                    showMessage('error', $t('Unable to delete selected rows.'));
                });
            });

            $('.artm-move-completed-btn').off('click').on('click', function () {
                var $btn = $(this);
                var $wrapper = $(this).closest('.admin__fieldset-wrapper');
                var ids = $wrapper.find('.artm-draft-row.artm-row-ready').map(function () {
                    return parseInt($(this).data('id'), 10) || 0;
                }).get().filter(function (id) { return id > 0; });

                if (!ids.length) {
                    showMessage('error', $t('No completed rows found in this group.'));
                    return;
                }

                setActionProcessing($btn, $t('Moving...'));
                showMessage('success', $t('Moving completed rows to Ready...'));

                $.ajax({
                    url: config.moveToReadyUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { form_key: window.FORM_KEY || '', ids: ids }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to move completed rows.'));
                        resetActionButton($btn);
                        return;
                    }
                    $.each(ids, function (_, draftId) {
                        markRowMovedToReady($('.artm-draft-row[data-id="' + parseInt(draftId, 10) + '"]'));
                    });
                    setActionDone($btn, $t('Moved'));
                    refreshTabCounts();
                }).fail(function () {
                    showMessage('error', $t('Unable to move completed rows.'));
                    resetActionButton($btn);
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
                    refreshTabCounts();
                    loadMatrix();
                }).fail(function () {
                    showMessage('error', $t('Unable to delete row.'));
                });
            });

            $root.off('click.artmMoveSet', '.artm-move-set').on('click.artmMoveSet', '.artm-move-set', function () {
                var $btn = $(this);
                var ids = String($(this).data('ids') || '')
                    .split(',')
                    .map(function (x) { return parseInt(x, 10) || 0; })
                    .filter(function (x) { return x > 0; });

                if (!ids.length) {
                    showMessage('error', $t('No drafts selected.'));
                    return;
                }

                setActionProcessing($btn, $t('Moving...'));
                showMessage('success', $t('Moving selected group to Ready...'));

                $.ajax({
                    url: config.moveToReadyUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        form_key: window.FORM_KEY || '',
                        ids: ids
                    }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to move to ready.'));
                        resetActionButton($btn);
                        return;
                    }
                    $.each(ids, function (_, draftId) {
                        markRowMovedToReady($('.artm-draft-row[data-id="' + parseInt(draftId, 10) + '"]'));
                    });
                    setActionDone($btn, $t('Moved'));
                    refreshTabCounts();
                }).fail(function () {
                    showMessage('error', $t('Unable to move to ready.'));
                    resetActionButton($btn);
                });
            });

            function collectVariationAttributes($tr) {
                return normalizeVariationAttributes($tr.find('.artm-variation-tag').map(function () {
                    return String($(this).data('code') || '').trim();
                }).get());
            }

            function requiredInputsFilled($tr) {
                var codes = String($tr.data('required-codes') || '').split(',').filter(function (s) { return !!s; });
                var allFilled = true;

                if (codes.length) {
                    $.each(codes, function (_, code) {
                        var $inp = $tr.find('.artm-attr-input[data-code="' + code + '"]');
                        if (!$inp.length || !String($inp.val() || '').trim()) {
                            allFilled = false;
                            return false;
                        }
                        return true;
                    });
                }

                return allFilled;
            }

            function rowHasVariationInputs($tr, variationCodes) {
                var allFilled = true;
                $.each(variationCodes || [], function (_, code) {
                    var $inp = $tr.find('.artm-attr-input[data-code="' + code + '"]');
                    if (!$inp.length || !String($inp.val() || '').trim()) {
                        allFilled = false;
                        return false;
                    }
                    return true;
                });
                return allFilled;
            }

            function refreshConfigurableParentStateBySku($table, parentSku) {
                var sku = String(parentSku || '').trim();
                var $parentRow;
                if (!sku || !$table || !$table.length) {
                    return;
                }

                $parentRow = $table.find('tr.artm-row-configurable[data-sku="' + sku + '"]').first();
                if (!$parentRow.length) {
                    return;
                }

                $table.find('tr.artm-row-child[data-parent-sku="' + sku + '"]').each(function () {
                    checkRowCompleteness($(this), true);
                });
                checkRowCompleteness($parentRow, true);
                syncConfigurableGroupState($table, sku);
            }

            function rowVariationRequirementsMetDom($tr) {
                var $table = $tr.closest('table.artm-draft-table');
                var rowSku = String($tr.attr('data-sku') || '').trim();
                var parentSku = String($tr.attr('data-parent-sku') || '').trim();
                var variationCodes = [];
                var allChildrenReady = true;
                var childCount = 0;

                if ($tr.hasClass('artm-row-configurable')) {
                    variationCodes = collectVariationAttributes($tr);
                    if (!variationCodes.length || !rowSku) {
                        return false;
                    }

                    $table.find('tr.artm-row-child[data-parent-sku="' + rowSku + '"]').each(function () {
                        childCount += 1;
                        var $childRow = $(this);
                        if (!requiredInputsFilled($childRow) || !rowHasVariationInputs($childRow, variationCodes)) {
                            allChildrenReady = false;
                            return false;
                        }
                        return true;
                    });

                    return childCount > 0 && allChildrenReady;
                }

                if (parentSku) {
                    var $parentRow = $table.find('tr.artm-row-configurable[data-sku="' + parentSku + '"]').first();
                    if (!$parentRow.length) {
                        return false;
                    }
                    variationCodes = collectVariationAttributes($parentRow);
                    if (!variationCodes.length) {
                        return false;
                    }
                    return rowHasVariationInputs($tr, variationCodes);
                }

                return true;
            }

            function checkRowCompleteness($tr, skipDependentRefresh) {
                var allFilled;
                var $table = $tr.closest('table.artm-draft-table');
                var parentSku = String($tr.attr('data-parent-sku') || '').trim();

                if ($tr.hasClass('artm-row-configurable')) {
                    allFilled = rowVariationRequirementsMetDom($tr);
                } else {
                    allFilled = requiredInputsFilled($tr) && rowVariationRequirementsMetDom($tr);
                }

                if (allFilled) {
                    $tr.addClass('artm-row-ready');
                    $tr.find('.artm-move-row-btn').css('display', '');
                } else {
                    $tr.removeClass('artm-row-ready');
                    $tr.find('.artm-move-row-btn').hide();
                }

                syncRelatedConfigurableGroupState($tr);

                if (skipDependentRefresh) {
                    return;
                }

                if ($tr.hasClass('artm-row-configurable')) {
                    refreshConfigurableParentStateBySku($table, String($tr.attr('data-sku') || '').trim());
                    return;
                }

                if (parentSku) {
                    refreshConfigurableParentStateBySku($table, parentSku);
                }
            }

            // Initial completeness check for all rows
            $('.artm-draft-row[data-required-codes]').each(function () {
                checkRowCompleteness($(this));
            });
            $('.artm-draft-row.artm-row-configurable').each(function () {
                refreshConfigurableParentStateBySku($(this).closest('table.artm-draft-table'), String($(this).attr('data-sku') || '').trim());
            });

            // Live validation on input/change
            $('.artm-draft-table').off('input.artmVal change.artmVal').on('input.artmVal change.artmVal', '.artm-attr-input', function () {
                var $tr = $(this).closest('tr');
                if (String($(this).data('code') || '') === 'sku') {
                    $(this).removeClass('artm-field-error');
                }
                updateLiveSkuInRow($tr);
                markRowDirty($tr);
                checkRowCompleteness($tr);
            });

            $('.artm-draft-table').off('change.artmVariation', '.artm-variation-select').on('change.artmVariation', '.artm-variation-select', function () {
                var $select = $(this);
                var $tr = $select.closest('tr');
                var selectedCode = String($select.val() || '').trim();
                var selectedLabel = String($select.find('option:selected').text() || selectedCode);
                var alreadyExists = false;

                console.log('[ARTM DEBUG] variation-select change fired. selectedCode=', selectedCode, 'tr data-id=', $tr.attr('data-id'));

                if (!selectedCode) {
                    console.log('[ARTM DEBUG] selectedCode empty, returning early');
                    return;
                }

                $tr.find('.artm-variation-tag').each(function () {
                    if (String($(this).data('code') || '').trim() === selectedCode) {
                        alreadyExists = true;
                        return false;
                    }
                    return true;
                });

                if (!alreadyExists) {
                    $tr.find('.artm-variation-tags').append('<span class="artm-variation-tag" data-code="' + escapeHtml(selectedCode) + '">'
                        + '<span>' + escapeHtml(selectedLabel) + '</span>'
                        + '<button type="button" class="artm-variation-remove" title="' + escapeHtml($t('Remove')) + '">&times;</button>'
                        + '</span>');
                    $select.find('option[value="' + selectedCode + '"]').prop('disabled', true);
                }

                $select.val('');
                markRowDirty($tr);
                console.log('[ARTM DEBUG] Calling persistRowNow after variation add');
                persistRowNow($tr);
                checkRowCompleteness($tr);
            });

            $('.artm-draft-table').off('click.artmVariationRemove', '.artm-variation-remove').on('click.artmVariationRemove', '.artm-variation-remove', function () {
                var $btn = $(this);
                var $tag = $btn.closest('.artm-variation-tag');
                var $tr = $btn.closest('tr');
                var code = String($tag.data('code') || '').trim();
                var $select = $tr.find('.artm-variation-select').first();

                $tag.remove();
                if (code) {
                    $select.find('option[value="' + code + '"]').prop('disabled', false);
                }

                markRowDirty($tr);
                persistRowNow($tr);
                checkRowCompleteness($tr);
            });

            // Move single row to ready
            $('.artm-move-row-btn').off('click').on('click', function () {
                var $btn = $(this);
                var $tr = $(this).closest('tr');
                var draftId = parseInt($tr.data('id'), 10) || 0;
                if (draftId <= 0) {
                    return;
                }
                setActionProcessing($btn, '');
                showMessage('success', $t('Moving row to Ready...'));
                $.ajax({
                    url: config.moveToReadyUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: { form_key: window.FORM_KEY || '', ids: [draftId] }
                }).done(function (resp) {
                    if (!resp || !resp.success) {
                        showMessage('error', resp && resp.message ? resp.message : $t('Unable to move to ready.'));
                        resetActionButton($btn);
                        return;
                    }
                    markRowMovedToReady($tr);
                    setActionDone($btn, '');
                    refreshTabCounts();
                }).fail(function () {
                    showMessage('error', $t('Unable to move to ready.'));
                    resetActionButton($btn);
                });
            });

            // ---- View Product popup ----
            if (!$('#artm-view-product-modal').length) {
                $('body').append(
                    '<div id="artm-view-product-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:100200;">'
                    + '<div class="artm-vp-overlay" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.55);"></div>'
                    + '<div class="artm-vp-dialog" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:6px;width:700px;max-width:90vw;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.25);">'
                    + '<div style="padding:14px 18px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">'
                    + '<strong id="artm-vp-title" style="font-size:15px;"></strong>'
                    + '<button type="button" class="artm-vp-close" style="background:none;border:none;font-size:24px;line-height:1;cursor:pointer;color:#666;">&times;</button>'
                    + '</div>'
                    + '<div id="artm-vp-body" style="padding:0;overflow-y:auto;flex:1;"></div>'
                    + '</div>'
                    + '</div>'
                );
                $(document).on('click', '#artm-view-product-modal .artm-vp-close, #artm-view-product-modal .artm-vp-overlay', function () {
                    $('#artm-view-product-modal').hide();
                });
            }

            function popupEditorHtml($mainInput, code) {
                var safeCode = escapeHtml(code);
                if ($mainInput.is('select')) {
                    var opts = [];
                    $mainInput.find('option').each(function () {
                        var ov = String($(this).attr('value') || '');
                        var selected = String($mainInput.val() || '') === ov ? ' selected="selected"' : '';
                        opts.push('<option value="' + escapeHtml(ov) + '"' + selected + '>' + escapeHtml($(this).text()) + '</option>');
                    });
                    return '<select class="admin__control-select artm-vp-input" data-code="' + safeCode + '" style="width:100%;">' + opts.join('') + '</select>';
                }
                if ($mainInput.is('textarea')) {
                    return '<textarea class="admin__control-textarea artm-vp-input" data-code="' + safeCode + '" style="width:100%;min-height:58px;">'
                        + escapeHtml(String($mainInput.val() || '')) + '</textarea>';
                }

                var inputType = String($mainInput.attr('type') || 'text').toLowerCase();
                if (['date', 'number', 'text'].indexOf(inputType) === -1) {
                    inputType = 'text';
                }
                return '<input type="' + inputType + '" class="admin__control-text artm-vp-input" data-code="' + safeCode + '" value="'
                    + escapeHtml(String($mainInput.val() || '')) + '" style="width:100%;"/>';
            }

            function isMainFieldFilled($mainInput) {
                if ($mainInput.is('select')) {
                    return String($mainInput.val() || '').trim() !== '';
                }
                return String($mainInput.val() || '').trim() !== '';
            }

            function renderPopupForRow($tr) {
                var sku = $tr.find('.artm-col-sku').text().trim();
                var status = String($tr.data('status') || '');
                var draftId = parseInt($tr.data('id'), 10) || 0;
                var $table = $tr.closest('table');
                var filledRows = [];
                var emptyRows = [];
                var requiredFilledRows = [];
                var requiredEmptyRows = [];
                var optionalFilledRows = [];
                var optionalEmptyRows = [];

                $('#artm-vp-title').text($t('Product') + ': ' + (sku || $t('(no SKU)')));

                function makeMetaRow(label, val) {
                    return '<tr><td style="padding:8px 10px;width:40%;font-weight:600;border-bottom:1px solid #eef2f7;color:#334155;">' + escapeHtml(label) + '</td>'
                        + '<td style="padding:8px 10px;border-bottom:1px solid #eef2f7;color:#0f172a;">' + escapeHtml(val || '—') + '</td></tr>';
                }

                function makeAttrRow(labelHtml, editorHtml, code, rowClass) {
                    return '<tr class="' + escapeHtml(rowClass || '') + '" data-code="' + escapeHtml(code) + '">'
                        + '<td style="padding:8px 10px;width:40%;font-weight:600;border-bottom:1px solid #eef2f7;color:#334155;vertical-align:top;">' + labelHtml + '</td>'
                        + '<td style="padding:6px 8px;border-bottom:1px solid #eef2f7;">' + editorHtml + '</td>'
                        + '</tr>';
                }

                filledRows.push(makeMetaRow($t('SKU'), sku));
                filledRows.push(makeMetaRow($t('Status'), status));

                var requiredCodesStr = String($tr.data('required-codes') || '');
                var requiredCodes = requiredCodesStr ? requiredCodesStr.split(',') : [];

                $tr.find('[data-attr-code]').each(function () {
                    var code = String($(this).attr('data-attr-code') || '');
                    var label = String($(this).attr('data-attr-label') || code);
                    var isRequired = requiredCodes.indexOf(code) !== -1;
                    var $mainInput = $(this).find('.artm-attr-input');
                    var labelHtml = isRequired
                        ? '<span class="artm-vp-required-star">*</span>' + escapeHtml(label)
                        : escapeHtml(label);
                    var rowClass = isRequired ? 'artm-vp-row artm-vp-row--required' : 'artm-vp-row';
                    var rowHtml;

                    if (!$mainInput.length || !code) {
                        return;
                    }

                    rowHtml = makeAttrRow(labelHtml, popupEditorHtml($mainInput, code), code, rowClass);
                    if (isMainFieldFilled($mainInput)) {
                        if (isRequired) {
                            requiredFilledRows.push(rowHtml);
                        } else {
                            optionalFilledRows.push(rowHtml);
                        }
                    } else if (isRequired) {
                        requiredEmptyRows.push(rowHtml);
                    } else {
                        optionalEmptyRows.push(rowHtml);
                    }
                });

                filledRows = filledRows.concat(requiredFilledRows, optionalFilledRows);
                emptyRows = emptyRows.concat(requiredEmptyRows, optionalEmptyRows);

                function normalizeHeaderText(txt) {
                    return String(txt || '')
                        .replace(/\*/g, '')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .toLowerCase();
                }

                var popupCovered = {
                    sku: true,
                    status: true
                };
                $tr.find('[data-attr-label]').each(function () {
                    var lbl = normalizeHeaderText($(this).data('attr-label'));
                    if (lbl) {
                        popupCovered[lbl] = true;
                    }
                });

                var nonPopupColumns = [];
                $table.find('thead th').each(function () {
                    var original = String($(this).text() || '').replace(/\s+/g, ' ').trim();
                    var normalized = normalizeHeaderText(original);
                    if (!normalized) {
                        return;
                    }
                    if (!popupCovered[normalized]) {
                        nonPopupColumns.push(original);
                    }
                });

                var filledTable = '<table style="width:100%;border-collapse:collapse;">' + filledRows.join('') + '</table>';
                var emptyTable = '<table style="width:100%;border-collapse:collapse;">'
                    + (emptyRows.length ? emptyRows.join('') : '<tr><td style="padding:8px 10px;color:#94a3b8;font-style:italic;">' + escapeHtml($t('No empty fields')) + '</td></tr>')
                    + '</table>';

                var noticeHtml = '';
                if (nonPopupColumns.length) {
                    noticeHtml = '<div style="margin:10px 10px 0;padding:8px 10px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;border-radius:4px;font-size:12px;">'
                        + escapeHtml($t('Please check these columns in table itself: '))
                        + escapeHtml(nonPopupColumns.join(', '))
                        + '</div>';
                }

                var bodyHtml = noticeHtml
                    + '<div style="display:flex;gap:12px;align-items:flex-start;padding:10px;" data-draft-id="' + draftId + '">'
                    + '<div style="flex:1;min-width:0;border:1px solid #d1fae5;border-radius:4px;overflow:hidden;">'
                    + '<div style="padding:7px 10px;background:#ecfdf5;color:#166534;font-weight:700;font-size:12px;border-bottom:1px solid #d1fae5;">' + escapeHtml($t('FILLED FIELDS')) + ' (' + filledRows.length + ')</div>'
                    + '<div class="artm-vp-filled">' + filledTable + '</div>'
                    + '</div>'
                    + '<div style="flex:1;min-width:0;border:1px solid #e2e8f0;border-radius:4px;overflow:hidden;">'
                    + '<div style="padding:7px 10px;background:#f8fafc;color:#64748b;font-weight:700;font-size:12px;border-bottom:1px solid #e2e8f0;">' + escapeHtml($t('EMPTY FIELDS')) + ' (' + emptyRows.length + ')</div>'
                    + '<div class="artm-vp-empty">' + emptyTable + '</div>'
                    + '</div>'
                    + '</div>';

                $('#artm-vp-body').html(bodyHtml);
                $('#artm-view-product-modal').data('draft-row-id', draftId);
            }

            function markPopupFieldState($vpInput, isFilled) {
                $vpInput.toggleClass('artm-vp-input--filled-temp', isFilled);
                $vpInput.closest('tr').toggleClass('artm-vp-row--completed-temp', isFilled);
            }

            $(document).off('change.artmVp blur.artmVp', '#artm-view-product-modal .artm-vp-input')
                .on('change.artmVp blur.artmVp', '#artm-view-product-modal .artm-vp-input', function () {
                    var $vpInput = $(this);
                    var code = String($vpInput.data('code') || '');
                    if (!code) {
                        return;
                    }

                    var draftId = parseInt($('#artm-view-product-modal').data('draft-row-id'), 10) || 0;
                    var $tr = $('.artm-draft-row[data-id="' + draftId + '"]');
                    if (!$tr.length) {
                        return;
                    }

                    var newVal = String($vpInput.val() == null ? '' : $vpInput.val());
                    if (code === 'sku' && !validateDraftSkuUniqueness($tr, newVal)) {
                        $vpInput.addClass('artm-field-error');
                        return;
                    }

                    $.ajax({
                        url: config.rowSaveUrl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            form_key: window.FORM_KEY || '',
                            draft_id: draftId,
                            values: (function () {
                                var payload = {};
                                payload[code] = newVal;
                                return payload;
                            })()
                        }
                    }).done(function (resp) {
                        if (!resp || !resp.success) {
                            showMessage('error', resp && resp.message ? resp.message : $t('Unable to save field.'));
                            return;
                        }

                        var $rowInput = $tr.find('.artm-attr-input[data-code="' + code + '"]');
                        if ($rowInput.length) {
                            $rowInput.val(newVal);
                            syncSearchableInputFromSelect($rowInput);
                        }

                        if (code === 'sku') {
                            $vpInput.removeClass('artm-field-error');
                            $rowInput.removeClass('artm-field-error');
                            updateLiveSkuInRow($tr);
                        }

                        checkRowCompleteness($tr);
                        markPopupFieldState($vpInput, String(newVal).trim() !== '');
                    }).fail(function () {
                        showMessage('error', $t('Unable to save field.'));
                    });
                });

            $('.artm-view-product-btn').off('click').on('click', function () {
                var $tr = $(this).closest('tr');
                renderPopupForRow($tr);
                $('#artm-view-product-modal').show();
            });
        }

        function loadMatrix() {
            function rowEntityId(row) {
                return parseInt(row && row.entity_id, 10) || 0;
            }

            function groupLatestRowId(group) {
                var latest = 0;
                $.each(group && group.rows ? group.rows : [], function (_, row) {
                    var id = rowEntityId(row);
                    if (id > latest) {
                        latest = id;
                    }
                });
                return latest;
            }

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

                var groups = (resp.groups || []).slice();
                $.each(groups, function (_, group) {
                    if (!group || !Array.isArray(group.rows)) {
                        return;
                    }
                    group.rows = group.rows.slice().sort(function (a, b) {
                        return rowEntityId(b) - rowEntityId(a);
                    });
                });

                groups.sort(function (a, b) {
                    return groupLatestRowId(b) - groupLatestRowId(a);
                });

                var html = [];
                $.each(groups, function (_, group) {
                    html.push(renderGroup(group));
                });
                if (!html.length) {
                    html.push('<div class="message message-notice notice"><div>' + $t('No draft records found.') + '</div></div>');
                }

                closeSearchableSelectPanel();
                $('#artm-draft-groups').html(html.join(''));
                $('#artm-draft-msg').hide();
                bindActions();
                applyPagination();
            }).fail(function () {
                showMessage('error', $t('Unable to load drafts.'));
            });
        }

        loadMatrix();
    };
});