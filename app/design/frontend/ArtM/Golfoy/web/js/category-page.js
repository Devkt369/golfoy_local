require(['jquery'], function ($) {
    $(document).ready(function () {

        $("#gfmyBtn").click(function () {
            $("#gfmore").toggle("slow", function () {
                if ($("#gfmore").is(":visible")) {
                    $("#gfdots").hide();
                    $("#gfmyBtn").html("Read less");
                } else {
                    $("#gfdots").show();
                    $("#gfmyBtn").html("Read more");
                }
            });
        });

        // category page mobile filter handler start
        function isMobile() {
            return window.innerWidth < 768;
        }

        function setupMobileFilters() {
            if (!isMobile()) return;

            // 1. On click of filter heading, add 'active' to #ajaxlist-reload-layered-navigation
            $(document).on('click', '.filter_heading.close', function () {
                $('body').addClass('ajaxlist-reload-layered-navigation-active');
            });

            // 3. On click of either filter heading popup or apply bar, remove 'active' from #ajaxlist-reload-layered-navigation
            $(document).on('click', '.filter_heading_popup, .apply-bar-btn', function () {
                $('body').removeClass('ajaxlist-reload-layered-navigation-active');
            });
        }

        $(function () {
            setupMobileFilters();
            $(window).on('resize', function () {
                setupMobileFilters();
            });
        });
        // Category Page Mobile Filter Handler end

        // Custom Sort Overlay for Mobile
        function setupCustomSortOverlay() {
            var $overlay = $('.custom-sort-overlay');
            var $optionsContainer = $overlay.find('.custom-sort-options');
            var $closeBtn = $overlay.find('.custom-sort-close');

            // Populate custom sort options from #sorter
            function populateSortOptions() {
                $optionsContainer.empty();
                // Add heading and close button
                var $heading = $('<div class="custom-sort-option custom-sort-heading"></div>')
                    .append('<span>Sort</span>')
                    .append('<span class="custom-sort-close" style="font-size:22px; cursor:pointer; margin-left:10px;">&times;</span>');
                $optionsContainer.append($heading);
                var $sorter = $('#sorter');
                console.log('Populating sort options. Sorter found:', $sorter.length);
                $sorter.find('option').each(function () {
                    var $opt = $(this);
                    var value = $opt.val();
                    var text = $opt.text();
                    var selected = $opt.is(':selected');
                    var $item = $('<div class="custom-sort-option"></div>')
                        .text(text)
                        .attr('data-value', value);
                    if (selected) $item.addClass('selected');
                    $optionsContainer.append($item);
                });
            }

            // Show/hide overlay on .sorting_overlay click
            $(document).off('click.customSort').on('click.customSort', '.sorting_overlay', function () {
                if ($overlay.hasClass('active')) {
                    $overlay.removeClass('active');
                } else {
                    populateSortOptions();
                    $overlay.addClass('active');
                }
            });

            // Hide overlay on close button click (delegate to container for dynamically added close button)
            $optionsContainer.off('click.customSortClose').on('click.customSortClose', '.custom-sort-close', function (e) {
                e.stopPropagation();
                $overlay.removeClass('active');
                // Restore the selected value in #sorter if it was changed visually but not confirmed
                var $sorter = $('#sorter');
                var selectedValue = $optionsContainer.find('.custom-sort-option.selected').attr('data-value');
                if (selectedValue !== undefined) {
                    $sorter.val(selectedValue);
                }
            });

            // Handle custom option click
            $optionsContainer.off('click.customSort').on('click.customSort', '.custom-sort-option', function () {
                var value = $(this).attr('data-value');
                var $sorter = $('#sorter');
                $sorter.val(value).trigger('change');
                $overlay.removeClass('active');
            });

            // Hide overlay when clicking anywhere else on the page
            $(document).off('click.customSortOutside').on('click.customSortOutside', function (e) {
                // Check if overlay is active and click is outside overlay and sorting trigger
                if ($overlay.hasClass('active')) {
                    var $target = $(e.target);
                    // If click is not on the overlay, options container, sorting overlay, or any of their children
                    if (!$target.closest('.custom-sort-overlay').length &&
                        !$target.closest('.sorting_overlay').length) {
                        $overlay.removeClass('active');
                    }
                }
            });
        }

        // Initial setup
        $(function () {
            setupCustomSortOverlay();
        });

        // Re-initialize after AJAX content is loaded
        $(document).on('ajaxComplete ajaxSuccess', function () {
            setupCustomSortOverlay();
        });
        // category page mobile filter handler end

        //clicking clear all filter link should trigger click on the actual clear all filter link in layered navigation block
        $(document).on('click', 'a.action_click', function(e) {
            var targetId = $(this).attr('target');
            if (targetId) {
                var $target = $('#' + targetId);
                if ($target.length) {
                    e.preventDefault(); // Prevent anchor redirect
                    $target.trigger('click');
                }
            }
        });


    });
});