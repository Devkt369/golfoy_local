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

    return function (data, actionCode) {
        var getDataSetForGraph = function (result, label, color, actionCode) {
            let dataSet = {
                label: label,
                fill: false,
                backgroundColor: window.chartColors[color],
                borderColor: window.chartColors[color]
            };

            var dataSetData = [];

            labels.map(
                (value) => {
                    var average = null;
                    if (typeof result[value][actionCode] !== 'undefined' && result[value][actionCode]) {
                        average = result[value][actionCode];
                    }
                    dataSetData.push(average);
                    return value;
                }
            );
            dataSet.data = dataSetData;
            return dataSet;
        }

        var labels = [
            '00:00', '00:30',
            '01:00', '01:30',
            '02:00', '02:30',
            '03:00', '03:30',
            '04:00', '04:30',
            '05:00', '05:30',
            '06:00', '06:30',
            '07:00', '07:30',
            '08:00', '08:30',
            '09:00', '09:30',
            '10:00', '10:30',
            '11:00', '12:30',
            '13:00', '13:30',
            '14:00', '14:30',
            '15:00', '15:30',
            '16:00', '16:30',
            '17:00', '17:30',
            '18:00', '18:30',
            '19:00', '19:30',
            '20:00', '20:30',
            '21:00', '21:30',
            '22:00', '22:30',
            '23:00', '23:30',
        ];

        var result = {};
        labels.map(
            (value) => {
                result[value] = {};
                return value;
            }
        );


        data.map(
            (value) => {
                var startDate = new Date(value.from);
                var minutes = String(startDate.getMinutes()).padStart(2, "0");
                var hours = String(startDate.getHours()).padStart(2, "0");
                result[hours + ':' + minutes][value.action_code] = value.average;
            }
        );


        var config = {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    getDataSetForGraph(result, actionCode, 'red', actionCode),
                ]
            },
            options: {
                responsive: true,
                title: {
                    display: true,
                    text: $t('POS Performance Detail By Hours In This Day')
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Hours'
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Seconds'
                        },
                        ticks: {
                            suggestedMin: 0
                        }
                    }]
                }
            }
        };

        var ctx = document.getElementById('canvas').getContext('2d');
        window.myLine = new Chart(ctx, config);
    };
});
