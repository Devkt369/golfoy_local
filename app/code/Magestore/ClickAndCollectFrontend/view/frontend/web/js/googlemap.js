/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_ClickAndCollectFrontend
 * @copyright   Copyright (c) Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

var newmap;

define([
    'jquery',
    'Magestore_ClickAndCollectFrontend/js/map/map-loader',
    'mage/template',
    'graphql-query',
    'Magestore_ClickAndCollectFrontend/js/pagination',
    'Magestore_ClickAndCollectFrontend/js/liststore',
    'Magestore_ClickAndCollectFrontend/js/searchbox',
    'Magestore_ClickAndCollectFrontend/js/direction',
    'jquery/ui',
    'mage/translate'
], function($, Maploader, mageTemplate, graphqlQuery) {
    'use strict';

    $.widget('magestore.GoogleMap', {
        options: {
            baseUrl: '',
            defaultStoreIcon: '',
            mediaUrlImage: ''
        },
        _create: function() {
            var self= this, options  = this.options;
            this._initOption();

            $.extend(this, {
                $liststoreBox: $(options.liststoreContainer),
                $paginationWrapper: $(options.paginationWrapper),
                $searchBox: $(options.searchBox),
                $directionBox: $('.option-direction'),
                stores: null,
                searchedStores: [],
                searchKey: '',
                displayStores: []
            });

            Maploader.done($.proxy(this._initMap, this)).fail(function() {
                console.error("ERROR: Google maps library failed to load");
            });
        },

        _initOption: function () {
            this._defaultOption('latitude',0);
            this._defaultOption('longitude',0);
            this._defaultOption('zoom_level',4);
            this._defaultOption('minZoom',1);
            this._defaultOption('maxZoom',20);
        },

        _defaultOption:  function(name, defaultValue) {
            if(this.options[name] === '' || this.options[name] === null || typeof this.options[name] === 'undefined') {
                this.options[name] = defaultValue;
            }
        },

        _initMap: function() {
            var self= this, options  = this.options,
                centerPosition = new google.maps.LatLng(options.latitude, options.longitude);

            /**
             * map
             * @type {google.maps.Map}
             */
            this.map = new google.maps.Map(this.element[0], {
                zoom: parseFloat(options.zoom_level),
                center: centerPosition,
                minZoom: options.minZoom,
                maxZoom: options.maxZoom
            });


            /**
             * marker cluster
             * @type {*|k}
             */
            this.markerClusterer = new MarkerClusterer(this.map, [], {
                styles: mcOptions,
                gridSize: 10,
                maxZoom: 15
            } );

            this.storePopupTmpl = mageTemplate($(options.storePopupTemplate).html());

            /**
             * infor windopw
             * @type {google.maps.InfoWindow}
             */
            this.infowindow = new google.maps.InfoWindow({
                //maxWidth: 250,
                //disableAutoPan: true,
                maxWidth: 450,
                minWidth: 350,
            });

            this.circleMarker = new google.maps.Marker({
                icon: options.circleCenterIcon
            });

            /**
             * circle
             * @type {google.maps.Circle}
             */
            this.circle = new google.maps.Circle({
                center: null,
                map: null,
                radius: 0,
                fillColor: '#cd003a',
                fillOpacity: 0.1,
                strokeColor: "#000000",
                strokeOpacity: 0.3,
                strokeWeight: 1
            });

            this.$searchBox.searchbox({
                defaultRadius: options.defaultRadius,
                distanceUnit: options.distanceUnit
            });

            /**
             * Directions Service
             * @type {google.maps.DirectionsService}
             */
            this.dirService = new google.maps.DirectionsService();

            /**
             * Directions Renderer
             * @type {google.maps.DirectionsRenderer}
             */
            this.dirDisplay = new google.maps.DirectionsRenderer({
                draggable: true,
                map: this.map
            });

            /**
             * Street view
             * @type {google.maps.StreetViewService}
             */
            this.streetViews = new google.maps.StreetViewService();

            /**
             * Street View Panorama
             * @type {google.maps.StreetViewPanorama}
             */
            this.panorama = new google.maps.StreetViewPanorama(this.element[0], {
                enableCloseButton: true,
                visible: false
            });

            this.$searchBox.searchbox('initInputSearchAddess');
            /**
             * Event listener
             */

            this.$searchBox.on('geocoded', function (event, data) {
                self.drawCycle(data.location, self.$searchBox.searchbox('getRadius'));
                var params = $.extend(self.getParams(), {
                    radius: self.$searchBox.searchbox('getRadius'),
                    latitude: data.location.lat(),
                    longitude: data.location.lng()
                });
                self.loadStore(params);
            });

            this.$searchBox.on('reset-map', function (event, data) {
                self.loadStore(self.getParams());
                self.circle.setMap(null);
            });
            var country = window.googleSpecialCountry ? window.googleSpecialCountry : '';
            country = country.split(",");
            var autocomplete = new google.maps.places.Autocomplete(
                this.$directionBox.find('.origin.originA')[0],
                {
                    types: ['(cities)'],
                    componentRestrictions: {country: country}
                }
            );
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                self.$directionBox.direction('triggerEventDirection');
            });

            this.$directionBox.on('getDirection', function (event, data) {
                self.getDirection(data.start, data.end, data.traveMode, self.$directionBox.direction('getDirectionPanel'));
            });

            this.$liststoreBox.on('clickStore', function (event, data) {
                var index = data.index, store = self.displayStores[index];
                if(store) {
                    google.maps.event.trigger(store.marker, 'click');
                }
            });

            this.$liststoreBox.on('streetview', function (event, data) {
                var index = data.index, store = self.displayStores[index];
                if (store) {
                    self.streetView(store.marker);
                }
            });

            this.$paginationWrapper.on('changePage', function (event, data) {
                self.$directionBox.hide();
                var params = $.extend(self.getParams(), {
                    curPage: data.newPage
                });
                self.loadStore(params);
            });

            let locationBoxView = $('.location-box-view');
            locationBoxView.show();
            this.map.controls[google.maps.ControlPosition.LEFT_TOP].push(locationBoxView[0]);

            locationBoxView.click(this.currentPosition.bind(this));
            newmap = this.map;//Silver and Richard
            this.loadStore(self.getParams());

            if($(window).height() < 600){
                $('.map-box-container').css('height', '480px');
            }
            $(window).resize(function () {
                if($(window).height() < 600){
                    $('.map-box-container').css('height', '480px');
                }else{
                    $('.map-box-container').css('height', '606px');
                }
            })
        },

        getParams: function () {
            var params = {
                radius: this.$searchBox.searchbox('getRadius')
            };
            if(this.circle.getMap()) {
                params.latitude = this.circle.getCenter().lat();
                params.longitude = this.circle.getCenter().lng();
            }
            return params;
        },

        getDirection: function(start, end, travelMode, panelElement) {
            var self= this, options  = this.options;
            this.dirDisplay.setMap(this.map);
            this.dirDisplay.setPanel(panelElement);
            this.dirService.route({
                origin: start,
                destination: end,
                travelMode: google.maps.TravelMode[travelMode],
                unitSystem: (options.distanceUnit === 'Km') ? google.maps.UnitSystem.METRIC : google.maps.UnitSystem.IMPERIAL
            }, function(response, status) {
                if (status === google.maps.DirectionsStatus.OK) {
                    this.dirDisplay.setDirections(response);
                } else {
                    self.dirDisplay.setMap(null);
                    self.dirDisplay.setPanel(null);
                    alert($.mage.__('At least one of the origin, destination, or waypoints could not be geocoded.'));
                }
            }.bind(this));
        },

        drawCycle: function(center, radius) {
            this.removeCycle();
            this.circleMarker.setPosition(center);
            this.circleMarker.setMap(this.map);
            this.circle.setMap(this.map);
            this.circle.setRadius(radius);
            this.circle.bindTo('center', this.circleMarker, 'position');
            this.map.setCenter(center);
            this.map.setZoom(Math.round(15 - Math.log(radius / 1000) / Math.LN2));
        },

        removeCycle: function() {
            if (this.circle.getMap()) {
                this.circleMarker.setMap(null);
                this.circle.setMap(null);
            }
        },

        /**
         * Request get all stores
         * @returns {Promise<*>}
         */
        getAllStores: async function () {
            let result = await graphqlQuery(
                `query msGetAllLocationsAtFrontend($stockId: Int = 0) {
                    locations(
                        stockId: $stockId,
                        pageSize: 300
                    ) {
                        items {
                            location_id
                            name
                            description
                            telephone
                            email
                            street
                            city
                            region
                            region_id
                            country_id
                            postcode
                            country
                            stock_id
                            store_id
                            is_allow_pickup
                            latitude
                            longitude
                            schedule_id
                            schedule {
                                schedule_id
                                schedule_name 
                                monday_status
                                tuesday_status
                                wednesday_status
                                thursday_status
                                friday_status
                                saturday_status
                                sunday_status
                                monday_open
                                tuesday_open
                                wednesday_open
                                thursday_open
                                friday_open
                                saturday_open
                                sunday_open
                                monday_close
                                tuesday_close
                                wednesday_close
                                thursday_close
                                friday_close
                                saturday_close
                                sunday_close
                            }
                        }
                        total_count
                    }
                }`,
                {
                    stockId: this.options.stockId
                });
            return result.data.locations;
        },

        /**
         * Process search result
         * @param result
         */
        searchResultProcess: function (result) {
            var self= this, options  = this.options;
            if (result) {
                self.$paginationWrapper.pagination();
                self.$paginationWrapper.pagination(
                    'updatePagination',
                    {
                        curPage: result.current_page,
                        totalPage: result.total_page
                    }
                );
                self.$paginationWrapper.pagination('addChangePageEvent');

                self.displayStores = result.items;
                self.markerClusterer.clearMarkers();
                self.$directionBox.insertAfter('.boxes-content');
                self.$liststoreBox.html('');
                var bounds = new google.maps.LatLngBounds();
                $.each(self.displayStores, function (index, store) {
                    store.index = index;
                    self.$liststoreBox.liststore();
                    self.$liststoreBox.liststore('addStore', store);

                    var optionMarker = {
                        map: null,
                        position: new google.maps.LatLng(store.latitude, store.longitude),
                    }

                    if(store.marker_icon) {
                        optionMarker.icon = options.mediaUrlImage + store.marker_icon;
                    }

                    store.marker = new google.maps.Marker(optionMarker);
                    self.$liststoreBox.liststore();
                    google.maps.event.addListener(store.marker, 'click', function () {
                        self.map.panTo(store.marker.getPosition());
                        var $store = $(self.storePopupTmpl({data: store}).trim());
                        self.$liststoreBox.liststore('addEventDirectionBox',$store);
                        self.infowindow.setContent($store[0]);
                        self.infowindow.setPosition(store.marker.getPosition());
                        self.infowindow.open(self.map, store.marker);
                        // self.map.setZoom(parseFloat(store.zoom_level));

                        if(self.panorama.getVisible()) {
                            self.streetView(store.marker);
                        }
                    });

                    self.markerClusterer.addMarker(store.marker);
                    bounds.extend(optionMarker.position);
                });
                if (!self.circle.getMap()) {
                    self.map.fitBounds(bounds);
                }
                window.markerClusterer = self.markerClusterer;
                if(jQuery('.list-store-container li.store-item').length)
                    self.$liststoreBox.liststore('updateListStoresEvent');
                $('.number-store').html(result.total_count +' '+ $.mage.__('Stores'));
            }
        },

        /**
         * Search store
         * @param params
         * @returns {Promise<{total_count: number, total_page: number, items: T[], current_page: (*|number)}>}
         */
        searchStore: async function (params) {
            // If stores list is null, get all stores from server
            if (!this.stores) {
                try {
                    let result = await this.getAllStores();
                    this.stores = result.items;
                } catch (e) {
                    console.log(e);
                    this.stores = [];
                }
            }
            let searchKey = '' + params.radius + params.latitude + params.longitude;
            // If change search params, search stores in loaded store list by distance
            if (searchKey !== this.searchKey) {
                this.searchedStores = this.stores;
                if (params.radius && params.latitude && params.longitude) {
                    let currentLatLng = new google.maps.LatLng(params.latitude, params.longitude);
                    this.searchedStores = this.stores.filter(store => {
                        if (store.latitude && store.longitude) {
                            let storeLatLng = new google.maps.LatLng(store.latitude, store.longitude);
                            let distance = google.maps.geometry.spherical.computeDistanceBetween(
                                storeLatLng,
                                currentLatLng
                            );
                            if (distance <= params.radius) {
                                store.distance = distance;
                            }
                            return distance <= params.radius;
                        }
                        return false;
                    });
                    this.searchedStores.sort((a, b) => a.distance - b.distance);
                }
                this.searchKey = searchKey;
            }

            // Get page
            let totalCount = this.searchedStores.length;
            let pageSize = params.pageSize ? params.pageSize : 20;
            let totalPage = Math.ceil(totalCount / pageSize);
            let curPage = params.curPage ? params.curPage : 1;
            if (curPage > totalPage) {
                curPage = totalPage;
            }
            let items = this.searchedStores.slice((curPage - 1) * pageSize, curPage * pageSize);

            return {
                items: items,
                total_count: totalCount,
                current_page: curPage,
                total_page: totalPage
            };
        },

        /**
         * Load and display store
         *
         * @param params
         * @returns {Promise<void>}
         */
        loadStore: async function (params) {
            let options  = this.options;
            $(options.loader).show();
            try {
                let result = await this.searchStore(params);
                this.searchResultProcess(result);
                $(options.loader).hide();
            } catch (e) {
                console.log(e);
                $(options.loader).hide();
            }
        },

        streetView: function(marker) {
            this.streetViews.getPanorama({
                location: marker.getPosition(),
                radius: 50
            }, this.processSVData.bind(this));
        },
        processSVData: function processSVData(data, status) {
            this.panorama.setVisible(false);
            if (status === google.maps.StreetViewStatus.OK) {
                this.panorama.setPano(data.location.pano);
                this.panorama.setPov({
                    heading: 270,
                    pitch: 0
                });
                this.panorama.setVisible(true);
            } else {
                window.alert($.mage.__('Street Not Found !'));
            }
        },

        getMap: function () {
            return this.map;
        },

        currentPosition: function() {
            var self= this, options  = this.options;
            var infoPopup = new google.maps.InfoWindow({
                content: "",
                maxWidth: 293
            });

            var geocoder = new google.maps.Geocoder();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    infoPopup.setPosition(pos);
                    geocoder.geocode({
                        latLng: latlng
                    }, function(results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            infoPopup.setContent(results[0]['formatted_address']);
                            $('.input-search-distance').val(results[0]['formatted_address']);
                            self.$searchBox.searchbox('searchDistance');
                        }
                    });
                    infoPopup.setMap(self.map);
                    self.map.setZoom(13);
                    self.map.setCenter(pos);
                }, function() {
                    infoPopup.setPosition(self.map.getCenter());
                    infoPopup.setContent($.mage.__('Error: The Geolocation service failed.'));
                });
            }
        }
    });

    return $.magestore.GoogleMap;
});
