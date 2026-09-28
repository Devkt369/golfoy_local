/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
define([
    'jquery',
    'mage/template',
    'jquery/ui',
], function($, mageTemplate) {
    $.widget('magestore.pagination', {
        options: {
            paginationTemplate: '#pagination-template',
            range: 3
        },
        _create: function () {
            var self = this, options = this.options;
            self.addChangePageEvent();
            self.paginationTmpl = mageTemplate($(options.paginationTemplate).html());
        },

        /**
         * Update Pagination
         * @param data
         */
        updatePagination: function (data) {
            $(this.element).html(this.toPaginationHtml(data));
        },

        /**
         * To Pagination Html
         * @param data
         * @returns {*|jQuery|HTMLElement}
         */
        toPaginationHtml: function (data) {
            let options = this.options;
            let middle = Math.ceil(options.range / 2);
            let minPage, maxPage;
            if (data.totalPage < options.range) {
                minPage = 1;
                maxPage = data.totalPage;
            } else {
                minPage = data.curPage - middle + 1;
                maxPage = data.curPage + middle - 1;
                if (minPage < 1) {
                    minPage = 1;
                    maxPage = options.range;
                } else if (maxPage > data.totalPage) {
                    minPage = data.totalPage - options.range + 1;
                    maxPage = data.totalPage;
                }
            }
            data.minPage = minPage;
            data.maxPage = maxPage;
            return $(this.paginationTmpl({data: data}).trim());
        },

        addChangePageEvent: function () {
            var self = this, options = this.options;
            $(self.element).find('.page-item:not(.disabled)').click(function () {
                if($(this).data('first-page')) {
                    $(self.element).trigger('changePage', {newPage: $(this).data('first-page')});
                } else if($(this).data('last-page')) {
                    $(self.element).trigger('changePage', {newPage: $(this).data('last-page')});
                } else if(!$(this).hasClass('active')) {
                    $(self.element).trigger('changePage', {newPage: $(this).data('page-id')});
                }
            });
        },

        getCurPage: function () {
            return $(this).find('.page-item.active').data('page-id');
        },
    });

    return $.magestore.pagination;
});
