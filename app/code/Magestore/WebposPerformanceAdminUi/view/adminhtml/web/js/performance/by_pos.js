/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magestore_WebposPerformanceAdminUi/js/chart/Chart.min',
    'Magestore_WebposPerformanceAdminUi/js/chart/utils'
], function ($, $t) {
    'use strict';

    return function (reloadListUrl, reloadDetailUrl, loadMoreUrl) {
        var color = Chart.helpers.color;
        var barChartData = {
            labels: [],
            datasets: []
        };

        window.initGraph = function () {
            var ctx = document.getElementById('canvas').getContext('2d');
            window.myBar = new Chart(ctx, {
                type: 'bar',
                data: barChartData,
                options: {
                    responsive: true,
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: $t('POS Slowest By Action')
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                suggestedMin: 0
                            }
                        }]
                    }
                }
            });
        }

        window.loadMore = function (element) {
            if($(element).scrollTop() + $(element).innerHeight() >= $(element)[0].scrollHeight) {
                var nextPage = parseInt($('#currentPage').val(), 10) + 1;
                if (nextPage <= parseInt($('#maxPage').val())) {
                    loadPage(parseInt($('#currentPage').val(), 10) + 1);
                }
            }
        }

        window.loadPage = function (pageNumber) {
            var rangeValue = $('#date-range-select').val();
            var from = '';
            var to = '';
            if (rangeValue === 'custom_range') {
                from = $('#from').val();
                to = $('#to').val();
            }
            $.ajax({
                url: loadMoreUrl,
                type: 'POST',
                dataType: 'text',
                showLoader: true,
                data: {
                    pageNumber: pageNumber,
                    date_range: rangeValue,
                    from: from,
                    to: to
                }
            }).done(function (response) {
                $('.bar-list').append(response);
                $('#currentPage').val(pageNumber);
            });
        }

        initGraph();

        window.loadPosDetail = function (posId) {
            var dateRangeSelect = $('#date-range-select').val();
            var from = $('#from').val();
            var to = $('#to').val();
            $.ajax({
                url: reloadDetailUrl,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    date_range:  dateRangeSelect,
                    pos_id: posId,
                    from: from,
                    to: to
                }
            }).done(function (response) {
                $('#action-info').html(response.detail_block);
                barChartData.datasets = [
                    {
                        label: $t('Action Response Time By POS'),
                        backgroundColor: color(window.chartColors.red).alpha(0.5).rgbString(),
                        borderColor: window.chartColors.red,
                        borderWidth: 1,
                        data: response.data_set_graph.data
                    }
                ];
                barChartData.labels = response.data_set_graph.labels;
                window.myBar.update();
            });
        };

        window.selectPos = function (posId) {
            $('#rank-list .bar-item').removeClass('selected');
            $('#' + posId).addClass('selected');
            loadPosDetail(posId);
        };

        window.loadFirstPos = function () {
            if ($('#rank-list .bar-item').length) {
                selectPos($('#rank-list .bar-item')[0].getAttribute('id'));
                $('#graph-action').show();
            } else {
                $('#graph-action').hide();
                loadPosDetail('');
            }
        };

        window.onload = function() {
            loadFirstPos();
        }

        $('#date-range-select').change(function () {
            var rangeValue = $('#date-range-select').val();
            if (rangeValue !== 'custom_range') {
                $.ajax({
                    url: reloadListUrl,
                    type: 'POST',
                    dataType: 'text',
                    showLoader: true,
                    data: {
                        date_range:  rangeValue,
                    }
                }).done(function (response) {
                    $('.bar-list').html(response);
                    loadFirstPos();
                });
            }
        });

        $('.date-range').change(function () {
            var rangeValue = $('#date-range-select').val();
            var from = $('#from').val();
            var to = $('#to').val();
            if (rangeValue === 'custom_range' && from && to) {
                $.ajax({
                    url: reloadListUrl,
                    type: 'POST',
                    dataType: 'text',
                    showLoader: true,
                    data: {
                        date_range: rangeValue,
                        from: from,
                        to: to
                    }
                }).done(function (response) {
                    $('.bar-list').html(response);
                    loadFirstPos();
                });
            }
        });
    };
});
