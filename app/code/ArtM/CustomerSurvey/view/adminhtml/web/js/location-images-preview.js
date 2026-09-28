define([
    'jquery',
    'ko',
    'uiComponent',
    'uiRegistry'
], function ($, ko, Component, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'ArtM_CustomerSurvey/location-images-preview',
            locationIds: [],
            locations: []
        },

        initObservable: function () {
            this._super();
            this.selectedLocations = ko.observableArray([]);

            return this;
        },

        setDebug: function () {
            // Debug window removed by request.
        },

        initialize: function () {
            this._super();

            this.bindDomLocationField();
            this.setDebug('Starting location preview');
            this.bindLocationField();

            return this;
        },

        bindLocationField: function () {
            var self = this;

            registry.async('customersurvey_survey_form.customersurvey_survey_form.survey_locations.location_ids')(
                function (field) {
                    self.locationField = field;
                    self.locations = self.getLocationsFromField(field);
                    self.setDebug('Registry field found', {
                        fieldName: field.name || null,
                        initialValue: typeof field.value === 'function' ? field.value() : field.value,
                        locationsCount: self.locations.length,
                        firstLocation: self.locations.length ? self.locations[0] : null
                    });
                    self.refreshSelectedLocations();

                    if (field.value && typeof field.value.subscribe === 'function') {
                        field.value.subscribe(function (newValue) {
                            self.locations = self.getLocationsFromField(field);
                            self.setDebug('Registry value.subscribe fired', {
                                newValue: newValue,
                                domValue: self.getDomSelectedLocationIds(),
                                locationsCount: self.locations.length
                            });
                            self.updateSelectedLocations(newValue);
                        });
                    }

                    if (typeof field.on === 'function') {
                        field.on('value', function (newValue) {
                            self.locations = self.getLocationsFromField(field);
                            self.setDebug('Registry field.on fired', {
                                newValue: newValue,
                                domValue: self.getDomSelectedLocationIds(),
                                locationsCount: self.locations.length
                            });
                            self.updateSelectedLocations(newValue);
                        });
                    }

                    self.refreshSelectedLocations();
                }
            );
        },

        bindDomLocationField: function () {
            var self = this;

            $(document).on('change', 'select[name="location_ids"], select[name="location_ids[]"], [data-index="location_ids"] select', function () {
                self.setDebug('DOM change fired', {
                    domValue: $(this).val() || [],
                    elementName: $(this).attr('name') || null,
                    locationsCount: self.locations.length
                });
                self.updateSelectedLocations($(this).val() || []);
            });
        },

        refreshSelectedLocations: function () {
            var domValue;

            if (!this.locationField) {
                domValue = this.getDomSelectedLocationIds();
                this.setDebug('Refreshing from DOM only', {
                    domValue: domValue,
                    locationsCount: this.locations.length
                });
                this.updateSelectedLocations(domValue);
                return;
            }

            this.locations = this.getLocationsFromField(this.locationField);
            this.setDebug('Refreshing from registry field', {
                fieldValue: typeof this.locationField.value === 'function'
                    ? this.locationField.value()
                    : this.locationField.value,
                domValue: this.getDomSelectedLocationIds(),
                locationsCount: this.locations.length
            });

            this.updateSelectedLocations(
                typeof this.locationField.value === 'function'
                    ? this.locationField.value()
                    : this.locationField.value
            );

            domValue = this.getDomSelectedLocationIds();
            if (domValue.length) {
                this.updateSelectedLocations(domValue);
            }
        },

        getDomSelectedLocationIds: function () {
            var field = $('select[name="location_ids"], select[name="location_ids[]"], [data-index="location_ids"] select').first();

            if (!field.length) {
                return [];
            }

            return field.val() || [];
        },

        getLocationsFromField: function (field) {
            var rawOptions = [];
            var locations = [];

            if (!field) {
                return locations;
            }

            if (typeof field.options === 'function') {
                rawOptions = field.options() || [];
            } else if (Array.isArray(field.options)) {
                rawOptions = field.options;
            }

            $.each(rawOptions, function (idx, option) {
                if (!option || option.value === undefined || option.value === null || option.value === '') {
                    return;
                }

                locations.push({
                    location_id: String(option.location_id !== undefined ? option.location_id : option.value),
                    name: option.name || option.label || '',
                    page_type: option.page_type || '',
                    screenshot: option.screenshot || '',
                    screenshot_url: option.screenshot_url || ''
                });
            });

            return locations;
        },

        normalizeLocationIds: function (locationIds) {
            var normalized = [];

            if (typeof locationIds === 'string') {
                locationIds = locationIds ? locationIds.split(',') : [];
            }

            if (!Array.isArray(locationIds)) {
                if (locationIds && typeof locationIds === 'object' && locationIds.value !== undefined) {
                    locationIds = [locationIds.value];
                } else {
                    return normalized;
                }
            }

            $.each(locationIds, function (idx, item) {
                var value = item;

                if (item && typeof item === 'object') {
                    if (item.value !== undefined) {
                        value = item.value;
                    } else if (item.location_id !== undefined) {
                        value = item.location_id;
                    }
                }

                if (Array.isArray(value)) {
                    $.each(value, function (innerIdx, innerValue) {
                        normalized.push(String(innerValue));
                    });
                    return;
                }

                if (value !== undefined && value !== null && value !== '') {
                    normalized.push(String(value));
                }
            });

            return normalized;
        },

        updateSelectedLocations: function (locationIds) {
            var self = this;
            var normalizedIds = this.normalizeLocationIds(locationIds);

            if (!normalizedIds.length) {
                self.setDebug('No normalized location ids found', {
                    rawValue: locationIds,
                    normalizedIds: normalizedIds,
                    domValue: self.getDomSelectedLocationIds(),
                    locationsCount: self.locations.length
                });
                self.selectedLocations([]);
                return;
            }

            var selected = [];
            $.each(normalizedIds, function (idx, locId) {
                $.each(self.locations, function (locationIdx, location) {
                    if (String(location.location_id) === String(locId)) {
                        selected.push(location);
                        return false;
                    }
                    return true;
                });
            });

            var uniqueSelected = [];
            var seen = {};

            $.each(selected, function (idx, location) {
                var key = String(location.location_id);
                if (!seen[key]) {
                    seen[key] = true;
                    uniqueSelected.push(location);
                }
            });

            self.setDebug('Selected locations updated', {
                rawValue: locationIds,
                normalizedIds: normalizedIds,
                matchedCount: uniqueSelected.length,
                matchedIds: $.map(uniqueSelected, function (location) {
                    return location.location_id;
                }),
                domValue: self.getDomSelectedLocationIds(),
                sampleMatched: uniqueSelected.length ? uniqueSelected[0] : null
            });

            self.selectedLocations(uniqueSelected);
        }
    });
});
