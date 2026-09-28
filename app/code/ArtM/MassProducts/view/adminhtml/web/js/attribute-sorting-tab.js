define(['jquery'], function ($) {
    'use strict';

    return function initAttributeSortingTab() {
        var $root = $('#massproducts-attribute-sorting');
        if (!$root.length) {
            return;
        }

        var itemsUrl = String($root.data('items-url') || '');
        var saveUrl = String($root.data('save-url') || '');

        var $title = $('#massproducts-as-current-title');
        var $items = $('#massproducts-as-items');
        var $saveBtn = $('#massproducts-as-save');

        var currentSetId = 0;
        var dragItem = null;

        function esc(text) {
            return $('<div/>').text(String(text || '')).html();
        }

        function wireDnD() {
            $items.find('.massproducts-as-item').attr('draggable', 'true')
                .off('dragstart').on('dragstart', function (e) {
                    dragItem = this;
                    $(this).addClass('is-dragging');
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                })
                .off('dragend').on('dragend', function () {
                    $(this).removeClass('is-dragging');
                    dragItem = null;
                })
                .off('dragover').on('dragover', function (e) {
                    e.preventDefault();
                    if (!dragItem || dragItem === this) {
                        return;
                    }
                    var rect = this.getBoundingClientRect();
                    var before = (e.originalEvent.clientY - rect.top) < (rect.height / 2);
                    if (before) {
                        this.parentNode.insertBefore(dragItem, this);
                    } else {
                        this.parentNode.insertBefore(dragItem, this.nextSibling);
                    }
                });
        }

        function renderItems(list) {
            var systemItems = [];
            var userItems = [];
            $.each(list || [], function (_, item) {
                if (item && item.is_user_defined) {
                    userItems.push(item);
                    return;
                }
                systemItems.push(item);
            });

            var html = '';
            html += '<div class="massproducts-as-group-title" style="font-weight:600;margin:6px 0;">' + esc('Non User Defined') + '</div>';
            html += '<div class="massproducts-as-system-items">';
            $.each(systemItems, function (_, item) {
                html += '<div class="massproducts-as-item" data-code="' + esc(item.attribute_code || '') + '" data-user-defined="0">' + esc(item.label || item.attribute_code || '') + '</div>';
            });
            html += '</div>';

            html += '<div class="massproducts-as-group-title" style="font-weight:600;margin:12px 0 6px;">' + esc('User Defined') + '</div>';
            html += '<div class="massproducts-as-user-items">';
            $.each(userItems, function (_, item) {
                html += '<div class="massproducts-as-item" data-code="' + esc(item.attribute_code || '') + '" data-user-defined="1">' + esc(item.label || item.attribute_code || '') + '</div>';
            });
            html += '</div>';

            $items.html(html || '<div class="massproducts-as-empty">No attributes available for this set.</div>');
            wireDnD();
        }

        function loadItems() {
            currentSetId = 0;
            $title.text('Global Attribute Sorting');
            $items.html('<div class="massproducts-as-empty">Loading...</div>');

            $.ajax({
                url: itemsUrl,
                type: 'GET',
                dataType: 'json',
                data: { attribute_set_id: 0 }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    $items.html('<div class="massproducts-as-empty">Unable to load attributes.</div>');
                    return;
                }
                renderItems(resp.items || []);
            }).fail(function () {
                $items.html('<div class="massproducts-as-empty">Unable to load attributes.</div>');
            });
        }

        function saveOrder() {
            var codes = [];
            $items.find('.massproducts-as-system-items .massproducts-as-item').each(function () {
                var code = String($(this).data('code') || '');
                if (code) {
                    codes.push(code);
                }
            });
            $items.find('.massproducts-as-user-items .massproducts-as-item').each(function () {
                var code = String($(this).data('code') || '');
                if (code) {
                    codes.push(code);
                }
            });

            $saveBtn.prop('disabled', true).text('Saving...');
            $.ajax({
                url: saveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY,
                    attribute_set_id: 0,
                    attribute_codes: codes
                }
            }).done(function (resp) {
                if (resp && resp.success) {
                    alert(resp.message || 'Saved.');
                    return;
                }
                alert((resp && resp.message) ? resp.message : 'Unable to save order.');
            }).fail(function () {
                alert('Unable to save order.');
            }).always(function () {
                $saveBtn.prop('disabled', false).text('Save');
            });
        }

        $saveBtn.off('click.massproductsSort').on('click.massproductsSort', function () {
            saveOrder();
        });

        loadItems();
    };
});
