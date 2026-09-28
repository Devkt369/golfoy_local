define([
    'jquery',
    'ArtM_AdminAI/js/chart-manager'
], function ($, chartManager) {
    'use strict';

    var state = {
        rows: [],
        currentPage: 1,
        pageSize: 20,
        currentSection: 'overview',
        sortKey: null,
        sortDirection: 'asc',
        widgetTables: {},
        skuTracker: {
            sku: '',
            fromDate: '',
            toDate: ''
        },
        customerPeriod: '90d',
        customerLookupQuery: ''
    };

    function formatNumber(value) {
        var num = Number(value || 0);
        if (!isFinite(num)) {
            return '0';
        }
        return num.toLocaleString();
    }

    function formatCurrency(value) {
        var num = Number(value || 0);
        if (!isFinite(num)) {
            num = 0;
        }
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function isPriceColumn(key) {
        var column = String(key || '').toLowerCase();
        var hints = [
            'price',
            'amount',
            'revenue',
            'value',
            'aov',
            'avg_order_value',
            'average_order_value',
            'avg_selling_price',
            'average_selling_price'
        ];

        return hints.some(function (hint) {
            return column.indexOf(hint) !== -1;
        });
    }

    function formatTableCellValue(key, value) {
        if (!isPriceColumn(key)) {
            return value;
        }

        var normalized = String(value == null ? '' : value).replace(/,/g, '').trim();
        var num = Number(normalized);
        if (!isFinite(num)) {
            return value;
        }

        return formatCurrency(num);
    }

    function formatWidgetCellHtml(key, value) {
        var text = formatTableCellValue(key, value);
        var escaped = escapeHtml(text == null ? '' : text);
        var inner = escaped.replace(/\n/g, '<br />');
        if (key === 'order_dates') {
            return '<div class="adminai-order-dates-scroll">' + inner + '</div>';
        }
        return inner;
    }

    function setOverviewVisibility($root, visible) {
        var $panel = $root.closest('.adminai-content__panel');
        var $overview = $panel.find('.adminai-overview-zone');
        if (visible) {
            $overview.removeClass('is-hidden');
        } else {
            $overview.addClass('is-hidden');
        }
    }

    function setSectionZoneVisibility($root, section) {
        var $panel = $root.closest('.adminai-content__panel');
        var $zones = $panel.find('.adminai-section-zone');
        $zones.addClass('is-hidden');
        if (section !== 'overview') {
            $panel.find('.adminai-section-zone[data-zone-section="' + section + '"]').removeClass('is-hidden');
        }
    }

    function setToolbarVisibility($root, section) {
        var $panel = $root.closest('.adminai-content__panel');
        var $toolbar = $panel.find('.adminai-toolbar');
        if (section === 'customers') {
            $toolbar.hide();
            return;
        }
        $toolbar.show();
    }

    function setMainGridVisibility($root, visible) {
        var $targets = $root.find('.adminai-data-summary, .adminai-grid-title, .adminai-grid-controls, .adminai-data-grid');
        if (visible) {
            $targets.show();
            return;
        }
        $targets.hide();
    }

    function isCustomerWindowWidget(widgetKey) {
        return widgetKey === 'customer_active' || widgetKey === 'customer_inactive';
    }

    function isCustomerSegmentWidget(widgetKey) {
        return [
            'customer_new_customers',
            'customer_frequent_buyers',
            'customer_high_value_customers',
            'customer_discount_hunters',
            'customer_category_loyal_customers',
            'customer_new_arrival_buyers',
            'customer_at_risk_customers',
            'customer_reactivated_customers',
            'customer_one_time_buyers',
            'customer_lost_customers'
        ].indexOf(widgetKey) !== -1;
    }

    function isZeroPricePurchaseWidget(widgetKey) {
        return widgetKey === 'customer_zero_price_purchases';
    }

    function isCustomerTableWidget(widgetKey) {
        return String(widgetKey || '').indexOf('customer_') === 0;
    }

    function getWidgetDistinctValues(rows, key) {
        var seen = {};
        var values = [];

        (rows || []).forEach(function (row) {
            var value = String((row && row[key]) || '').trim();
            var token = value.toLowerCase();
            if (!value || seen[token]) {
                return;
            }

            seen[token] = true;
            values.push(value);
        });

        values.sort(function (left, right) {
            return left.localeCompare(right);
        });

        return values;
    }

    function renderWidgetSelect(widgetKey, cssClass, label, values, selectedValue, allLabel) {
        var html = '<label>' + escapeHtml(label) + ' <select class="' + escapeHtml(cssClass) + '" data-widget-key="' + escapeHtml(widgetKey) + '">';
        html += '<option value="">' + escapeHtml(allLabel) + '</option>';
        (values || []).forEach(function (value) {
            var selected = String(selectedValue || '') === String(value) ? ' selected="selected"' : '';
            html += '<option value="' + escapeHtml(value) + '"' + selected + '>' + escapeHtml(value) + '</option>';
        });
        html += '</select></label>';
        return html;
    }

    function toggleGlobalLoader(show) {
        $('body').trigger(show ? 'processStart' : 'processStop');
    }

    function renderListWidget($target, items) {
        var html = '';
        (items || []).forEach(function (line) {
            html += '<div class="adminai-widget-list__item">' + escapeHtml(line) + '</div>';
        });
        $target.html(html || '<div class="adminai-empty">No data available.</div>');
    }

    function buildCustomerOrderRangeBands(activeRows) {
        var rows = Array.isArray(activeRows) ? activeRows : [];
        var values = rows
            .map(function (row) {
                var n = Number((row && row.total_orders) || 0);
                return isFinite(n) ? Math.max(0, Math.floor(n)) : 0;
            })
            .filter(function (n) {
                return n > 0;
            });

        if (!values.length) {
            return { min: 0, max: 0, total: 0, ranges: [] };
        }

        var min = Math.min.apply(null, values);
        var max = Math.max.apply(null, values);
        var span = Math.max(1, (max - min + 1));
        var lowWidth = Math.max(1, Math.round(span * 0.10));
        var highWidth = Math.max(1, Math.round(span * 0.10));

        if ((lowWidth + highWidth) >= span) {
            lowWidth = 1;
            highWidth = 1;
        }

        var lowStart = min;
        var lowEnd = lowStart + lowWidth - 1;
        var highEnd = max;
        var highStart = highEnd - highWidth + 1;
        var midStart = lowEnd + 1;
        var midEnd = highStart - 1;

        if (midStart > midEnd) {
            midStart = lowEnd;
            midEnd = lowEnd;
        }

        var rangeDefs = [
            { label: 'Top 10%', start: lowStart, end: Math.min(lowEnd, max), css: 'adminai-risk-low' },
            { label: '11%-90%', start: Math.max(lowEnd + 1, min), end: Math.min(midEnd, max), css: 'adminai-risk-medium' },
            { label: '91%-100%', start: Math.max(highStart, min), end: highEnd, css: 'adminai-risk-high' }
        ];

        rangeDefs.forEach(function (range) {
            if (range.end < range.start) {
                range.count = 0;
                return;
            }
            range.count = values.filter(function (n) {
                return n >= range.start && n <= range.end;
            }).length;
        });

        return {
            min: min,
            max: max,
            total: values.length,
            ranges: rangeDefs
        };
    }

    function renderCustomerEngagementBars($panel, summary) {
        var $target = $panel.find('[data-widget="customer_engagement_bars"]');
        if (!$target.length) {
            return;
        }

        var inactive    = Number(summary.inactive_customers || 0);
        var active      = Number(summary.active_customers || 0);
        var superActive = Number(summary.super_active_customers || 0);
        var superDuper  = Number(summary.super_duper_customers || 0);
        var total       = inactive + active;

        if (total <= 0) {
            $target.html('<div class="adminai-empty">No customer data available.</div>');
            return;
        }

        function col(label, count, description, cssClass) {
            var pct = Math.round((count / total) * 100);
            var html = '';
            html += '<div class="adminai-order-band-col">';
            html += '<div class="adminai-order-band-col__label">' + escapeHtml(label) + '</div>';
            html += '<div class="adminai-risk-row__bar adminai-order-band-col__bar"><span class="' + escapeHtml(cssClass) + '" style="width:' + pct + '%"></span></div>';
            html += '<div class="adminai-order-band-col__count">' + escapeHtml(formatNumber(count)) + ' customers</div>';
            html += '<div class="adminai-order-band-col__caption">' + escapeHtml(description) + '</div>';
            html += '<div class="adminai-order-band-col__pct">' + escapeHtml(String(pct)) + '% of total</div>';
            html += '</div>';
            return html;
        }

        var html = '<div class="adminai-customer-orders-band-list adminai-customer-orders-band-list--4">';
        html += col('Inactive',           inactive,    'No orders in window.',        'adminai-engagement-inactive');
        html += col('Active',             active,      '\u22651 order in window.',     'adminai-engagement-active');
        html += col('Super Active',       superActive, '1\u20135 orders in window.',   'adminai-engagement-super');
        html += col('Super Duper Active', superDuper,  '>5 orders in window.',         'adminai-engagement-superduper');
        html += '</div>';

        $target.html(html);
    }

    function renderCustomerOrderDistributionChart($panel, activeRows) {
        var $bars = $panel.find('[data-widget="customer_order_distribution_bars"]');
        var $meta = $panel.find('[data-widget="customer_order_distribution_meta"]');
        var $empty = $panel.find('[data-widget="customer_order_distribution_empty"]');
        if (!$bars.length) {
            return;
        }

        var banded = buildCustomerOrderRangeBands(activeRows || []);
        if (!banded.total || !banded.ranges.length) {
            $meta.html('');
            $bars.html('');
            $empty.removeClass('is-hidden');
            return;
        }

        $empty.addClass('is-hidden');
        $meta.html(
            '<div class="adminai-customer-orders-chart-header">'
            + '<div class="adminai-meta-line">Active customers: <strong>' + escapeHtml(formatNumber(banded.total)) + '</strong></div>'
            + '<div class="adminai-meta-line">Order range (lifetime): <strong>' + escapeHtml(String(banded.min) + ' to ' + String(banded.max)) + '</strong></div>'
            + '</div>'
        );

        var html = '';
        banded.ranges.forEach(function (range) {
            var pct = banded.total > 0 ? Math.round((range.count / banded.total) * 100) : 0;
            html += '<div class="adminai-order-band-col">';
            html += '<div class="adminai-order-band-col__label">' + escapeHtml(range.label) + '</div>';
            html += '<div class="adminai-risk-row__bar adminai-order-band-col__bar"><span class="' + escapeHtml(range.css) + '" style="width:' + pct + '%"></span></div>';
            html += '<div class="adminai-order-band-col__count">' + escapeHtml(formatNumber(range.count)) + ' customers</div>';
            html += '<div class="adminai-order-band-col__caption">Orders ' + escapeHtml(String(range.start) + '–' + String(range.end)) + '</div>';
            html += '<div class="adminai-order-band-col__pct">' + escapeHtml(String(pct)) + '% of active</div>';
            html += '</div>';
        });

        $bars.html(html);
    }

    function updateWidgetTableData(widgetKey, rows, options) {
        if (!widgetKey) {
            return;
        }

        var cfg = options || {};
        var previous = state.widgetTables[widgetKey] || {};
        state.widgetTables[widgetKey] = {
            rows: Array.isArray(rows) ? rows : [],
            currentPage: 1,
            pageSize: Number(previous.pageSize || 10),
            sortKey: previous.sortKey || (widgetKey === 'stock_projection' ? 'days_remaining' : null),
            sortDirection: previous.sortDirection || 'asc',
            searchQuery: previous.searchQuery || '',
            daysThreshold: Number(previous.daysThreshold || 15),
            projectionRangeDays: Number(cfg.projectionRangeDays || previous.projectionRangeDays || 90),
            includeOutOfStock: previous.includeOutOfStock === undefined ? false : Boolean(previous.includeOutOfStock),
            customerSinceFrom: previous.customerSinceFrom || '',
            customerSinceTo: previous.customerSinceTo || '',
            brandFilter: previous.brandFilter || '',
            attributeSetFilter: previous.attributeSetFilter || ''
        };
    }

    function filterWidgetRows(widgetKey, rows, tableState) {
        var safeRows = Array.isArray(rows) ? rows : [];
        var query = String((tableState && tableState.searchQuery) || '').trim().toLowerCase();

        if (widgetKey !== 'stock_projection') {
            if (isCustomerWindowWidget(widgetKey)) {
                var customerSinceFrom = String((tableState && tableState.customerSinceFrom) || '').trim();
                var customerSinceTo = String((tableState && tableState.customerSinceTo) || '').trim();

                return safeRows.filter(function (row) {
                    var customerId = String((row && row.customer_id) || '').toLowerCase();
                    var customerSince = String((row && row.customer_since) || '');
                    var matchesQuery = !query || customerId.indexOf(query) !== -1;
                    var matchesFrom = !customerSinceFrom || (customerSince && customerSince >= customerSinceFrom);
                    var matchesTo = !customerSinceTo || (customerSince && customerSince <= customerSinceTo);
                    return matchesQuery && matchesFrom && matchesTo;
                });
            }

            if (isZeroPricePurchaseWidget(widgetKey)) {
                var brandFilter = String((tableState && tableState.brandFilter) || '').trim().toLowerCase();
                var attributeSetFilter = String((tableState && tableState.attributeSetFilter) || '').trim().toLowerCase();

                return safeRows.filter(function (row) {
                    var rowBrand = String((row && row.brand) || '').toLowerCase();
                    var rowAttributeSet = String((row && row.attribute_set) || '').toLowerCase();
                    var matchesQuery = !query || Object.keys(row || {}).some(function (key) {
                        return String(row[key] == null ? '' : row[key]).toLowerCase().indexOf(query) !== -1;
                    });
                    var matchesBrand = !brandFilter || rowBrand === brandFilter;
                    var matchesAttributeSet = !attributeSetFilter || rowAttributeSet === attributeSetFilter;

                    return matchesQuery && matchesBrand && matchesAttributeSet;
                });
            }

            if (isCustomerSegmentWidget(widgetKey)) {
                if (!query) {
                    return safeRows;
                }

                return safeRows.filter(function (row) {
                    return Object.keys(row || {}).some(function (key) {
                        return String(row[key] == null ? '' : row[key]).toLowerCase().indexOf(query) !== -1;
                    });
                });
            }

            if (!query) {
                return safeRows;
            }
            return safeRows.filter(function (row) {
                return Object.keys(row || {}).some(function (key) {
                    return String(row[key] == null ? '' : row[key]).toLowerCase().indexOf(query) !== -1;
                });
            });
        }

        var threshold = Number((tableState && tableState.daysThreshold) || 15);
        if (!isFinite(threshold) || threshold <= 0) {
            threshold = 15;
        }
        var includeOutOfStock = Boolean(tableState && tableState.includeOutOfStock);

        // When SKU search is used, search in the full stock projection dataset
        // so users can always find a SKU regardless of days threshold filter.
        if (query) {
            return safeRows.filter(function (row) {
                var sku = String((row && row.product) || '').toLowerCase();
                return sku.indexOf(query) !== -1;
            });
        }

        var filtered = safeRows.filter(function (row) {
            var days = Number((row && row.days_remaining) || 0);
            var stockLeft = Number((row && row.stock_left) || 0);
            var isLowQty = stockLeft < 3;
            var isBelowThreshold = days > 0 && days < threshold;
            var isOutOfStock = stockLeft <= 0.0001;

            if (!includeOutOfStock && isOutOfStock) {
                return false;
            }

            return isLowQty || isBelowThreshold;
        });

        return filtered;
    }

    function getWidgetExportRows(widgetKey, tableState) {
        var rows = Array.isArray(tableState.rows) ? tableState.rows : [];
        var filteredRows = filterWidgetRows(widgetKey, rows, tableState);
        var sortedRows = getSortedRowsBy(filteredRows, tableState.sortKey, tableState.sortDirection);
        return sortedRows;
    }

    function renderWidgetControls(widgetKey, tableState, pageInfo) {
        var html = '';

        html += '<div class="adminai-widget-table__controls">';
        html += '<div class="adminai-widget-table__controls-left">';
        html += '<button type="button" class="action-secondary adminai-widget-export" data-widget-key="' + escapeHtml(widgetKey) + '">Export CSV</button>';

        if (widgetKey === 'stock_projection') {
            html += '<label>Sales window <select class="adminai-widget-range-days" data-widget-key="' + escapeHtml(widgetKey) + '">';
            [90, 180, 360].forEach(function (days) {
                var selectedDays = Number(tableState.projectionRangeDays || 90) === days ? ' selected="selected"' : '';
                html += '<option value="' + days + '"' + selectedDays + '>Last ' + days + ' days</option>';
            });
            html += '</select></label>';
            html += '<label>Days less than <input type="number" min="1" class="adminai-widget-days-threshold" data-widget-key="' + escapeHtml(widgetKey) + '" value="' + escapeHtml(tableState.daysThreshold || 15) + '" /></label>';
            html += '<label class="adminai-widget-checkbox"><input type="checkbox" class="adminai-widget-oos-toggle" data-widget-key="' + escapeHtml(widgetKey) + '"' + (tableState.includeOutOfStock ? ' checked="checked"' : '') + ' /> Include O-O-S</label>';
            html += '<input type="text" class="adminai-widget-search" data-widget-key="' + escapeHtml(widgetKey) + '" placeholder="Search SKU" value="' + escapeHtml(tableState.searchQuery || '') + '" />';
        } else if (isCustomerWindowWidget(widgetKey)) {
            html += '<input type="text" class="adminai-widget-search" data-widget-key="' + escapeHtml(widgetKey) + '" placeholder="Search Customer ID" value="' + escapeHtml(tableState.searchQuery || '') + '" />';
            html += '<label>Customer since from <input type="date" class="adminai-widget-customer-since-from" data-widget-key="' + escapeHtml(widgetKey) + '" value="' + escapeHtml(tableState.customerSinceFrom || '') + '" /></label>';
            html += '<label>Customer since to <input type="date" class="adminai-widget-customer-since-to" data-widget-key="' + escapeHtml(widgetKey) + '" value="' + escapeHtml(tableState.customerSinceTo || '') + '" /></label>';
        } else if (isZeroPricePurchaseWidget(widgetKey)) {
            html += '<input type="text" class="adminai-widget-search" data-widget-key="' + escapeHtml(widgetKey) + '" placeholder="Search customer, order, product" value="' + escapeHtml(tableState.searchQuery || '') + '" />';
            html += renderWidgetSelect(widgetKey, 'adminai-widget-brand-filter', 'Brand', getWidgetDistinctValues(tableState.rows, 'brand'), tableState.brandFilter, 'All brands');
            html += renderWidgetSelect(widgetKey, 'adminai-widget-attribute-set-filter', 'Attribute Set', getWidgetDistinctValues(tableState.rows, 'attribute_set'), tableState.attributeSetFilter, 'All sets');
        } else if (isCustomerSegmentWidget(widgetKey)) {
            html += '<input type="text" class="adminai-widget-search" data-widget-key="' + escapeHtml(widgetKey) + '" placeholder="Search customer or email" value="' + escapeHtml(tableState.searchQuery || '') + '" />';
        }

        html += '<label>Show <select class="adminai-widget-page-size" data-widget-key="' + escapeHtml(widgetKey) + '">';
        [10, 20, 50, 100].forEach(function (size) {
            var selected = Number(tableState.pageSize) === size ? ' selected="selected"' : '';
            html += '<option value="' + size + '"' + selected + '>' + size + '</option>';
        });
        html += '</select></label>';
        html += '</div>';
        html += '<div class="adminai-widget-pagination">';
        html += '<button type="button" class="action-secondary adminai-widget-page-prev" data-widget-key="' + escapeHtml(widgetKey) + '"' + (pageInfo.currentPage <= 1 ? ' disabled="disabled"' : '') + '>Prev</button>';
        html += '<span>Page ' + pageInfo.currentPage + ' of ' + pageInfo.totalPages + ' (Rows ' + pageInfo.start + '-' + pageInfo.end + ' of ' + pageInfo.totalRows + ')</span>';
        html += '<button type="button" class="action-secondary adminai-widget-page-next" data-widget-key="' + escapeHtml(widgetKey) + '"' + (pageInfo.currentPage >= pageInfo.totalPages ? ' disabled="disabled"' : '') + '>Next</button>';
        html += '</div>';
        html += '</div>';

        return html;
    }

    function renderWidgetEmpty($target, message) {
        $target.html('<div class="adminai-empty">' + escapeHtml(message) + '</div>');
    }

    function renderWidgetTable($root, widgetKey) {
        var tableState = state.widgetTables[widgetKey] || null;
        var $panel = $root.closest('.adminai-content__panel');
        var $target = $panel.find('[data-widget="' + widgetKey + '"]');
        if (!$target.length || !tableState) {
            return;
        }

        var rows = Array.isArray(tableState.rows) ? tableState.rows : [];
        if (!rows.length) {
            renderWidgetEmpty($target, 'No data available.');
            return;
        }

        var filteredRows = filterWidgetRows(widgetKey, rows, tableState);
        if (!filteredRows.length) {
            var emptyInfo = {
                currentPage: 1,
                totalPages: 1,
                start: 0,
                end: 0,
                totalRows: 0
            };
            var emptyMessage = widgetKey === 'stock_projection'
                ? 'No rows found for current stock filters.'
                : 'No rows found for current filters.';
            $target.html(
                renderWidgetControls(widgetKey, tableState, emptyInfo)
                + '<div class="adminai-empty">' + escapeHtml(emptyMessage) + '</div>'
            );
            return;
        }

        var sortedRows = getSortedRowsBy(filteredRows, tableState.sortKey, tableState.sortDirection);
        var pageInfo = getPageSliceBy(sortedRows, tableState.currentPage, tableState.pageSize);
        tableState.currentPage = pageInfo.currentPage;

        var pageRows = pageInfo.rows;
        var headers = Object.keys(pageRows[0] || {});
        var html = '';

        html += renderWidgetControls(widgetKey, tableState, pageInfo);

        var wrapClass = 'adminai-widget-table__wrap';
        if (isCustomerTableWidget(widgetKey)) {
            wrapClass += ' adminai-widget-table__wrap--customer';
        }

        html += '<div class="' + wrapClass + '"><table><thead><tr>';
        headers.forEach(function (key) {
            var isActiveSort = tableState.sortKey === key;
            var arrow = isActiveSort ? (tableState.sortDirection === 'asc' ? ' ▲' : ' ▼') : '';
            var classes = 'adminai-widget-table-sort' + (isActiveSort ? ' is-sorted' : '');
            html += '<th class="' + classes + '" data-widget-key="' + escapeHtml(widgetKey) + '" data-widget-sort-key="' + escapeHtml(key) + '">'
                + escapeHtml(key.replace(/_/g, ' ')) + arrow + '</th>';
        });
        html += '</tr></thead><tbody>';

        pageRows.forEach(function (row) {
            html += '<tr>';
            headers.forEach(function (key) {
                var cellValue = row[key];
                if (widgetKey === 'stock_projection' && key === 'days_remaining') {
                    cellValue = formatDaysRemainingRange(cellValue);
                }
                html += '<td>' + formatWidgetCellHtml(key, cellValue) + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $target.html(html);
    }

    function getSortedRowsBy(rows, sortKey, sortDirection) {
        var safeRows = Array.isArray(rows) ? rows.slice() : [];
        if (!sortKey) {
            return safeRows;
        }

        safeRows.sort(function (a, b) {
            var left = parseSortValue(a[sortKey]);
            var right = parseSortValue(b[sortKey]);

            if (left.value < right.value) {
                return sortDirection === 'asc' ? -1 : 1;
            }
            if (left.value > right.value) {
                return sortDirection === 'asc' ? 1 : -1;
            }
            return 0;
        });

        return safeRows;
    }

    function getPageSliceBy(rows, currentPage, pageSize) {
        var safeRows = Array.isArray(rows) ? rows : [];
        var safePageSize = Math.max(1, Number(pageSize || 10));
        var total = safeRows.length;
        var pages = Math.max(1, Math.ceil(total / safePageSize));
        var page = Math.min(Math.max(1, Number(currentPage || 1)), pages);

        var start = (page - 1) * safePageSize;
        var end = start + safePageSize;

        return {
            rows: safeRows.slice(start, end),
            totalRows: total,
            totalPages: pages,
            currentPage: page,
            start: total === 0 ? 0 : start + 1,
            end: Math.min(end, total)
        };
    }

    function buildCsv(rows) {
        var safeRows = Array.isArray(rows) ? rows : [];
        if (!safeRows.length) {
            return '';
        }

        var headers = Object.keys(safeRows[0] || {});
        function csvCell(value) {
            var text = value == null ? '' : String(value);
            return '"' + text.replace(/"/g, '""') + '"';
        }

        var lines = [];
        lines.push(headers.map(csvCell).join(','));
        safeRows.forEach(function (row) {
            lines.push(headers.map(function (key) {
                return csvCell(row[key]);
            }).join(','));
        });

        return lines.join('\r\n');
    }

    function downloadCsv(fileName, csvContent) {
        if (!csvContent) {
            return;
        }

        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }

    function renderOverviewWidgets($root, widgets) {
        var $panel = $root.closest('.adminai-content__panel');
        var business = widgets.business_health || {};
        var segments = widgets.segment_counts || {};
        var risk = widgets.risk_monitor || {};
        var acq = widgets.acquisition_retention || {};
        var alerts = widgets.alerts || {};
        var feed = Array.isArray(widgets.notifications_feed) ? widgets.notifications_feed : [];

        var rangeLabel = String(business.range_label || '30D');
        var kpisHtml = '';
        [
            { label: 'Total Revenue (' + rangeLabel + ')', value: formatCurrency(business.total_revenue) },
            { label: 'Orders (' + rangeLabel + ')', value: formatNumber(business.orders_count) },
            { label: 'Active Customers (' + rangeLabel + ')', value: formatNumber(business.active_customers) },
            { label: 'Avg Order Value', value: formatCurrency(business.avg_order_value) },
            { label: 'Returning Customer %', value: formatNumber(business.returning_customer_percent) + '%' }
        ].forEach(function (item) {
            kpisHtml += '<div class="adminai-overview-kpi">';
            kpisHtml += '<div class="adminai-overview-kpi__label">' + escapeHtml(item.label) + '</div>';
            kpisHtml += '<div class="adminai-overview-kpi__value">' + escapeHtml(item.value) + '</div>';
            kpisHtml += '</div>';
        });
        $panel.find('.adminai-overview-kpis').html(kpisHtml);

        var segHtml = '';
        Object.keys(segments).forEach(function (key) {
            segHtml += '<button type="button" class="adminai-segment-card" data-segment="' + escapeHtml(key) + '">';
            segHtml += '<span class="adminai-segment-card__code">' + escapeHtml(key) + '</span>';
            segHtml += '<span class="adminai-segment-card__count">' + escapeHtml(formatNumber(segments[key])) + '</span>';
            segHtml += '</button>';
        });
        $panel.find('.adminai-segment-cards').html(segHtml || '<div class="adminai-empty">No segment data.</div>');

        var totalRisk = Number(risk.low || 0) + Number(risk.medium || 0) + Number(risk.high || 0);
        function riskBar(label, value, css) {
            var safe = Number(value || 0);
            var pct = totalRisk > 0 ? Math.round((safe / totalRisk) * 100) : 0;
            return '<div class="adminai-risk-row">'
                + '<span class="adminai-risk-row__label">' + escapeHtml(label) + '</span>'
                + '<div class="adminai-risk-row__bar"><span class="' + css + '" style="width:' + pct + '%"></span></div>'
                + '<span class="adminai-risk-row__value">' + escapeHtml(formatNumber(safe)) + '</span>'
                + '</div>';
        }
        $panel.find('.adminai-risk-monitor').html(
            riskBar('Low Risk', risk.low, 'adminai-risk-low')
            + riskBar('Medium Risk', risk.medium, 'adminai-risk-medium')
            + riskBar('High Risk', risk.high, 'adminai-risk-high')
        );

        var newCustomers = Number(acq.new_customers || 0);
        var returning = Number(acq.returning_customers || 0);
        var total = newCustomers + returning;
        var newPct = total > 0 ? Math.round((newCustomers / total) * 100) : 0;
        var returnPct = total > 0 ? Math.round((returning / total) * 100) : 0;
        $panel.find('.adminai-acq-retention').html(
            '<div class="adminai-acq-retention__row"><span>New Customers</span><strong>' + escapeHtml(formatNumber(newCustomers)) + ' (' + newPct + '%)</strong></div>'
            + '<div class="adminai-acq-retention__row"><span>Returning Customers</span><strong>' + escapeHtml(formatNumber(returning)) + ' (' + returnPct + '%)</strong></div>'
        );

        $panel.find('.adminai-alerts-summary').html(
            '<div class="adminai-alert-pill">Unread Notifications: <strong>' + escapeHtml(formatNumber(alerts.unread_notifications || 0)) + '</strong></div>'
        );

        var feedHtml = '';
        feed.forEach(function (item) {
            feedHtml += '<div class="adminai-alert-item">';
            feedHtml += '<span class="adminai-alert-item__severity">' + escapeHtml(String(item.severity || 'info').toUpperCase()) + '</span>';
            feedHtml += '<span class="adminai-alert-item__title">' + escapeHtml(item.title || '') + '</span>';
            feedHtml += '<span class="adminai-alert-item__time">' + escapeHtml(item.created_at || '') + '</span>';
            feedHtml += '</div>';
        });
        $panel.find('.adminai-alert-feed').html(feedHtml || '<div class="adminai-empty">No active alerts.</div>');
    }

    function renderSalesWidgets($root, widgets) {
        var $panel = $root.closest('.adminai-content__panel');
        var summary = Array.isArray(widgets.executive_sales_summary) ? widgets.executive_sales_summary : [];
        renderListWidget($panel.find('[data-widget="executive_sales_summary"]'), summary);

        updateWidgetTableData('top_sold_products', widgets.top_sold_products || []);
        renderWidgetTable($root, 'top_sold_products');
    }

    function renderSkuTracker($root, tracker) {
        var $panel = $root.closest('.adminai-content__panel');
        var data = tracker || {};
        var period = data.period || {};
        var product = data.product || null;
        var children = Array.isArray(data.children) ? data.children : [];
        var kpis = data.kpis || {};

        var $sku = $panel.find('.adminai-sku-input');
        var $from = $panel.find('.adminai-sku-from');
        var $to = $panel.find('.adminai-sku-to');

        if ($sku.length && document.activeElement !== $sku[0]) {
            $sku.val(state.skuTracker.sku || '');
        }
        if ($from.length && document.activeElement !== $from[0]) {
            $from.val(state.skuTracker.fromDate || '');
        }
        if ($to.length && document.activeElement !== $to[0]) {
            $to.val(state.skuTracker.toDate || '');
        }

        var periodLabel = period.mode === 'range' && period.from_date && period.to_date
            ? ('Range: ' + period.from_date + ' to ' + period.to_date)
            : 'Range: All-time';
        var metaHtml = '<div class="adminai-sku-meta-pill">' + escapeHtml(periodLabel) + '</div>';
        if (data.message) {
            metaHtml += '<div class="adminai-empty">' + escapeHtml(data.message) + '</div>';
        }
        $panel.find('[data-widget="sku_tracker_meta"]').html(metaHtml);

        if (!data.found || !product) {
            $panel.find('[data-widget="sku_tracker_product"]').html('');
            $panel.find('[data-widget="sku_tracker_children"]').html('');
            $panel.find('[data-widget="sku_tracker_kpis"]').html('');
            updateWidgetTableData('sku_tracker_orders', []);
            renderWidgetTable($root, 'sku_tracker_orders');
            return;
        }

        var imageHtml = product.image_url
            ? '<img src="' + escapeHtml(product.image_url) + '" alt="' + escapeHtml(product.sku || '') + '" />'
            : '<div class="adminai-sku-image__placeholder">No Image</div>';
        var stockBadge = product.is_out_of_stock
            ? '<span class="adminai-stock-badge is-out">Out Of Stock</span>'
            : '<span class="adminai-stock-badge is-in">In Stock</span>';

        var productHtml = '';
        productHtml += '<div class="adminai-sku-product-card">';
        productHtml += '<div class="adminai-sku-image">' + imageHtml + '</div>';
        productHtml += '<div class="adminai-sku-product-info">';
        productHtml += '<h4>' + escapeHtml(product.name || product.sku || '') + '</h4>';
        productHtml += '<div><strong>ID:</strong> ' + escapeHtml(product.id) + '</div>';
        productHtml += '<div><strong>SKU:</strong> ' + escapeHtml(product.sku || '') + '</div>';
        productHtml += '<div><strong>Type:</strong> ' + escapeHtml(product.type || '') + '</div>';
        productHtml += '<div><strong>Price:</strong> ' + escapeHtml(formatCurrency(product.price)) + '</div>';
        productHtml += '<div><strong>Stock:</strong> ' + escapeHtml(formatNumber(product.stock_qty)) + ' ' + stockBadge + '</div>';
        productHtml += '</div>';
        productHtml += '</div>';
        $panel.find('[data-widget="sku_tracker_product"]').html(productHtml);

        var childrenHtml = '';
        if (children.length) {
            childrenHtml += '<h4 class="adminai-sku-subtitle">Simple Products</h4>';
            childrenHtml += '<div class="adminai-sku-children-grid">';
            children.forEach(function (child) {
                var childImg = child.image_url
                    ? '<img src="' + escapeHtml(child.image_url) + '" alt="' + escapeHtml(child.sku || '') + '" />'
                    : '<div class="adminai-sku-image__placeholder">No Image</div>';
                var childBadge = child.is_out_of_stock
                    ? '<span class="adminai-stock-badge is-out">Out Of Stock</span>'
                    : '<span class="adminai-stock-badge is-in">In Stock</span>';

                childrenHtml += '<div class="adminai-sku-child-card">';
                childrenHtml += '<div class="adminai-sku-child-card__image">' + childImg + '</div>';
                childrenHtml += '<div class="adminai-sku-child-card__meta">';
                childrenHtml += '<div><strong>' + escapeHtml(child.sku || '') + '</strong></div>';
                childrenHtml += '<div>' + escapeHtml(child.name || '') + '</div>';
                childrenHtml += '<div>Price: ' + escapeHtml(formatCurrency(child.price)) + '</div>';
                childrenHtml += '<div>Stock: ' + escapeHtml(formatNumber(child.stock_qty)) + ' ' + childBadge + '</div>';
                childrenHtml += '</div>';
                childrenHtml += '</div>';
            });
            childrenHtml += '</div>';
        }
        $panel.find('[data-widget="sku_tracker_children"]').html(childrenHtml);

        var kpiCards = [
            { label: 'Total Revenue', value: formatCurrency(kpis.total_revenue) },
            { label: 'Total Qty Sold', value: formatNumber(kpis.total_qty_sold) },
            { label: 'Total Orders', value: formatNumber(kpis.total_orders) },
            { label: 'Lowest Price (Order)', value: formatCurrency(kpis.lowest_price) },
            { label: 'Highest Price (Order)', value: formatCurrency(kpis.highest_price) }
        ];

        var kpiHtml = '<div class="adminai-sku-kpis-grid">';
        kpiCards.forEach(function (kpi) {
            kpiHtml += '<div class="adminai-kpi-item">';
            kpiHtml += '<div class="adminai-kpi-label">' + escapeHtml(kpi.label) + '</div>';
            kpiHtml += '<div class="adminai-kpi-value">' + escapeHtml(kpi.value) + '</div>';
            kpiHtml += '</div>';
        });
        kpiHtml += '</div>';
        $panel.find('[data-widget="sku_tracker_kpis"]').html(kpiHtml);

        updateWidgetTableData('sku_tracker_orders', data.orders || []);
        renderWidgetTable($root, 'sku_tracker_orders');
    }

    function renderProductWidgets($root, widgets) {
        updateWidgetTableData('trending_products', widgets.trending_products || []);
        updateWidgetTableData('stock_projection', widgets.stock_projection || [], {
            projectionRangeDays: Number(widgets.stock_projection_range_days || 90)
        });
        updateWidgetTableData('slow_moving_products', widgets.slow_moving_products || []);
        renderSkuTracker($root, widgets.sku_tracker || {});

        var stockProjectionState = state.widgetTables.stock_projection || {};
        $root.data('stock-projection-days', Number(stockProjectionState.projectionRangeDays || 90));

        renderWidgetTable($root, 'trending_products');
        renderWidgetTable($root, 'stock_projection');
        renderWidgetTable($root, 'slow_moving_products');
    }

    function renderNotificationWidgets($root, widgets) {
        var rec = Array.isArray(widgets.action_recommendations) ? widgets.action_recommendations : [];
        var $panel = $root.closest('.adminai-content__panel');
        renderListWidget($panel.find('[data-widget="action_recommendations"]'), rec);

        updateWidgetTableData('severity_summary', widgets.severity_summary || []);
        renderWidgetTable($root, 'severity_summary');
    }

    function renderCustomerLookup($root, lookup) {
        var $panel = $root.closest('.adminai-content__panel');
        var data = lookup || {};
        var customer = data.customer || null;
        var addresses = Array.isArray(data.addresses) ? data.addresses : [];
        var recentBuys = Array.isArray(data.recent_buys) ? data.recent_buys : [];

        var $input = $panel.find('.adminai-customer-lookup-input');
        if ($input.length && document.activeElement !== $input[0]) {
            $input.val(state.customerLookupQuery || '');
        }

        var metaHtml = '';
        if (data.query) {
            metaHtml += '<div class="adminai-sku-meta-pill">Search: ' + escapeHtml(data.query) + '</div>';
        }
        if (data.message) {
            metaHtml += '<div class="adminai-empty">' + escapeHtml(data.message) + '</div>';
        }
        $panel.find('[data-widget="customer_lookup_meta"]').html(metaHtml);

        if (!data.found || !customer) {
            $panel.find('[data-widget="customer_lookup_profile"]').html('');
            $panel.find('[data-widget="customer_lookup_addresses"]').html('');
            $panel.find('[data-widget="customer_lookup_recent"]').html('');
            updateWidgetTableData('customer_lookup_orders', []);
            renderWidgetTable($root, 'customer_lookup_orders');
            return;
        }

        var profileItems = [
            { label: 'Customer ID', value: customer.customer_id },
            { label: 'Name', value: customer.name || '-' },
            { label: 'Email', value: customer.email || '-' },
            { label: 'Customer Since', value: customer.customer_since || '-' },
            { label: 'Customer Group', value: customer.group_id },
            { label: 'Date Of Birth', value: customer.dob || '-' },
            { label: 'Gender', value: customer.gender || '-' },
            { label: 'Orders', value: formatNumber(customer.orders_count || 0) },
            { label: 'Total Spent', value: formatCurrency(customer.total_spent || 0) },
            { label: 'Last Order', value: customer.last_order_at || '-' }
        ];
        var profileHtml = '<div class="adminai-customer-profile-grid">';
        profileItems.forEach(function (item) {
            profileHtml += '<div class="adminai-kpi-item">';
            profileHtml += '<div class="adminai-kpi-label">' + escapeHtml(item.label) + '</div>';
            profileHtml += '<div class="adminai-kpi-value">' + escapeHtml(item.value) + '</div>';
            profileHtml += '</div>';
        });
        profileHtml += '</div>';
        $panel.find('[data-widget="customer_lookup_profile"]').html(profileHtml);

        var addressHtml = '';
        if (addresses.length) {
            addressHtml += '<h4 class="adminai-sku-subtitle">Addresses</h4>';
            addressHtml += '<div class="adminai-customer-address-grid">';
            addresses.forEach(function (address) {
                addressHtml += '<div class="adminai-customer-address-card">';
                addressHtml += '<div class="adminai-customer-address-card__type">' + escapeHtml(address.type || 'Address') + '</div>';
                addressHtml += '<div><strong>' + escapeHtml(address.name || '-') + '</strong></div>';
                if (address.company) {
                    addressHtml += '<div>' + escapeHtml(address.company) + '</div>';
                }
                addressHtml += '<div>' + escapeHtml(address.street || '-') + '</div>';
                addressHtml += '<div>' + escapeHtml([address.city, address.region, address.postcode].filter(Boolean).join(', ')) + '</div>';
                addressHtml += '<div>' + escapeHtml(address.country || '-') + '</div>';
                addressHtml += '<div>' + escapeHtml(address.telephone || '-') + '</div>';
                addressHtml += '</div>';
            });
            addressHtml += '</div>';
        }
        $panel.find('[data-widget="customer_lookup_addresses"]').html(addressHtml || '<div class="adminai-empty">No saved addresses found.</div>');

        var recentHtml = '';
        if (recentBuys.length) {
            recentHtml += '<h4 class="adminai-sku-subtitle">Bought In Last 90 Days</h4>';
            recentHtml += '<div class="adminai-customer-recent-grid">';
            recentBuys.forEach(function (row) {
                recentHtml += '<div class="adminai-customer-recent-card">';
                recentHtml += '<div class="adminai-customer-recent-card__sku">' + escapeHtml(row.sku || '-') + '</div>';
                if (row.name) {
                    recentHtml += '<div>' + escapeHtml(row.name) + '</div>';
                }
                recentHtml += '<div>Qty: ' + escapeHtml(formatNumber(row.qty_ordered || 0)) + '</div>';
                recentHtml += '<div>Orders: ' + escapeHtml(formatNumber(row.orders_count || 0)) + '</div>';
                recentHtml += '</div>';
            });
            recentHtml += '</div>';
        }
        $panel.find('[data-widget="customer_lookup_recent"]').html(recentHtml || '<div class="adminai-empty">No purchases found in the last 90 days.</div>');

        updateWidgetTableData('customer_lookup_orders', data.orders || []);
        renderWidgetTable($root, 'customer_lookup_orders');
    }

    function renderCustomerWidgets($root, widgets, summary) {
        var $panel = $root.closest('.adminai-content__panel');
        var safeSummary = summary || {};
        var activeRows = widgets.customer_active || [];
        var periodLabel = String(safeSummary.period_label || 'Last 90 Days');
        var cards = [
            { label: 'Active Customers', value: formatNumber(safeSummary.active_customers || 0) },
            { label: 'Inactive Customers', value: formatNumber(safeSummary.inactive_customers || 0) },
            { label: 'New Customers In Window', value: formatNumber(safeSummary.new_customers || 0) }
        ];
        var cardsHtml = '<div class="adminai-customer-summary__grid">';
        cards.forEach(function (card) {
            cardsHtml += '<div class="adminai-kpi-item">';
            cardsHtml += '<div class="adminai-kpi-label">' + escapeHtml(card.label) + '</div>';
            cardsHtml += '<div class="adminai-kpi-value">' + escapeHtml(card.value) + '</div>';
            cardsHtml += '</div>';
        });
        cardsHtml += '</div>';
        cardsHtml += '<div class="adminai-meta-line">Window: <strong>' + escapeHtml(periodLabel) + '</strong></div>';
        $panel.find('[data-widget="customer_summary_cards"]').html(cardsHtml);

        var $periodSelect = $panel.find('.adminai-customer-period');
        if ($periodSelect.length && document.activeElement !== $periodSelect[0]) {
            $periodSelect.val(state.customerPeriod || '90d');
        }

        renderCustomerOrderDistributionChart($panel, activeRows);
        renderCustomerEngagementBars($panel, safeSummary);
        renderListWidget($panel.find('[data-widget="customer_recent_purchases"]'), widgets.customer_recent_purchases || []);
        updateWidgetTableData('customer_active', activeRows);
        updateWidgetTableData('customer_inactive', widgets.customer_inactive || []);
        updateWidgetTableData('customer_new_customers', widgets.customer_new_customers || []);
        updateWidgetTableData('customer_frequent_buyers', widgets.customer_frequent_buyers || []);
        updateWidgetTableData('customer_high_value_customers', widgets.customer_high_value_customers || []);
        updateWidgetTableData('customer_discount_hunters', widgets.customer_discount_hunters || []);
        updateWidgetTableData('customer_category_loyal_customers', widgets.customer_category_loyal_customers || []);
        updateWidgetTableData('customer_new_arrival_buyers', widgets.customer_new_arrival_buyers || []);
        updateWidgetTableData('customer_at_risk_customers', widgets.customer_at_risk_customers || []);
        updateWidgetTableData('customer_reactivated_customers', widgets.customer_reactivated_customers || []);
        updateWidgetTableData('customer_one_time_buyers', widgets.customer_one_time_buyers || []);
        updateWidgetTableData('customer_lost_customers', widgets.customer_lost_customers || []);
        updateWidgetTableData('customer_zero_price_purchases', widgets.customer_zero_price_purchases || []);
        renderWidgetTable($root, 'customer_active');
        renderWidgetTable($root, 'customer_inactive');
        renderWidgetTable($root, 'customer_new_customers');
        renderWidgetTable($root, 'customer_frequent_buyers');
        renderWidgetTable($root, 'customer_high_value_customers');
        renderWidgetTable($root, 'customer_discount_hunters');
        renderWidgetTable($root, 'customer_category_loyal_customers');
        renderWidgetTable($root, 'customer_new_arrival_buyers');
        renderWidgetTable($root, 'customer_at_risk_customers');
        renderWidgetTable($root, 'customer_reactivated_customers');
        renderWidgetTable($root, 'customer_one_time_buyers');
        renderWidgetTable($root, 'customer_lost_customers');
        renderWidgetTable($root, 'customer_zero_price_purchases');
        renderCustomerLookup($root, widgets.customer_lookup || {});
    }

    function renderSectionWidgets($root, section, widgets, summary) {
        if (section === 'sales') {
            renderSalesWidgets($root, widgets || {});
            return;
        }
        if (section === 'products') {
            renderProductWidgets($root, widgets || {});
            return;
        }
        if (section === 'customers') {
            renderCustomerWidgets($root, widgets || {}, summary || {});
            return;
        }
        if (section === 'notifications') {
            renderNotificationWidgets($root, widgets || {});
        }
    }

    function escapeHtml(value) {
        return $('<div/>').text(value == null ? '' : String(value)).html();
    }

    function renderSummary($root, summary, meta) {
        var $summary = $root.find('.adminai-data-summary');
        var html = '';
        var keys = Object.keys(summary || {});

        if (!keys.length) {
            $summary.html('<div class="adminai-empty">No summary data available.</div>');
            return;
        }

        html += '<div class="adminai-meta-line">';
        html += 'Cache: <strong>' + ((meta && meta.cached) ? 'HIT' : 'MISS') + '</strong>';
        html += '</div>';
        html += '<div class="adminai-kpi-grid">';

        keys.forEach(function (key) {
            var value = summary[key];
            html += '<div class="adminai-kpi-item">';
            html += '<div class="adminai-kpi-label">' + escapeHtml(key.replace(/_/g, ' ')) + '</div>';
            html += '<div class="adminai-kpi-value">' + escapeHtml(value) + '</div>';
            html += '</div>';
        });

        html += '</div>';
        $summary.html(html);
    }

    function getPageSlice(rows) {
        var safeRows = Array.isArray(rows) ? rows : [];
        var total = safeRows.length;
        var pages = Math.max(1, Math.ceil(total / state.pageSize));

        if (state.currentPage > pages) {
            state.currentPage = pages;
        }
        if (state.currentPage < 1) {
            state.currentPage = 1;
        }

        var start = (state.currentPage - 1) * state.pageSize;
        var end = start + state.pageSize;

        return {
            rows: safeRows.slice(start, end),
            totalRows: total,
            totalPages: pages,
            start: total === 0 ? 0 : start + 1,
            end: Math.min(end, total)
        };
    }

    function parseSortValue(value) {
        if (value == null) {
            return { type: 'text', value: '' };
        }

        var raw = String(value).trim();
        if (raw === '') {
            return { type: 'text', value: '' };
        }

        if (/^-?\d+(\.\d+)?$/.test(raw)) {
            return { type: 'number', value: parseFloat(raw) };
        }

        if (/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/.test(raw)) {
            var time = Date.parse(raw.replace(' ', 'T'));
            if (!isNaN(time)) {
                return { type: 'number', value: time };
            }
        }

        return { type: 'text', value: raw.toLowerCase() };
    }

    function formatDaysRemainingRange(value) {
        var num = Number(value || 0);
        if (!isFinite(num) || num < 0) {
            num = 0;
        }

        var lower = Math.floor(num);
        var upper = lower + 1;
        return String(lower) + '-' + String(upper);
    }

    function getSortedRows(rows) {
        return getSortedRowsBy(rows, state.sortKey, state.sortDirection);
    }

    function updatePaginationUi($root, pageInfo) {
        var $info = $root.find('.adminai-page-info');
        var $prev = $root.find('.adminai-page-prev');
        var $next = $root.find('.adminai-page-next');

        $info.text(
            'Page ' + state.currentPage + ' of ' + pageInfo.totalPages
            + ' (Rows ' + pageInfo.start + '-' + pageInfo.end + ' of ' + pageInfo.totalRows + ')'
        );

        $prev.prop('disabled', state.currentPage <= 1);
        $next.prop('disabled', state.currentPage >= pageInfo.totalPages);
    }

    function renderGrid($root, rows) {
        var $grid = $root.find('.adminai-data-grid');
        var html = '';
        var sortedRows = getSortedRows(rows);
        var pageInfo = getPageSlice(sortedRows);
        var pageRows = pageInfo.rows;

        updatePaginationUi($root, pageInfo);

        if (!pageRows.length) {
            $grid.html('<div class="adminai-empty">No records found for selected filters.</div>');
            return;
        }

        var headers = Object.keys(pageRows[0] || {});
        html += '<div class="adminai-grid-wrap"><table class="adminai-grid-table"><thead><tr>';
        headers.forEach(function (header) {
            var isActiveSort = state.sortKey === header;
            var arrow = isActiveSort ? (state.sortDirection === 'asc' ? ' ▲' : ' ▼') : '';
            var classes = 'adminai-grid-sortable' + (isActiveSort ? ' is-sorted' : '');
            html += '<th class="' + classes + '" data-sort-key="' + escapeHtml(header) + '">'
                + escapeHtml(header.replace(/_/g, ' ')) + arrow + '</th>';
        });
        html += '</tr></thead><tbody>';

        pageRows.forEach(function (row) {
            html += '<tr>';
            headers.forEach(function (header) {
                html += '<td>' + escapeHtml(formatTableCellValue(header, row[header])) + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $grid.html(html);
    }

    function renderResponse(section, response) {
        var $root = $('#adminai-dashboard-root');
        if (!$root.length) {
            return;
        }

        var gridTitles = {
            overview: 'Detailed Records',
            sales: 'Per Day Sale',
            products: 'Detailed Product Records',
            customers: 'Customer Activity',
            notifications: 'Detailed Notification Records'
        };
        $root.find('.adminai-grid-title').text(gridTitles[section] || 'Detailed Records');

        setOverviewVisibility($root, section === 'overview');
        setSectionZoneVisibility($root, section);
        setToolbarVisibility($root, section);
        setMainGridVisibility($root, section !== 'customers');
        if (section === 'overview') {
            renderOverviewWidgets($root, response.widgets || {});
        } else {
            renderSectionWidgets($root, section, response.widgets || {}, response.summary || {});
        }

        state.rows = Array.isArray(response.grid) ? response.grid : [];
        if (state.sortKey && state.rows.length && !(state.sortKey in state.rows[0])) {
            state.sortKey = null;
            state.sortDirection = 'asc';
        }
        renderSummary($root, response.summary || {}, response.meta || {});
        renderGrid($root, state.rows);
        chartManager.render(section, response || {});
    }

    function renderError(message) {
        var $root = $('#adminai-dashboard-root');
        if (!$root.length) {
            return;
        }

        $root.find('.adminai-data-summary').html(
            '<div class="adminai-empty">' + escapeHtml(message || 'Unable to load dashboard data.') + '</div>'
        );
        state.rows = [];
        renderGrid($root, []);
        chartManager.clear();
    }

    function collectFilters($root) {
        var $panel = $root.closest('.adminai-content__panel');
        var section = String($root.data('section') || 'overview');
        var filters = {
            range: String($panel.find('.adminai-filter-range').val() || ''),
            from_date: String($panel.find('.adminai-filter-from').val() || ''),
            to_date: String($panel.find('.adminai-filter-to').val() || ''),
            store_id: String($panel.find('.adminai-filter-store').val() || ''),
            segment_code: String($root.data('segment-code') || '')
        };

        if (section === 'products') {
            var stockProjectionDays = Number(
                (state.widgetTables.stock_projection && state.widgetTables.stock_projection.projectionRangeDays)
                || $root.data('stock-projection-days')
                || 90
            );
            if (!isFinite(stockProjectionDays) || [90, 180, 360].indexOf(stockProjectionDays) === -1) {
                stockProjectionDays = 90;
            }
            filters.stock_projection_days = String(stockProjectionDays);

            if (state.skuTracker.sku) {
                filters.sku_tracker_sku = String(state.skuTracker.sku);
            }
            if (state.skuTracker.fromDate && state.skuTracker.toDate) {
                filters.sku_tracker_from_date = String(state.skuTracker.fromDate);
                filters.sku_tracker_to_date = String(state.skuTracker.toDate);
            }
        } else if (section === 'customers') {
            filters.customer_period = String(state.customerPeriod || '90d');
            if (state.customerLookupQuery) {
                filters.customer_lookup_query = String(state.customerLookupQuery);
            }
        }

        Object.keys(filters).forEach(function (key) {
            if (!filters[key]) {
                delete filters[key];
            }
        });

        return filters;
    }

    function fillControlsFromUrl($root) {
        var $panel = $root.closest('.adminai-content__panel');
        var params = new URLSearchParams(window.location.search || '');
        var range = params.get('range') || '30d';
        $panel.find('.adminai-filter-range').val(range);
        $panel.find('.adminai-filter-from').val(params.get('from_date') || '');
        $panel.find('.adminai-filter-to').val(params.get('to_date') || '');
        $panel.find('.adminai-filter-store').val(params.get('store_id') || '');
        $root.data('segment-code', params.get('segment_code') || '');
        $root.data('stock-projection-days', Number(params.get('stock_projection_days') || '90'));
        state.skuTracker.sku = String(params.get('sku_tracker_sku') || '').toUpperCase();
        state.skuTracker.fromDate = String(params.get('sku_tracker_from_date') || '');
        state.skuTracker.toDate = String(params.get('sku_tracker_to_date') || '');
        state.customerPeriod = String(params.get('customer_period') || '90d');
        state.customerLookupQuery = String(params.get('customer_lookup_query') || '');

        $panel.find('.adminai-filter-from, .adminai-filter-to').prop('disabled', range !== 'custom');
        $panel.find('.adminai-customer-period').val(state.customerPeriod);
        $panel.find('.adminai-customer-lookup-input').val(state.customerLookupQuery);

        var pageSize = parseInt(String(params.get('page_size') || '20'), 10);
        if (!isNaN(pageSize) && pageSize > 0) {
            state.pageSize = pageSize;
        }
        $root.find('.adminai-page-size').val(String(state.pageSize));
    }

    function updateUrl(filters, pageSize) {
        var params = new URLSearchParams(window.location.search || '');
        ['range', 'from_date', 'to_date', 'store_id', 'segment_code', 'stock_projection_days', 'sku_tracker_sku', 'sku_tracker_from_date', 'sku_tracker_to_date', 'customer_period', 'customer_lookup_query'].forEach(function (key) {
            if (filters[key]) {
                params.set(key, filters[key]);
            } else {
                params.delete(key);
            }
        });

        params.set('page_size', String(pageSize));

        var newUrl = window.location.pathname + '?' + params.toString();
        window.history.replaceState({}, '', newUrl);
    }

    function getEndpointForSection($root, section) {
        var endpointMap = {
            overview: $root.data('url-overview'),
            sales: $root.data('url-sales'),
            products: $root.data('url-products'),
            customers: $root.data('url-customers'),
            notifications: $root.data('url-notifications')
        };

        return endpointMap[section] || endpointMap.overview || '';
    }

    function loadSectionData($root, forceReload) {
        if (!$root.length) {
            return;
        }
        if ($root.data('dashboard-data-loading')) {
            return;
        }

        if ($root.data('dashboard-data-loaded') && !forceReload) {
            return;
        }

        $root.data('dashboard-data-loading', true);
        toggleGlobalLoader(true);

        var section = String($root.data('section') || 'overview');
        var endpoint = getEndpointForSection($root, section);

        if (!endpoint) {
            console.warn('AdminAI Dashboard: No endpoint configured for section', section);
            renderError('No endpoint configured for current section.');
            $root.data('dashboard-data-loading', false);
            toggleGlobalLoader(false);
            return;
        }

        var filters = collectFilters($root);
        updateUrl(filters, state.pageSize);

        $.ajax({
            url: endpoint,
            method: 'GET',
            data: filters,
            dataType: 'json'
        }).done(function (response) {
            console.log('AdminAI Dashboard payload [' + section + ']:', response);
            state.currentPage = 1;
            renderResponse(section, response || {});
            $root.data('dashboard-data-loaded', true);
        }).fail(function (xhr) {
            console.error('AdminAI Dashboard request failed [' + section + ']:', xhr.status, xhr.responseText);
            renderError('Request failed (' + xhr.status + ').');
        }).always(function () {
            $root.data('dashboard-data-loading', false);
            toggleGlobalLoader(false);
        });
    }

    function bindUiEvents($root) {
        var $panel = $root.closest('.adminai-content__panel');

        function applyRangeDefaults() {
            var range = String($panel.find('.adminai-filter-range').val() || '30d');
            var isCustom = range === 'custom';
            $panel.find('.adminai-filter-from, .adminai-filter-to').prop('disabled', !isCustom);
            if (range === 'custom') {
                return;
            }
            var daysMap = { '7d': 6, '30d': 29, '90d': 89 };
            var offset = daysMap[range] != null ? daysMap[range] : 29;
            var to = new Date();
            var from = new Date();
            from.setDate(to.getDate() - offset);

            function fmt(d) {
                var m = String(d.getMonth() + 1).padStart(2, '0');
                var day = String(d.getDate()).padStart(2, '0');
                return d.getFullYear() + '-' + m + '-' + day;
            }

            $panel.find('.adminai-filter-from').val(fmt(from));
            $panel.find('.adminai-filter-to').val(fmt(to));
        }

        $panel.on('change', '.adminai-filter-range', function () {
            applyRangeDefaults();
            loadSectionData($root, true);
        });

        $panel.on('click', '.adminai-filter-apply', function () {
            loadSectionData($root, true);
        });

        $panel.on('click', '.adminai-filter-reset', function () {
            $panel.find('.adminai-filter-range').val('30d');
            $panel.find('.adminai-filter-from').val('');
            $panel.find('.adminai-filter-to').val('');
            $panel.find('.adminai-filter-store').val('');
            $root.data('segment-code', '');
            state.currentPage = 1;
            applyRangeDefaults();
            loadSectionData($root, true);
        });

        $panel.on('click', '.adminai-grid-table th[data-sort-key]', function () {
            var key = String($(this).data('sortKey') || '').trim();
            if (!key) {
                return;
            }

            if (state.sortKey === key) {
                state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                state.sortKey = key;
                state.sortDirection = 'asc';
            }

            state.currentPage = 1;
            renderGrid($root, state.rows);
        });

        $panel.on('click', '.adminai-segment-card', function () {
            var segment = String($(this).data('segment') || '').trim();
            if (!segment) {
                return;
            }

            var params = new URLSearchParams(window.location.search || '');
            params.set('section', 'customers');
            params.set('segment_code', segment);
            var newUrl = window.location.pathname + '?' + params.toString();
            window.location.href = newUrl;
        });

        $panel.on('change', '.adminai-page-size', function () {
            var value = parseInt(String($(this).val() || '20'), 10);
            if (isNaN(value) || value <= 0) {
                value = 20;
            }
            state.pageSize = value;
            state.currentPage = 1;
            updateUrl(collectFilters($root), state.pageSize);
            renderGrid($root, state.rows);
        });

        $panel.on('click', '.adminai-page-prev', function () {
            state.currentPage = Math.max(1, state.currentPage - 1);
            renderGrid($root, state.rows);
        });

        $panel.on('click', '.adminai-page-next', function () {
            state.currentPage = state.currentPage + 1;
            renderGrid($root, state.rows);
        });

        $panel.on('click', '.adminai-widget-table-sort', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            var sortKey = String($(this).data('widgetSortKey') || '').trim();
            if (!widgetKey || !sortKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var widgetState = state.widgetTables[widgetKey];
            if (widgetState.sortKey === sortKey) {
                widgetState.sortDirection = widgetState.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                widgetState.sortKey = sortKey;
                widgetState.sortDirection = 'asc';
            }
            widgetState.currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-page-size', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var value = parseInt(String($(this).val() || '10'), 10);
            if (isNaN(value) || value <= 0) {
                value = 10;
            }

            state.widgetTables[widgetKey].pageSize = value;
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('click', '.adminai-widget-page-prev', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var widgetState = state.widgetTables[widgetKey];
            widgetState.currentPage = Math.max(1, Number(widgetState.currentPage || 1) - 1);
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('click', '.adminai-widget-page-next', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var widgetState = state.widgetTables[widgetKey];
            widgetState.currentPage = Number(widgetState.currentPage || 1) + 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('click', '.adminai-widget-export', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var widgetState = state.widgetTables[widgetKey];
            var fileDate = new Date().toISOString().slice(0, 10);
            var csvRows = getWidgetExportRows(widgetKey, widgetState);
            var csv = buildCsv(csvRows);
            downloadCsv('adminai_' + widgetKey + '_' + fileDate + '.csv', csv);
        });

        $panel.on('input', '.adminai-widget-search', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            state.widgetTables[widgetKey].searchQuery = String($(this).val() || '');
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-brand-filter', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            state.widgetTables[widgetKey].brandFilter = String($(this).val() || '').trim();
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-attribute-set-filter', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            state.widgetTables[widgetKey].attributeSetFilter = String($(this).val() || '').trim();
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-customer-since-from', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            state.widgetTables[widgetKey].customerSinceFrom = String($(this).val() || '').trim();
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-customer-since-to', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            state.widgetTables[widgetKey].customerSinceTo = String($(this).val() || '').trim();
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-days-threshold', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var value = Number($(this).val());
            if (!isFinite(value) || value <= 0) {
                value = 15;
            }

            state.widgetTables[widgetKey].daysThreshold = value;
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('change', '.adminai-widget-range-days', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            var value = Number($(this).val());
            if (!isFinite(value) || [90, 180, 360].indexOf(value) === -1) {
                value = 90;
            }

            state.widgetTables[widgetKey].projectionRangeDays = value;
            state.widgetTables[widgetKey].currentPage = 1;
            $root.data('stock-projection-days', value);
            loadSectionData($root, true);
        });

        $panel.on('change', '.adminai-widget-oos-toggle', function () {
            var widgetKey = String($(this).data('widgetKey') || '').trim();
            if (!widgetKey || !state.widgetTables[widgetKey]) {
                return;
            }

            state.widgetTables[widgetKey].includeOutOfStock = $(this).is(':checked');
            state.widgetTables[widgetKey].currentPage = 1;
            renderWidgetTable($root, widgetKey);
        });

        $panel.on('click', '.adminai-sku-submit', function () {
            var sku = String($panel.find('.adminai-sku-input').val() || '').trim().toUpperCase();
            var fromDate = String($panel.find('.adminai-sku-from').val() || '').trim();
            var toDate = String($panel.find('.adminai-sku-to').val() || '').trim();

            state.skuTracker.sku = sku;
            if (fromDate && toDate) {
                state.skuTracker.fromDate = fromDate;
                state.skuTracker.toDate = toDate;
            } else {
                state.skuTracker.fromDate = '';
                state.skuTracker.toDate = '';
            }

            loadSectionData($root, true);
        });

        $panel.on('click', '.adminai-sku-clear', function () {
            state.skuTracker.sku = '';
            state.skuTracker.fromDate = '';
            state.skuTracker.toDate = '';
            $panel.find('.adminai-sku-input').val('');
            $panel.find('.adminai-sku-from').val('');
            $panel.find('.adminai-sku-to').val('');
            loadSectionData($root, true);
        });

        $panel.on('change', '.adminai-customer-period', function () {
            state.customerPeriod = String($(this).val() || '90d');
            loadSectionData($root, true);
        });

        $panel.on('click', '.adminai-customer-lookup-submit', function () {
            state.customerLookupQuery = String($panel.find('.adminai-customer-lookup-input').val() || '').trim();
            loadSectionData($root, true);
        });

        $panel.on('click', '.adminai-customer-lookup-clear', function () {
            state.customerLookupQuery = '';
            $panel.find('.adminai-customer-lookup-input').val('');
            loadSectionData($root, true);
        });
    }

    return function (config, element) {
        var $root = $(element || '#adminai-dashboard-root');
        if (!$root.length) {
            return;
        }

        fillControlsFromUrl($root);
        bindUiEvents($root);
        state.currentSection = String($root.data('section') || 'overview');
        if (state.currentSection === 'overview') {
            setOverviewVisibility($root, true);
        }
        loadSectionData($root, true);
        $(document).on('contentUpdated', function () {
            loadSectionData($root, false);
        });
    };
});