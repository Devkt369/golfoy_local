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

    return function (reloadListUrl, reloadDetailUrl) {
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
                        text: $t('Top slowest POS')
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

        initGraph();

        window.loadActionDetail = function (actionCode) {
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
                    action_code: actionCode,
                    from: from,
                    to: to
                }
            }).done(function (response) {
                $('#action-info').html(response.detail_block);
                barChartData.datasets = [
                    {
                        label: $t('POS Response Time'),
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

        window.selectAction = function (actionCode) {
            $('#rank-list .bar-item').removeClass('selected');
            $('#' + actionCode).addClass('selected');
            loadActionDetail(actionCode);
        };

        window.loadFirstPos = function () {
            if ($('#rank-list .bar-item').length) {
                selectAction($('#rank-list .bar-item')[0].getAttribute('id'));
                $('#graph-pos').show();
            } else {
                $('#graph-pos').hide();
                loadActionDetail('');
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
                    $('#rank-list').html(response);
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
                    $('#rank-list').html(response);
                    loadFirstPos();
                });
            }
        });
    };
});
