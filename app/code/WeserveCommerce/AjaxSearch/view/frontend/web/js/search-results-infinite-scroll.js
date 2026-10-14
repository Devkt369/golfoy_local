require([
    'jquery'
], function ($) {
    'use strict';

    function initializeInfiniteScroll() {
        var $page = $('.ajax-search-page[data-loading-mode="infinite_scroll"]');
        if (!$page.length) {
            return;
        }

        var $loadMore = $page.find('.ajax-search-load-more');
        var $loading = $loadMore.find('.ajax-search-loading-message');
        var $error = $loadMore.find('.ajax-search-error-message');
        var currentPage = parseInt($page.attr('data-page'), 10) || 1;
        var totalPages = parseInt($page.attr('data-total-pages'), 10) || 1;
        var isLoading = false;
        var scrollCheckScheduled = false;
        var lastLoadScrollY = null;

        function escapeHtml(value) {
            return $('<div/>').text(value === null || value === undefined ? '' : String(value)).html();
        }

        function formatPrice(value) {
            var amount = Number(value);
            if (!isFinite(amount)) {
                return '';
            }

            try {
                return new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    maximumFractionDigits: 2
                }).format(amount);
            } catch (error) {
                return '₹' + amount.toLocaleString('en-IN');
            }
        }

        function renderProduct(product) {
            var url = escapeHtml(product.url || '#');
            var name = escapeHtml(product.name || '');
            var html = '<li class="item product product-item"><div class="product-item-info">' +
                '<a class="product photo product-item-photo" href="' + url + '">';

            if (product.image) {
                html += '<span class="product-image-container"><span class="product-image-wrapper">' +
                    '<img class="product-image-photo" src="' + escapeHtml(product.image) +
                    '" alt="' + name + '"/></span></span>';
            }

            html += '</a><div class="product details product-item-details">' +
                '<strong class="product name product-item-name"><a class="product-item-link" href="' +
                url + '">' + name + '</a></strong>';

            if (product.brand) {
                html += '<div class="product-item-brand">' + escapeHtml(product.brand) + '</div>';
            }

            html += '<div class="price-box price-final_price">';
            if (product.special_price !== null && Number(product.special_price) > 0) {
                html += '<span class="old-price"><span class="price-container">' +
                    '<span class="price-label">MRP</span><span class="price">' +
                    escapeHtml(formatPrice(product.price)) + '</span></span></span>' +
                    '<span class="special-price"><span class="price-container">' +
                    '<span class="price-label">Special Price</span><span class="price">' +
                    escapeHtml(formatPrice(product.special_price)) + '</span></span></span>';
            } else {
                html += '<span class="price-container"><span class="price">' +
                    escapeHtml(formatPrice(product.price)) + '</span></span>';
            }

            return html + '</div></div></div></li>';
        }

        function finishLoading() {
            isLoading = false;
            $loading.prop('hidden', true);
        }

        function loadNextPage() {
            if (isLoading || currentPage >= totalPages) {
                return;
            }

            isLoading = true;
            lastLoadScrollY = window.scrollY;
            $error.prop('hidden', true).text('');
            $loading.prop('hidden', false);

            $.ajax({
                url: $page.attr('data-endpoint'),
                type: 'GET',
                dataType: 'json',
                cache: false,
                data: {
                    q: $page.attr('data-query'),
                    page: currentPage + 1,
                    limit: parseInt($page.attr('data-limit'), 10) || 20
                }
            }).done(function (response) {
                if (!response || response.error || !Array.isArray(response.products)) {
                    finishLoading();
                    $error.text('Unable to load more products. Please try again.').prop('hidden', false);
                    return;
                }

                var $items = $page.find('.product-items').first();
                $.each(response.products, function (index, product) {
                    $items.append(renderProduct(product));
                });

                currentPage = parseInt(response.page, 10) || currentPage + 1;
                $page.attr('data-page', currentPage);
                finishLoading();

                if (!response.products.length || currentPage >= totalPages) {
                    $(window).off('scroll.ajaxSearchInfinite resize.ajaxSearchInfinite');
                    $loadMore.remove();
                }
            }).fail(function () {
                finishLoading();
                $error.text('Unable to load more products. Please try again.').prop('hidden', false);
            });
        }

        function checkScrollPosition() {
            if (scrollCheckScheduled || isLoading) {
                return;
            }

            scrollCheckScheduled = true;
            window.requestAnimationFrame(function () {
                scrollCheckScheduled = false;
                if (lastLoadScrollY !== null && Math.abs(window.scrollY - lastLoadScrollY) < 80) {
                    return;
                }

                var sentinel = $loadMore.find('.ajax-search-scroll-sentinel')[0];
                if (!sentinel) {
                    return;
                }

                var bounds = sentinel.getBoundingClientRect();
                if (bounds.top <= window.innerHeight + 300 && bounds.bottom >= -300) {
                    loadNextPage();
                }
            });
        }

        $(window).on('scroll.ajaxSearchInfinite resize.ajaxSearchInfinite', checkScrollPosition);
        checkScrollPosition();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeInfiniteScroll, {once: true});
    } else {
        initializeInfiniteScroll();
    }
});
