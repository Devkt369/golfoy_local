define([
    'jquery',
    'mage/url',
    'domReady!'
], function ($, urlBuilder) {
    'use strict';

    var debounceTimer;
    var DEBOUNCE_DELAY = 300;
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
        } catch (e) {
            return '₹' + amount.toLocaleString('en-IN');
        }
    }

    function initAutocomplete(element) {
        var $input = $(element);
        if (!$input.length) {
            $input = $('#ajaxsearch-input, #search').first();
        }
        if (!$input.length) {
            return;
        }

        var limit = parseInt($input.data('limit'), 10);
        var $results = getResultsContainer($input);

        $input.off('.ajaxsearch').on('input.ajaxsearch', function () {
            var query = $input.val().trim();
            if (query.length === 0) {
                $results.empty().addClass('hidden');
                return;
            }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                fetchResults(query, limit, $results);
            }, DEBOUNCE_DELAY);
        });

        $input.on('keydown.ajaxsearch', function (e) {
            if ($results.hasClass('hidden')) {
                return;
            }
            var $items = $results.find('.ajaxsearch-item');
            var $focused = $items.filter('.focused');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var $next = $focused.length ? $focused.next('.ajaxsearch-item') : $items.first();
                $items.removeClass('focused');
                $next.addClass('focused');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var $prev = $focused.length ? $focused.prev('.ajaxsearch-item') : $items.last();
                $items.removeClass('focused');
                $prev.addClass('focused');
            } else if (e.key === 'Enter' && $focused.length) {
                e.preventDefault();
                window.location.href = $focused.data('url');
            }
        });

        $results.off('.ajaxsearch').on('click.ajaxsearch', '.ajaxsearch-item', function () {
            window.location.href = $(this).data('url');
        });
    }

    function getResultsContainer($input) {
        var $results = $('#ajaxsearch-results');
        if ($results.length) {
            return $results;
        }
        $results = $input.siblings('#search_autocomplete, .search-autocomplete').first();
        if (!$results.length) {
            $results = $('<div id="search_autocomplete" class="search-autocomplete ajaxsearch-results"/>');
            $input.after($results);
        }
        $results.addClass('ajaxsearch-results');
        return $results;
    }

    function fetchResults(query, limit, $results) {
        $results.html('<div class="ajaxsearch-loading">Loading...</div>').removeClass('hidden');
        var endpoint = urlBuilder.build('ajaxsearch/search/autocomplete');
        var requestData = { q: query };
        if (isFinite(limit) && limit > 0) {
            requestData.limit = limit;
        }
        $.ajax({
            url: endpoint,
            data: requestData,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                renderResults(data, $results);
            },
            error: function () {
                $results.html('<div class="ajaxsearch-error">Error loading suggestions</div>');
            }
        });
    }

    function renderResults(data, $results) {
        $results.empty().removeClass('hidden');
        if (!data || !Array.isArray(data.products) || data.products.length === 0) {
            $results.append('<div class="ajaxsearch-empty">No results</div>');
            return;
        }
        var $grid = $('<div class="products wrapper grid products-grid">' +
            '<ol class="products list items product-items"/>');

        data.products.forEach(function (product) {
            var $card = $('<div class="product-item-info"/>');
            var $imageLink = $('<a class="product photo product-item-photo"/>')
                .attr('href', product.url || '#');
            if (product.image) {
                $imageLink.append(
                    $('<span class="product-image-container"><span class="product-image-wrapper"/></span>')
                        .find('.product-image-wrapper')
                        .append($('<img class="product-image-photo"/>')
                            .attr('src', product.image)
                            .attr('alt', product.name || ''))
                        .end()
                );
            }
            var $details = $('<div class="product details product-item-details"/>')
                .append(
                    $('<strong class="product name product-item-name"/>')
                        .append($('<a class="product-item-link"/>')
                            .attr('href', product.url || '#')
                            .text(product.name || ''))
                );
            if (product.brand) {
                $details.append($('<div class="product-item-brand"/>').text(product.brand));
            }

            var $priceBox = $('<div class="price-box price-final_price"/>');
            if (product.special_price !== null && Number(product.special_price) > 0) {
                $priceBox
                    .append($('<span class="old-price"><span class="price-container"><span class="price-label">MRP</span></span></span>')
                        .find('.price-container')
                        .append($('<span class="price"/>').text(formatPrice(product.price)))
                        .end())
                    .append($('<span class="special-price"><span class="price-container"><span class="price-label">Special Price</span></span></span>')
                        .find('.price-container')
                        .append($('<span class="price"/>').text(formatPrice(product.special_price)))
                        .end());
            } else {
                $priceBox.append(
                    $('<span class="price-container"><span class="price"/></span>')
                        .find('.price')
                        .text(formatPrice(product.price))
                        .end()
                );
            }
            $card.append($imageLink, $details.append($priceBox));
            $grid.find('.product-items').append(
                $('<li class="item product product-item"/>').append($card)
            );
        });
        $results.append($grid);
    }

    return function (config, element) {
        initAutocomplete(element || '#search');
    };
});
