/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'uiRegistry',
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid'
], function (registry, dynamicRowsGrid) {
    'use strict';

    return dynamicRowsGrid.extend({
        /**
         * Init header elements
         */
        initHeader: function () {
            var labels = [],
                data;
            let status = registry.get(this.provider).data.transfer_summary.general_information.status;
            if (!this.labels().length) {
                _.each(this.childTemplate.children, function (cell) {
                    data = this.createHeaderTemplate(cell.config);
                    cell.config.labelVisible = false;
                    _.extend(data, {
                        defaultLabelVisible: data.visible(),
                        label: cell.config.label,
                        name: cell.name,
                        required: !!cell.config.validation,
                        columnsHeaderClasses: cell.config.columnsHeaderClasses,
                        sortOrder: cell.config.sortOrder
                    });
                    if (!parseInt(status) && data.name === "qty_requested") {
                        data.label = "Qty To Request";
                    }
                    labels.push(data);
                }, this);
                this.labels(_.sortBy(labels, 'sortOrder'));
            }
        },

        /**
         * Initialize children
         *
         * @returns {Object} Chainable.
         */
        initChildren: function () {
            if (this.recordData().length > 0) {
                this.parsePagesData(this.recordData());
            }
            this.getChildItems().forEach(function (data, index) {
                this.processingAddChild(data, this.startIndex + index, data[this.identificationDRProperty]);
            }, this);

            return this;
        },

        /**
         * @inheritDoc
         */
        getChildItems: function (data, page) {
            if (typeof this.pageSize === 'string') {
                this.pageSize = Number(this.pageSize);
            }
            return this._super(data, page);
        },

        /**
         * @inheritDoc
         */
        setInitialProperty: function () {
            // We do not need init (used for default init)
            return this;
        },

        /**
         * @inheritDoc
         */
        getNewData: function (data) {
            var changes = [],
                tmpObj = {};

            if (data.length !== this.relatedData.length) {
                // Update algorithm for check changes data
                _.each(this.relatedData, function (obj) {
                    tmpObj[obj[this.identificationDRProperty]] = true;
                }, this);

                _.each(data, function (obj) {
                    if (!tmpObj[obj[this.identificationDRProperty]]) {
                        changes.push(obj);
                    }
                }, this);
            }

            return changes;
        },

        /**
         * @inheritDoc
         */
        _checkGridData: function (data) {
            // Init grid data
            var cacheLength = this.cacheGridData.length;
            if (0 === cacheLength) {
                return data;
            }

            // Remove or regenerate item
            var curData = data.length;
            if (curData <= cacheLength) {
                return [];
            }

            // Add item(s)
            var changes = [], obj = {};

            this.cacheGridData.each(function (record) {
              obj[record[this.map[this.identificationDRProperty]]] = true;
            }, this);

            data.each(function (record, index) {
                if (!obj[record[this.map[this.identificationDRProperty]]]) {
                    changes.push(data[index]);
                }
            }, this);

            return changes;
        }
    });
});
