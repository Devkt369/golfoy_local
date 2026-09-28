define([
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    /**
     * Drives the Settings page's "Column Grouping" configurator: a pool of
     * available Dashboard columns on the right, and admin-created groups on the
     * left. Columns move between the pool and any group (or between groups) via
     * drag and drop (jQuery UI sortable, connected lists). State is kept purely
     * client-side and mirrored into a hidden input on every change, so the normal
     * form POST/redirect flow (matching the rest of this page) persists it.
     *
     * @param {Object} config - {allColumns: {key: label}, savedGroups: {groups: [{name, columns}], customColumns: {key: label}}}
     * @param {Element} element - the <form> this component is bound to
     */
    return function (config, element) {
        var $form = $(element);

        // allColumns starts as the built-in Dashboard columns, then gets any
        // manually-added ones (from a previous save) merged in, so both render
        // identically as far as buildColumnItem() is concerned.
        var allColumns = $.extend({}, config.allColumns || {}, (config.savedGroups && config.savedGroups.customColumns) || {});
        var customColumns = $.extend({}, (config.savedGroups && config.savedGroups.customColumns) || {});
        var state = {
            groups: (config.savedGroups && config.savedGroups.groups) || []
        };
        var nextGroupSuffix = 0;
        var nextCustomSuffix = 0;

        var $groupsContainer = $form.find('#artm_groups_container');
        var $available = $form.find('#artm_available_columns');
        var $hiddenInput = $form.find('#artm_column_groups_input');
        var $addGroupBtn = $form.find('#artm_add_group_btn');
        var $newGroupName = $form.find('#artm_new_group_name');
        var $addColumnBtn = $form.find('#artm_add_column_btn');
        var $newColumnName = $form.find('#artm_new_column_name');

        // Saved groups don't have a client-side id yet - assign one so drag/rename/
        // delete can target the right group regardless of list position.
        state.groups.forEach(function (group) {
            group.id = 'grp_' + (nextGroupSuffix++) + '_' + Date.now();
        });

        function assignedColumnKeys() {
            var used = {};
            state.groups.forEach(function (group) {
                (group.columns || []).forEach(function (key) {
                    used[key] = true;
                });
            });
            return used;
        }

        function buildColumnItem(key) {
            return $('<li/>', {
                'class': 'artm-column-item',
                'data-column': key,
                text: allColumns[key] || key
            });
        }

        function initSortable() {
            $form.find('.artm-column-list').sortable({
                connectWith: '.artm-column-list',
                placeholder: 'artm-column-item-placeholder',
                forcePlaceholderSize: true,
                update: function () {
                    syncStateFromDom();
                    render();
                }
            });
        }

        function syncStateFromDom() {
            state.groups.forEach(function (group) {
                var $list = $groupsContainer.find('[data-group-id="' + group.id + '"] .artm-column-list');
                group.columns = $list.find('.artm-column-item').map(function () {
                    return $(this).attr('data-column');
                }).get();
            });
            syncHiddenInput();
        }

        function syncHiddenInput() {
            $hiddenInput.val(JSON.stringify({groups: state.groups, customColumns: customColumns}));
        }

        function render() {
            $groupsContainer.empty();
            $available.empty();

            state.groups.forEach(function (group) {
                var $group = $('<div/>', {'class': 'artm-column-group', 'data-group-id': group.id});
                var $header = $('<div/>', {'class': 'artm-column-group-header'});
                var $nameInput = $('<input/>', {
                    type: 'text',
                    'class': 'artm-column-group-name',
                    value: group.name
                });
                var $deleteBtn = $('<button/>', {
                    type: 'button',
                    'class': 'artm-column-group-delete',
                    title: 'Delete group',
                    html: '&times;'
                });

                $nameInput.on('input', function () {
                    group.name = $(this).val();
                    syncHiddenInput();
                });

                $deleteBtn.on('click', function () {
                    // Columns aren't touched here - they simply stop being referenced
                    // by any group once it's removed, so render() puts them back in
                    // the Available pool automatically.
                    state.groups = state.groups.filter(function (candidate) {
                        return candidate.id !== group.id;
                    });
                    render();
                });

                $header.append($nameInput).append($deleteBtn);

                var $list = $('<ul/>', {'class': 'artm-column-list', 'data-role': 'group-columns'});
                (group.columns || []).forEach(function (key) {
                    $list.append(buildColumnItem(key));
                });

                $group.append($header).append($list);
                $groupsContainer.append($group);
            });

            var used = assignedColumnKeys();
            Object.keys(allColumns).forEach(function (key) {
                if (!used[key]) {
                    $available.append(buildColumnItem(key));
                }
            });

            initSortable();
            syncHiddenInput();
        }

        $addGroupBtn.on('click', function () {
            var name = $.trim($newGroupName.val());
            if (!name) {
                return;
            }

            state.groups.push({
                id: 'grp_' + (nextGroupSuffix++) + '_' + Date.now(),
                name: name,
                columns: []
            });
            $newGroupName.val('');
            render();
        });

        $newGroupName.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $addGroupBtn.trigger('click');
            }
        });

        $addColumnBtn.on('click', function () {
            var label = $.trim($newColumnName.val());
            if (!label) {
                return;
            }

            var key = 'custom_' + (nextCustomSuffix++) + '_' + Date.now();
            allColumns[key] = label;
            customColumns[key] = label;
            $newColumnName.val('');
            render();
        });

        $newColumnName.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $addColumnBtn.trigger('click');
            }
        });

        $form.on('submit', syncStateFromDom);

        render();
    };
});
