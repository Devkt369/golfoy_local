define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    return function init($root, config) {
        var $sidebar = $root.find('[data-role="sidebar"]');
        var $content = $root.find('[data-role="content"]');
        var currentId = parseInt(config.initialCategoryId, 10) || 0;
        var sidebar = Array.isArray(config.mainCategories) ? config.mainCategories : [];
        var categoryCache = {};
        var paneCache = {};
        var pendingRequest = {};
        
        function isItemEnabled(item) {
            var value = item && item.is_enabled != null ? item.is_enabled : (item && item.config ? item.config.is_enabled : 1);
            var enabled = parseInt(value, 10);
            return isNaN(enabled) || enabled === 1;
        }

        sidebar = $.grep(sidebar, function (item) {
            return isItemEnabled(item);
        });

        function renderSidebar(activeId) {
            sidebar.sort(function (a, b) {
                return b.sort_order - a.sort_order;
            });
            var html = [];

            
            html.push('<div class="artm-sidebar-item-brand"><a href="/brands" class="react-nav">');
            html.push('<img loading="lazy" src="https://golfoy.com/media/category_showcase/brands-v121.gif" alt=""/>');
            html.push('<div style="min-width:0;"><div style="font-weight:700;line-height:1.2;">Brands</div></div>');
            html.push('</a></div>');


            $.each(sidebar, function (_, item) {
                var isActive = parseInt(item.category_id, 10) === parseInt(activeId, 10);
                html.push('<div class="artm-sidebar-item' + (isActive ? ' is-active' : '') + '" data-id="' + parseInt(item.category_id, 10) + '">');
                html.push(item.image ? '<img loading="lazy" src="' + escapeHtml(item.image) + '" alt=""/>' : '<div style="width:44px;height:44px;border-radius:12px;background:#e2e8f0;"></div>');
                html.push('<div style="min-width:0;"><div style="font-weight:700;line-height:1.2;">' + escapeHtml(item.label || '') + '</div><div style="font-size:12px;color:#64748b;">' + escapeHtml($t('Category')) + '</div></div>');
                html.push('</div>');
            });
            $sidebar.html(html.join(''));
        }

        function setActiveSidebar(activeId) {
            $sidebar.find('.artm-sidebar-item').removeClass('is-active');
            $sidebar.find('.artm-sidebar-item[data-id="' + parseInt(activeId, 10) + '"]').addClass('is-active');
        }

        function getSidebarCategoryUrl(categoryId) {
            var categoryIdInt = parseInt(categoryId, 10) || 0;
            var match = null;

            $.each(sidebar, function (_, item) {
                if (parseInt(item.category_id, 10) === categoryIdInt) {
                    match = item;
                    return false;
                }
            });

            return match && match.url ? String(match.url) : '';
        }

        function bannerHtml(data, activeCategoryId) {
            // Always prefer currently selected main category URL.
            var targetUrl = getSidebarCategoryUrl(activeCategoryId) || data.banner_link || data.url || '';
            var arrowSvg = '<span class="artm-banner-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="19" x2="19" y2="5"/><polyline points="9 5 19 5 19 15"/></svg></span>';
            var linkEl = targetUrl ? '<a class="artm-banner-link" href="' + escapeHtml(targetUrl) + '" aria-label="' + escapeHtml(data.label || '') + '"></a>' : '';
            var overlay = '<div class="artm-banner-overlay"><div><div style="font-size:28px;font-weight:800;">' + escapeHtml(data.label || '') + '</div></div></div>';

            if (!data.banner) {
                return '<div class="artm-banner">' + linkEl + overlay + arrowSvg + '</div>';
            }
            return '<div class="artm-banner"><img loading="lazy" src="' + escapeHtml(data.banner) + '" alt=""/>' + overlay + linkEl + arrowSvg + '</div>';
        }

        function renderPayloadHtml(data) {
            var groups = Array.isArray(data.groups) ? data.groups : [];
            var html = [];
            html.push('<div class="artm-fade">');
            html.push(bannerHtml(data, currentId));
            if (!groups.length) {
                html.push('<div class="artm-loading">' + escapeHtml($t('No categories to display.')) + '</div>');
            }
            $.each(groups, function (_, group) {
                var groupEnabled = group && group.is_enabled != null ? group.is_enabled : 1;
                if (parseInt(groupEnabled, 10) !== 1) {
                    return true;
                }

                var items = Array.isArray(group.items) ? $.grep(group.items, function (item) {
                    return isItemEnabled(item);
                }) : [];
                html.push('<div class="artm-group">');
                html.push('<h3>' + escapeHtml(group.heading || '') + '</h3>');
                html.push('<div class="artm-card-grid">');
                $.each(items, function (_, item) {
                    html.push('<a class="artm-card" href="' + escapeHtml(item.url || '#') + '"');
                    html.push(' data-id="' + parseInt(item.category_id || 0, 10) + '">');
                    html.push('<div class="artm-card-media">' + (item.image ? '<img loading="lazy" src="' + escapeHtml(item.image) + '" alt=""/>' : '') + '</div>');
                    html.push('<div class="artm-card-label">' + escapeHtml(item.label || '') + '</div>');
                    html.push('</a>');
                });
                html.push('</div></div>');
            });
            html.push('</div>');
            return html.join('');
        }

        function showCategoryPane(categoryId, html) {
            if (!$content.data('artm-content-initialized')) {
                $content.empty().data('artm-content-initialized', true);
            }

            if (!paneCache[categoryId]) {
                paneCache[categoryId] = $('<div class="artm-content-pane" data-pane-id="' + categoryId + '"></div>');
                $content.append(paneCache[categoryId]);
            }

            if (typeof html === 'string') {
                paneCache[categoryId].html(html);
            }

            $.each(paneCache, function (id, $pane) {
                if (parseInt(id, 10) === parseInt(categoryId, 10)) {
                    $pane.show();
                } else {
                    $pane.hide();
                }
            });
        }

        function loadCategory(categoryId) {
            currentId = parseInt(categoryId, 10) || 0;
            setActiveSidebar(currentId);

            if (!currentId) {
                return;
            }

            if (paneCache[currentId]) {
                showCategoryPane(currentId);
                return;
            }

            if (categoryCache[currentId]) {
                showCategoryPane(currentId, renderPayloadHtml(categoryCache[currentId]));
                return;
            }

            showCategoryPane(currentId, '<div class="artm-loading artm-skeleton">Loading...</div>');

            if (pendingRequest[currentId]) {
                return;
            }

            var requestedId = currentId;
            pendingRequest[requestedId] = true;
            $.ajax({
                url: config.loadUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    category_id: requestedId,
                    store_id: config.storeId || 0
                }
            }).done(function (resp) {
                if (!resp || !resp.success) {
                    showCategoryPane(requestedId, '<div class="artm-loading">' + escapeHtml(resp && resp.message ? resp.message : $t('Unable to load categories.')) + '</div>');
                    return;
                }
                categoryCache[requestedId] = resp;
                showCategoryPane(requestedId, renderPayloadHtml(resp));
            }).fail(function () {
                showCategoryPane(requestedId, '<div class="artm-loading">' + escapeHtml($t('Failed to load categories.')) + '<div style="margin-top:10px;"><button type="button" class="action-primary artm-retry-load" data-id="' + requestedId + '">' + escapeHtml($t('Retry')) + '</button></div></div>');
            }).always(function () {
                pendingRequest[requestedId] = false;
            });
        }

        $sidebar.off('click.artmCategoryPage').on('click.artmCategoryPage', '.artm-sidebar-item', function (e) {
            e.preventDefault();
            loadCategory($(this).data('id'));
        });

        $content.off('click.artmCategoryPageRetry').on('click.artmCategoryPageRetry', '.artm-retry-load', function () {
            var retryId = parseInt($(this).data('id'), 10) || currentId;
            delete categoryCache[retryId];
            if (paneCache[retryId]) {
                paneCache[retryId].remove();
                delete paneCache[retryId];
            }
            loadCategory(retryId);
        });

        if (currentId <= 0 && sidebar.length > 0) {
            currentId = parseInt(sidebar[0].category_id, 10) || 0;
        }

        renderSidebar(currentId);

        if (currentId > 0) {
            loadCategory(currentId);
        }
    };
});
