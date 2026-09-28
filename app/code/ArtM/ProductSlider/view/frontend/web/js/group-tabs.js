define(['jquery', 'mage/apply/main'], function ($, mageApply) {
    'use strict';

    function request(url) {
        return fetch(url, { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            });
    }

    return function (config, element) {
        var root = element;
        var nav = root.querySelector('[data-role="group-tabs-nav"]');
        var panes = Array.prototype.slice.call(root.querySelectorAll('[data-role="group-tab-pane"]') || []);
        var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-role="group-tab-btn"]') || []);
        var groupIdentifier = root.getAttribute('data-group-identifier') || '';
        var groupId = parseInt(root.getAttribute('data-group-id') || '0', 10);
        var ajaxUrl = config.ajaxUrl || '';
        var cache = {};

        if (!root || !nav || panes.length === 0 || buttons.length === 0 || !ajaxUrl) {
            return;
        }

        function setActive(index) {
            buttons.forEach(function (btn) {
                var btnIndex = parseInt(btn.getAttribute('data-tab-index') || '-1', 10);
                var active = btnIndex === index;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panes.forEach(function (pane) {
                var paneIndex = parseInt(pane.getAttribute('data-tab-index') || '-1', 10);
                var active = paneIndex === index;
                pane.classList.toggle('is-active', active);
                pane.hidden = !active;
            });
        }

        function loadPane(index) {
            var pane = panes.find(function (p) {
                return parseInt(p.getAttribute('data-tab-index') || '-1', 10) === index;
            });
            if (!pane) {
                return Promise.resolve();
            }

            if (pane.getAttribute('data-loaded') === '1') {
                return Promise.resolve();
            }

            var loading = pane.querySelector('[data-role="group-tab-loading"]');
            var content = pane.querySelector('[data-role="group-tab-content"]');
            if (!content) {
                return Promise.resolve();
            }

            if (cache[index]) {
                content.innerHTML = cache[index];
                pane.setAttribute('data-loaded', '1');
                if (typeof mageApply === 'function') {
                    mageApply(content);
                }
                if ($ && $.fn && typeof $.fn.trigger === 'function') {
                    $(content).trigger('contentUpdated');
                }
                return Promise.resolve();
            }

            if (loading) {
                loading.hidden = false;
            }

            var query = '?tab_index=' + encodeURIComponent(String(index));
            if (groupIdentifier !== '') {
                query += '&identifier=' + encodeURIComponent(groupIdentifier);
            }
            if (groupId > 0) {
                query += '&group_id=' + encodeURIComponent(String(groupId));
            }

            return request(ajaxUrl + query)
                .then(function (json) {
                    if (!json || !json.success || !json.data) {
                        throw new Error('Invalid response');
                    }

                    var html = String(json.data.html || '');
                    cache[index] = html;
                    content.innerHTML = html;
                    pane.setAttribute('data-loaded', '1');

                    if (typeof mageApply === 'function') {
                        mageApply(content);
                    }
                    if ($ && $.fn && typeof $.fn.trigger === 'function') {
                        $(content).trigger('contentUpdated');
                    }
                })
                .catch(function () {
                    content.innerHTML = '<div class="artm-productslider-group__error">Unable to load this tab.</div>';
                })
                .finally(function () {
                    if (loading) {
                        loading.hidden = true;
                    }
                });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var index = parseInt(btn.getAttribute('data-tab-index') || '-1', 10);
                if (index < 0) {
                    return;
                }

                setActive(index);
                loadPane(index);
            });
        });

        var defaultTab = parseInt(root.getAttribute('data-default-tab') || '0', 10);
        setActive(defaultTab);
        loadPane(defaultTab);
    };
});
