define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        var $search = $('#search');
        var $autocomplete = $('#search_autocomplete');

        if (!$search.length || !$autocomplete.length) {
            return;
        }

        var ajaxRequest = null;
        var debounceTimer = null;

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
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
            } catch (e) {
                return '₹' + amount.toLocaleString('en-IN');
            }
        }

        function renderProducts(products) {
            if (!products || !products.length) {
                $autocomplete.html(
                    '<div class="ajax-search-empty">No products found.</div>'
                ).show();

                return;
            }

            var html = '<div class="products wrapper grid products-grid">' +
                '<ol class="products list items product-items">';

            $.each(products, function (index, product) {
                var productUrl = escapeHtml(product.url);
                html += '<li class="item product product-item">' +
                    '<div class="product-item-info">' +
                    '<a class="product photo product-item-photo" href="' + productUrl + '">';

                if (product.image) {
                    html += '<span class="product-image-container">' +
                        '<span class="product-image-wrapper">' +
                        '<img class="product-image-photo" src="' + escapeHtml(product.image) +
                        '" alt="' + escapeHtml(product.name) + '">' +
                        '</span></span>';
                }

                html += '</a><div class="product details product-item-details">' +
                    '<strong class="product name product-item-name">' +
                    '<a class="product-item-link" href="' + productUrl + '">' +
                    escapeHtml(product.name) + '</a></strong>';

                if (product.brand) {
                    html += '<div class="product-item-brand">' + escapeHtml(product.brand) + '</div>';
                }

                html += '<div class="price-box price-final_price">';
                if (product.special_price !== null && Number(product.special_price) > 0) {
                    html += '<span class="old-price"><span class="price-container">' +
                        '<span class="price-label">MRP</span>' +
                        '<span class="price">' + escapeHtml(formatPrice(product.price)) + '</span>' +
                        '</span></span><span class="special-price"><span class="price-container">' +
                        '<span class="price-label">Special Price</span>' +
                        '<span class="price">' + escapeHtml(formatPrice(product.special_price)) + '</span>' +
                        '</span></span>';
                } else {
                    html += '<span class="price-container"><span class="price">' +
                        escapeHtml(formatPrice(product.price)) +
                        '</span></span>';
                }
                html += '</div></div></div></li>';
            });

            html += '</ol></div>';

            $autocomplete.html(html).show();
        }

        function searchProducts(query) {
            if (ajaxRequest) {
                ajaxRequest.abort();
            }

            ajaxRequest = $.ajax({
                url: '/ajaxsearch/search/autocomplete',
                type: 'GET',
                dataType: 'json',
                data: {
                    q: query
                },

                beforeSend: function () {
                    $autocomplete
                        .html('<div class="ajax-search-loading">Loading...</div>')
                        .show();
                },

                success: function (response) {
                    if (response && response.products) {
                        renderProducts(response.products);
                    } else {
                        renderProducts([]);
                    }
                },

                error: function (xhr, status) {
                    if (status !== 'abort') {
                        $autocomplete.html(
                            '<div class="ajax-search-error">' +
                            'Unable to load products.' +
                            '</div>'
                        ).show();
                    }
                },

                complete: function () {
                    ajaxRequest = null;
                }
            });
        }

        $search.on('input', function () {
            var query = $.trim($(this).val());

            clearTimeout(debounceTimer);

            if (query.length < 2) {
                $autocomplete.hide().empty();
                return;
            }

            debounceTimer = setTimeout(function () {
                searchProducts(query);
            }, 300);
        });

        $(document).on('click', function (event) {
            if (
                !$(event.target).closest('.block-search').length
            ) {
                $autocomplete.hide();
            }
        });

        $search.on('focus', function () {
            if ($.trim($(this).val()).length >= 2) {
                $autocomplete.show();
            }
        });
    };
});