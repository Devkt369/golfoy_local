define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedProducts = config.selectedProducts,
            categoryProducts = $H(selectedProducts),
            categorySortingPoints = $H(config.sortingPoints || {}),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000;

        $('in_category_products').value = Object.toJSON(categoryProducts);
        $('in_category_sorting_points').value = Object.toJSON(categorySortingPoints);

        function registerCategoryProduct(grid, element, checked) {
            if (checked) {
                if (element.positionElement) {
                    element.positionElement.disabled = false;
                    categoryProducts.set(element.value, element.positionElement.value);
                }
                if (element.sortingPointsElement) {
                    element.sortingPointsElement.disabled = false;
                    categorySortingPoints.set(element.value, element.sortingPointsElement.value);
                }
            } else {
                if (element.positionElement) {
                    element.positionElement.disabled = true;
                }
                if (element.sortingPointsElement) {
                    element.sortingPointsElement.disabled = true;
                }
                categoryProducts.unset(element.value);
                categorySortingPoints.unset(element.value);
            }
            $('in_category_products').value = Object.toJSON(categoryProducts);
            $('in_category_sorting_points').value = Object.toJSON(categorySortingPoints);
            grid.reloadParams = {
                'selected_products[]': categoryProducts.keys()
            };
        }

        function categoryProductRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                eventElement = Event.element(event),
                isInputCheckbox = eventElement.tagName === 'INPUT' && eventElement.type === 'checkbox',
                isInputPosition = grid.targetElement &&
                    grid.targetElement.tagName === 'INPUT' &&
                    (grid.targetElement.name === 'position' || grid.targetElement.name === 'sorting_points'),
                checked = false,
                checkbox = null;

            if (eventElement.tagName === 'LABEL' &&
                trElement.querySelector('#' + eventElement.htmlFor) &&
                trElement.querySelector('#' + eventElement.htmlFor).type === 'checkbox'
            ) {
                event.stopPropagation();
                trElement.querySelector('#' + eventElement.htmlFor).trigger('click');
                return;
            }

            if (trElement && !isInputPosition) {
                checkbox = Element.getElementsBySelector(trElement, 'input');
                if (checkbox[0]) {
                    checked = isInputCheckbox ? checkbox[0].checked : !checkbox[0].checked;
                    gridJsObject.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }

        function positionChange(event) {
            var element = Event.element(event);
            if (element && element.checkboxElement && element.checkboxElement.checked) {
                categoryProducts.set(element.checkboxElement.value, element.value);
                $('in_category_products').value = Object.toJSON(categoryProducts);
            }
        }

        function sortingPointsChange(event) {
            var element = Event.element(event);
            if (element && element.checkboxElement && element.checkboxElement.checked) {
                categorySortingPoints.set(element.checkboxElement.value, element.value);
                $('in_category_sorting_points').value = Object.toJSON(categorySortingPoints);
            }
        }

        function categoryProductRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0],
                position = row.querySelector('input[name="position"]'),
                sortingPoints = row.querySelector('input[name="sorting_points"]');

            if (checkbox && position) {
                checkbox.positionElement = position;
                position.checkboxElement = checkbox;
                position.disabled = !checkbox.checked;
                position.tabIndex = tabIndex++;
                Event.observe(position, 'keyup', positionChange);
            }

            if (checkbox && sortingPoints) {
                checkbox.sortingPointsElement = sortingPoints;
                sortingPoints.checkboxElement = checkbox;
                sortingPoints.disabled = !checkbox.checked;
                sortingPoints.tabIndex = tabIndex++;
                Event.observe(sortingPoints, 'keyup', sortingPointsChange);
            }
        }

        gridJsObject.rowClickCallback = categoryProductRowClick;
        gridJsObject.initRowCallback = categoryProductRowInit;
        gridJsObject.checkboxCheckCallback = registerCategoryProduct;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                categoryProductRowInit(gridJsObject, row);
            });
        }
    };
});