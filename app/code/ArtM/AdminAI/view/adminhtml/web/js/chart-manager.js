define(['jquery'], function ($) {
    'use strict';

    var activeCharts = {};

    function destroyChart(key) {
        if (activeCharts[key] && typeof activeCharts[key].destroy === 'function') {
            activeCharts[key].destroy();
        }
        delete activeCharts[key];
    }

    function destroyAllCharts() {
        destroyChart('sales');
        destroyChart('orders');
        destroyChart('products');
        destroyChart('customers');
        destroyChart('notifications');
        renderLegend('adminai-chart-legend-customers', [], []);
        renderLegend('adminai-chart-legend-notifications', [], []);
        renderSummaryCards('adminai-chart-meta-sales', [], [], true, 'Revenue');
        renderSummaryCards('adminai-chart-meta-orders', [], [], false, 'Orders');
        renderSummaryCards('adminai-chart-meta-sku', [], [], true, 'Revenue');
    }

    function showChartCard(section) {
        $('.adminai-chart-card').addClass('is-hidden');
        $('.adminai-chart-card[data-chart-section="' + section + '"]').removeClass('is-hidden');
    }

    function getCanvasContext(canvasId) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !canvas.getContext) {
            return null;
        }
        return canvas.getContext('2d');
    }

    function clearCanvas(ctx) {
        if (!ctx || !ctx.canvas) {
            return;
        }
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
    }

    function drawFallbackLine(canvasId, values) {
        var ctx = getCanvasContext(canvasId);
        if (!ctx) {
            return;
        }
        clearCanvas(ctx);

        var width = ctx.canvas.width;
        var height = ctx.canvas.height;
        var max = Math.max.apply(null, values.concat([1]));
        var step = values.length > 1 ? (width - 30) / (values.length - 1) : (width - 30);

        ctx.strokeStyle = '#007bdb';
        ctx.lineWidth = 2;
        ctx.beginPath();
        values.forEach(function (v, i) {
            var x = 15 + (i * step);
            var y = height - 15 - ((Number(v) || 0) / max) * (height - 30);
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();
    }

    function drawFallbackBars(canvasId, values) {
        var ctx = getCanvasContext(canvasId);
        if (!ctx) {
            return;
        }
        clearCanvas(ctx);

        var width = ctx.canvas.width;
        var height = ctx.canvas.height;
        var max = Math.max.apply(null, values.concat([1]));
        var barWidth = Math.max(8, Math.floor((width - 20) / Math.max(1, values.length)) - 4);

        values.forEach(function (v, i) {
            var barHeight = ((Number(v) || 0) / max) * (height - 25);
            var x = 10 + i * (barWidth + 4);
            var y = height - 10 - barHeight;
            ctx.fillStyle = '#f59d00';
            ctx.fillRect(x, y, barWidth, barHeight);
        });
    }

    function drawFallbackPie(canvasId, values) {
        var ctx = getCanvasContext(canvasId);
        if (!ctx) {
            return;
        }
        clearCanvas(ctx);

        var total = values.reduce(function (sum, v) { return sum + (Number(v) || 0); }, 0);
        if (total <= 0) {
            return;
        }

        var colors = ['#007bdb', '#f59d00', '#d9534f', '#6f42c1', '#5bc0de'];
        var centerX = ctx.canvas.width / 2;
        var centerY = ctx.canvas.height / 2;
        var radius = Math.max(20, Math.min(centerX, centerY) - 8);
        var start = -Math.PI / 2;

        values.forEach(function (v, i) {
            var val = Number(v) || 0;
            var angle = (val / total) * Math.PI * 2;
            var end = start + angle;
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, start, end);
            ctx.closePath();
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            start = end;
        });
    }

    function hasChartJs() {
        return typeof window.Chart !== 'undefined';
    }

    function renderLegend(containerId, labels, colors) {
        var el = document.getElementById(containerId);
        if (!el) {
            return;
        }

        var safeLabels = Array.isArray(labels) ? labels : [];
        var safeColors = Array.isArray(colors) ? colors : [];
        if (!safeLabels.length) {
            el.innerHTML = '';
            return;
        }

        var html = '';
        safeLabels.forEach(function (label, index) {
            var color = safeColors[index % safeColors.length] || '#999';
            html += '<span class="adminai-chart-legend__item">';
            html += '<span class="adminai-chart-legend__swatch" style="background:' + color + '"></span>';
            html += '<span>' + String(label) + '</span>';
            html += '</span>';
        });

        el.innerHTML = html;
    }

    function formatNumber(value) {
        var num = Number(value || 0);
        if (!isFinite(num)) {
            num = 0;
        }
        return num.toLocaleString('en-IN');
    }

    function formatCurrency(value) {
        var num = Number(value || 0);
        if (!isFinite(num)) {
            num = 0;
        }
        return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function parseDateValue(value) {
        var text = String(value || '').trim();
        if (!text) {
            return null;
        }
        var ts = Date.parse(text);
        if (!isNaN(ts)) {
            return new Date(ts);
        }
        var monthMatch = text.match(/^(\d{4})-(\d{2})$/);
        if (monthMatch) {
            return new Date(Number(monthMatch[1]), Number(monthMatch[2]) - 1, 1);
        }
        return null;
    }

    function formatDateShort(value) {
        var parsed = parseDateValue(value);
        if (!parsed) {
            return String(value || '');
        }
        var day = String(parsed.getDate()).padStart(2, '0');
        var month = parsed.toLocaleString('en-US', { month: 'short' });
        return day + ' ' + month;
    }

    function formatDateLong(value) {
        var parsed = parseDateValue(value);
        if (!parsed) {
            return String(value || '');
        }
        var day = String(parsed.getDate()).padStart(2, '0');
        var month = parsed.toLocaleString('en-US', { month: 'short' });
        return day + ' ' + month + ' ' + parsed.getFullYear();
    }

    function getSeriesData(chartData, fallbackLabel) {
        var labels = Array.isArray(chartData && chartData.labels) ? chartData.labels : [];
        var datasets = Array.isArray(chartData && chartData.datasets) ? chartData.datasets : [];
        if (!datasets.length) {
            datasets = [{
                label: fallbackLabel,
                data: Array.isArray(chartData && chartData.values) ? chartData.values : []
            }];
        }

        var firstDataset = datasets[0] || {};
        var firstValues = Array.isArray(firstDataset.data) ? firstDataset.data : [];
        var meta = (chartData && chartData.meta && typeof chartData.meta === 'object') ? chartData.meta : {};

        return {
            labels: labels,
            datasets: datasets,
            values: firstValues,
            meta: meta
        };
    }

    function renderSummaryCards(metaId, labels, values, isCurrency, metricLabel) {
        var el = document.getElementById(metaId);
        if (!el) {
            return;
        }

        var safeLabels = Array.isArray(labels) ? labels : [];
        var safeValues = Array.isArray(values) ? values : [];
        if (!safeLabels.length || !safeValues.length) {
            el.innerHTML = '<div class="adminai-empty">No chart points available.</div>';
            return;
        }

        var limit = Math.min(safeLabels.length, safeValues.length);
        var firstLabel = String(safeLabels[0] || '');
        var firstValue = Number(safeValues[0] || 0);
        var lastLabel = String(safeLabels[limit - 1] || '');
        var lastValue = Number(safeValues[limit - 1] || 0);

        var peakIndex = 0;
        var peakValue = Number(safeValues[0] || 0);
        for (var i = 1; i < limit; i++) {
            var candidate = Number(safeValues[i] || 0);
            if (candidate > peakValue) {
                peakValue = candidate;
                peakIndex = i;
            }
        }

        function valueText(v) {
            return isCurrency ? formatCurrency(v) : formatNumber(v);
        }

        var html = '';
        html += '<div class="adminai-chart-summary__card">';
        html += '<div class="adminai-chart-summary__label">Start ' + metricLabel + '</div>';
        html += '<div class="adminai-chart-summary__value">' + valueText(firstValue) + ' (' + formatDateShort(firstLabel) + ')</div>';
        html += '</div>';
        html += '<div class="adminai-chart-summary__card">';
        html += '<div class="adminai-chart-summary__label">Latest ' + metricLabel + '</div>';
        html += '<div class="adminai-chart-summary__value">' + valueText(lastValue) + ' (' + formatDateShort(lastLabel) + ')</div>';
        html += '</div>';
        html += '<div class="adminai-chart-summary__card">';
        html += '<div class="adminai-chart-summary__label">Peak ' + metricLabel + '</div>';
        html += '<div class="adminai-chart-summary__value">' + valueText(peakValue) + ' (' + formatDateShort(safeLabels[peakIndex] || '') + ')</div>';
        html += '</div>';
        el.innerHTML = html;
    }

    var hoverCrosshairPlugin = {
        id: 'adminaiHoverCrosshair',
        afterDraw: function (chart) {
            var tooltip = chart.tooltip;
            if (!tooltip || !tooltip.getActiveElements || !tooltip.getActiveElements().length) {
                return;
            }

            var activePoint = tooltip.getActiveElements()[0];
            if (!activePoint || !activePoint.element) {
                return;
            }

            var ctx = chart.ctx;
            var x = activePoint.element.x;
            var topY = chart.chartArea.top;
            var bottomY = chart.chartArea.bottom;

            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, topY);
            ctx.lineTo(x, bottomY);
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(0,0,0,0.25)';
            ctx.stroke();
            ctx.restore();
        }
    };

    function renderSales(response) {
        var revenueData = (response.charts && response.charts.revenue_trend) ? response.charts.revenue_trend : {};
        var orderData = (response.charts && response.charts.order_trend) ? response.charts.order_trend : {};
        var revenueSeries = getSeriesData(revenueData, 'Revenue');
        var orderSeries = getSeriesData(orderData, 'Orders');
        var revenueLabels = revenueSeries.labels;
        var revenueValues = revenueSeries.values;
        var orderLabels = orderSeries.labels;
        var orderValues = orderSeries.values;
        var revenueCanvas = document.getElementById('adminai-chart-sales');
        var orderCanvas = document.getElementById('adminai-chart-orders');

        destroyChart('sales');
        if (revenueCanvas) {
            activeCharts.sales = new window.Chart(revenueCanvas, {
                type: 'bar',
                plugins: [hoverCrosshairPlugin],
                data: {
                    labels: revenueLabels,
                    datasets: [{
                        label: (revenueSeries.datasets[0] && revenueSeries.datasets[0].label) || 'Revenue',
                        data: revenueValues,
                        borderColor: '#007bdb',
                        backgroundColor: 'rgba(0, 123, 219, 0.55)',
                        borderWidth: 1,
                        borderRadius: 2,
                        hoverBackgroundColor: 'rgba(0, 123, 219, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 280
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function (items) {
                                    var item = items && items[0] ? items[0] : null;
                                    return item ? 'Date: ' + formatDateLong(item.label) : '';
                                },
                                label: function (context) {
                                    return 'Revenue: ' + formatCurrency(context.parsed.y);
                                },
                                afterBody: function (items) {
                                    var item = items && items[0] ? items[0] : null;
                                    if (!item) {
                                        return [];
                                    }
                                    var index = item.dataIndex;
                                    var orders = Array.isArray(revenueSeries.meta.orders) ? revenueSeries.meta.orders : [];
                                    var itemsSold = Array.isArray(revenueSeries.meta.items) ? revenueSeries.meta.items : [];
                                    return [
                                        'Orders: ' + formatNumber(orders[index] || 0),
                                        'Items Sold: ' + formatNumber(itemsSold[index] || 0)
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                callback: function (value) {
                                    var label = this.getLabelForValue(value);
                                    return formatDateShort(label);
                                },
                                autoSkip: true,
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            ticks: {
                                callback: function (value) {
                                    return formatCurrency(value);
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.08)'
                            },
                            title: {
                                display: true,
                                text: 'Revenue (₹)'
                            }
                        }
                    }
                }
            });
        }
        renderSummaryCards('adminai-chart-meta-sales', revenueLabels, revenueValues, true, 'Revenue');

        destroyChart('orders');
        if (orderCanvas) {
            activeCharts.orders = new window.Chart(orderCanvas, {
                type: 'bar',
                plugins: [hoverCrosshairPlugin],
                data: {
                    labels: orderLabels,
                    datasets: [{
                        label: (orderSeries.datasets[0] && orderSeries.datasets[0].label) || 'Orders',
                        data: orderValues,
                        borderColor: '#f59d00',
                        backgroundColor: 'rgba(245, 157, 0, 0.6)',
                        borderWidth: 1,
                        borderRadius: 2,
                        hoverBackgroundColor: 'rgba(245, 157, 0, 0.82)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 280
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function (items) {
                                    var item = items && items[0] ? items[0] : null;
                                    return item ? 'Date: ' + formatDateLong(item.label) : '';
                                },
                                label: function (context) {
                                    return 'Orders: ' + formatNumber(context.parsed.y);
                                },
                                afterBody: function (items) {
                                    var item = items && items[0] ? items[0] : null;
                                    if (!item) {
                                        return [];
                                    }
                                    var index = item.dataIndex;
                                    var revenue = Array.isArray(orderSeries.meta.revenue) ? orderSeries.meta.revenue : [];
                                    var itemsSold = Array.isArray(orderSeries.meta.items) ? orderSeries.meta.items : [];
                                    return [
                                        'Revenue: ' + formatCurrency(revenue[index] || 0),
                                        'Items Sold: ' + formatNumber(itemsSold[index] || 0)
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                callback: function (value) {
                                    var label = this.getLabelForValue(value);
                                    return formatDateShort(label);
                                },
                                autoSkip: true,
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            ticks: {
                                callback: function (value) {
                                    return formatNumber(value);
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.08)'
                            },
                            title: {
                                display: true,
                                text: 'Orders'
                            }
                        }
                    }
                }
            });
        }
        renderSummaryCards('adminai-chart-meta-orders', orderLabels, orderValues, false, 'Orders');
    }

    function renderProducts(response) {
        var tracker = (response.widgets && response.widgets.sku_tracker) ? response.widgets.sku_tracker : {};
        var chartData = tracker.chart || {};
        var series = getSeriesData(chartData, 'Revenue');
        var labels = series.labels;
        var values = series.values;
        var canvas = document.getElementById('adminai-chart-sku-performance');
        if (!canvas) {
            return;
        }

        destroyChart('products');
        activeCharts.products = new window.Chart(canvas, {
            type: 'bar',
            plugins: [hoverCrosshairPlugin],
            data: {
                labels: labels,
                datasets: [{
                    label: (series.datasets[0] && series.datasets[0].label) || 'Revenue',
                    data: values,
                    backgroundColor: '#f59d00',
                    borderColor: '#d28400',
                    borderWidth: 1,
                    borderRadius: 2,
                    hoverBackgroundColor: '#dd8d00'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 280
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                var item = items && items[0] ? items[0] : null;
                                return item ? 'Date: ' + formatDateLong(item.label) : '';
                            },
                            label: function (context) {
                                return 'Revenue: ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function (value) {
                                var label = this.getLabelForValue(value);
                                return formatDateShort(label);
                            },
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        ticks: {
                            callback: function (value) {
                                return formatCurrency(value);
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.08)'
                        },
                        title: {
                            display: true,
                            text: 'Revenue (₹)'
                        }
                    }
                }
            }
        });
        renderSummaryCards('adminai-chart-meta-sku', labels, values, true, 'Revenue');
    }

    function renderCustomers(response) {
        var chartData = (response.charts && response.charts.risk_distribution) ? response.charts.risk_distribution : {};
        var labels = Array.isArray(chartData.labels) ? chartData.labels : [];
        var values = Array.isArray(chartData.values) ? chartData.values : [];
        var canvas = document.getElementById('adminai-chart-customers');
        if (!canvas) {
            return;
        }

        destroyChart('customers');
        activeCharts.customers = new window.Chart(canvas, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#007bdb', '#f59d00', '#d9534f', '#6f42c1', '#5bc0de']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        renderLegend('adminai-chart-legend-customers', labels, ['#007bdb', '#f59d00', '#d9534f', '#6f42c1', '#5bc0de']);
    }

    function renderNotifications(response) {
        var chartData = (response.charts && response.charts.severity_distribution) ? response.charts.severity_distribution : {};
        var labels = Array.isArray(chartData.labels) ? chartData.labels : [];
        var values = Array.isArray(chartData.values) ? chartData.values : [];
        var colors = ['#d9534f', '#f59d00', '#007bdb', '#5bc0de', '#6f42c1'];
        var canvas = document.getElementById('adminai-chart-notifications');
        if (!canvas) {
            return;
        }

        destroyChart('notifications');
        activeCharts.notifications = new window.Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        renderLegend('adminai-chart-legend-notifications', labels, colors);
    }

    return {
        render: function (section, response) {
            var normalizedSection = section === 'overview' ? 'sales' : section;
            showChartCard(normalizedSection);
            destroyAllCharts();

            if (hasChartJs()) {
                if (normalizedSection === 'sales') {
                    renderSales(response || {});
                    return;
                }

                if (normalizedSection === 'products') {
                    renderProducts(response || {});
                    return;
                }

                if (normalizedSection === 'customers') {
                    renderCustomers(response || {});
                    return;
                }

                if (normalizedSection === 'notifications') {
                    renderNotifications(response || {});
                }
                return;
            }

            console.warn('AdminAI Dashboard: Chart.js is not available. Using fallback canvas rendering.');
            if (normalizedSection === 'sales') {
                var revenueFallback = getSeriesData((((response || {}).charts || {}).revenue_trend || {}), 'Revenue');
                var orderFallback = getSeriesData((((response || {}).charts || {}).order_trend || {}), 'Orders');
                drawFallbackLine(
                    'adminai-chart-sales',
                    revenueFallback.values || []
                );
                drawFallbackLine(
                    'adminai-chart-orders',
                    orderFallback.values || []
                );
                renderSummaryCards(
                    'adminai-chart-meta-sales',
                    revenueFallback.labels || [],
                    revenueFallback.values || [],
                    true,
                    'Revenue'
                );
                renderSummaryCards(
                    'adminai-chart-meta-orders',
                    orderFallback.labels || [],
                    orderFallback.values || [],
                    false,
                    'Orders'
                );
                return;
            }
            if (normalizedSection === 'products') {
                var skuFallback = getSeriesData(((((response || {}).widgets || {}).sku_tracker || {}).chart || {}), 'Revenue');
                drawFallbackBars(
                    'adminai-chart-sku-performance',
                    skuFallback.values || []
                );
                renderSummaryCards(
                    'adminai-chart-meta-sku',
                    skuFallback.labels || [],
                    skuFallback.values || [],
                    true,
                    'Revenue'
                );
                return;
            }
            if (normalizedSection === 'customers') {
                drawFallbackPie(
                    'adminai-chart-customers',
                    (((response || {}).charts || {}).risk_distribution || {}).values || []
                );
                return;
            }
            if (normalizedSection === 'notifications') {
                drawFallbackPie(
                    'adminai-chart-notifications',
                    (((response || {}).charts || {}).severity_distribution || {}).values || []
                );
            }
        },
        clear: function () {
            destroyAllCharts();
        }
    };
});