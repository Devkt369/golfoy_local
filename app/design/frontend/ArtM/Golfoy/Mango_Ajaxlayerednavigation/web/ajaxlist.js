// Override for Mango_Ajaxlayerednavigation web/ajaxlist.js. Add your custom JS here.
/**
 * Copyright © 2016 MangoExtensions. All rights reserved.
 * */
/*jshint browser:true jquery:true*/
define([
    'jquery',
    'jquery_ui_touch',
    'productListToolbarForm',
    'underscore',
    'matchMedia',
    'collapsible',
    'dropdownDialog',
    window._ajaxlayerednavigation_url_handler
], function ($, jQueryUiTouch, productListToolbarForm, _, mediaCheck, _collapsible, _dropdownDialog, urlHandler) {
    "use strict";
    
    $.widget('custom.ajaxLayeredNavigation', $.mage.productListToolbarForm, {
        currentValues: {
        },
        ajaxOptions: {
            reloadNavigation: false
        },
        _open_horizontal_dropdown : false,/*use for clickoutside event...*/
        // History = window.History,

        _init: function () {            
            this._super();
            this._checkMedia();
        },

        _create: function () {
            this._categoriesTree();
            this._createLayeredNavigation();
            this._bindEvents();
            window.loadedAjaxLayeredNavigation = true;
            /* set default values for pagination, mode, sortby, direction, items per page*/
            this.currentValues = {};
        
            this._initInfiniteScroll();
            
            /* initialize url handler ***/
            if (this.options.urlhandler === "hashChangeJs") {/* change hash only */
                this._initHashchangeJs();
            } else if (this.options.urlhandler === "historyJs") { /* use history.js. will try to change the url */
                this._initHistoryJs();
            }
            /*additional horizontal layered navigation scripts*/
            this._additionalHorizontalLayeredNavigationScripts();
        },

        _checkMedia: function () {
            mediaCheck({
                media: '(min-width: 768px)',
                entry: $.proxy(function () {
                    this._toggleDesktopMode();
                }, this),
                exit: $.proxy(function () {
                    this._toggleMobileMode();
                }, this)
            });
        },

        _bindEvents: function () {
            this._bind(this.options.modeControl, this.options.mode, this.options.modeDefault);
            this._bind(this.options.directionControl, this.options.direction, this.options.directionDefault);
            this._bind(this.options.orderControl, this.options.order, this.options.orderDefault);
            this._bind(this.options.limitControl, this.options.limit, this.options.limitDefault);
            this._bindDirectLink(this.options.paginationLinks, false);
            this._bindDirectLink(this.options.layeredNavigationLinks, true);
            this._bind(this.options.modeControl, this.options.mode, this.options.modeDefault);
            if (parseInt(this.options.horizontal_mode)===1 || parseInt(this.options.horizontal_only)===1 ) {
                this._bindDirectLink(this.options.horizontal_layered_navigation_element + " .filter-items-list a", true);
            }
            
        },
        _bind: function (element, paramName, defaultValue) {
            var _element = $(element);
            this.ajaxOptions.reloadNavigation = false;
            if (_element.is("select")) {
                $(document).on('change', element, {paramName: paramName, default: defaultValue}, $.proxy(this._processSelect, this));
            } else {
                $(document).on('click', element, {paramName: paramName, default: defaultValue}, $.proxy(this._processLink, this));
            }
        },
        /*may use someday...*/
        /*                _createLoaders: function(){
         var _loaderTemplate = '<div class="products-list-loader"><div></div></div>';
         jQuery(this.options.product_list_element + ", " + this.options.layered_navigation_element).loader({ 
         template: '<div class="products-list-loader"><div></div></div>'
         });
         //jQuery(this.options.layered_navigation_element).append("<div class=\"products-list-loader\"><div></div></div>");
         },*/

        _createLayeredNavigation: function () {

            if ($(this.options.layered_navigation_element).length === 0) {
                return false;
            }
            
            /* before rendering layered navigation, move elements and create horizontal layered navigation*/
            this._createHorizontalLayeredNavigation();

            $(this.options.layered_navigation_element + " #layered-filter-block").collapsible({
                openedState: "active",
                collapsible: true,
                active: false,
                collateral:
                        {openedState: "filter-active",
                            element: "body"}
            });
            if (parseInt(this.options.collapse) === 1) {/* expand collapse effect for filters */
                $(this.options.layered_navigation_element + " #narrow-by-list .filter-options-item.active").collapsible({
                    openedState: "active",
                    collapsible: true,
                    active: true,
                    //animate:true,
                    multipleCollapsible: false
                });
                $(this.options.layered_navigation_element + " #narrow-by-list .filter-options-item").not(".active").collapsible({
                    openedState: "active",
                    collapsible: true,
                    active: false,
                    //animate: true,
                    multipleCollapsible: false
                });
            }
            $(this.options.layered_navigation_element + " .filter-current").collapsible({
                openedState: "active",
                collapsible: true,
                active: false
            });
            this._startSlider();
            this._startNumericSliders();
            this._startAttributeSliders();
            this.expandCategoriesFilter();
            this._enableTooltips();
            jQuery(document).trigger("afterCreateLayeredNavigation", [this]);
        },
        _bindDirectLink: function (element, _loadNavigation) { 
            var _this = this;
            $(document).on('click', element, function (e) {
                e.preventDefault();
                /* switch class for checkboxes */
                //_this.loadAjaxProductsList($(this).attr("href"), _loadNavigation, false);
                _this.ajaxOptions.reloadNavigation = _loadNavigation;
                _this._changeUrlOnToolbar($(this).attr("href"), {});
                if (jQuery(this).hasClass("layered-navigation-filter-link")) {
                    jQuery(this).closest("li.item").toggleClass("active-filter-option");
                } else if (jQuery(this).hasClass("swatch-option-link-layered")) {
                    jQuery(this).toggleClass("active-filter-option");
                }
            });
        },
        
        /* horizontal layered navigation */
        /* execute each time catalog is loaded */
        _createHorizontalLayeredNavigation: function ( ) {
            /* if option is disabled, do not do anything... */
            if (parseInt(this.options.horizontal_mode)!==1 && parseInt(this.options.horizontal_only)!==1 ) {
                return;
            }
            
            var _this = this;
            
            /* first, create container div */
            /* use var for  "#ajaxlist-reload-product_list" */
            /* move elements when horizontal+left layered navigation blocks are enabled.. */
            if (parseInt(this.options.horizontal_mode)===1) {
                jQuery(_this.options.product_list_element).before(jQuery("#horizontal-layered-navigation-container"));
            }
            /* add z-index for dropdowns... */
            var _z_index = 10000;
            jQuery(_this.options.horizontal_layered_navigation_element + " div.filter-options-item ").each(function (i) {
                jQuery(this).css("z-index", _z_index - i);
            });
            /* get the selected / active items */
            /*var _active_links = jQuery(_this.options.horizontal_layered_navigation_element + " .filter-options-item.active li.active-filter-option:not(.no-items):not(.uncheck_all) > a").clone();
            if (jQuery(_active_links).length > 0) {
                jQuery("div#horizontal-layered-navigation-container").before("<ol id='horizontal-layered-navigation-status'></ol>");
                jQuery("ol#horizontal-layered-navigation-status").append(_active_links);
            }*/
            /* keep the html, when the pages are reloaded with ajax, replace the layered navigation... */
            //window._horizontal_layered_navigation_contents = jQuery("div#horizontal-layered-navigation-container").html();
            
            /* use collapsible widget */
            /*start collapsible mode*/
            //console.debug($(_this.options.horizontal_layered_navigation_element + " .filter-options-item"));
            $(_this.options.horizontal_layered_navigation_element + " .filter-options-item").collapsible({
                openedState: "active",
                collapsible: true,
                active: false
            });
            
            $(_this.options.horizontal_layered_navigation_element + " .filter-options-item").on("beforeOpen", function () {
                $(_this.options.horizontal_layered_navigation_element + " .filter-options-item.active").collapsible("forceDeactivate");
                _this._open_horizontal_dropdown = true;
            });
            
            /* use dropdownDialog widget... */
            /*$(this.options.horizontal_layered_navigation_element + " .horizontal-filter").each(function(i,el){
                var _id = jQuery(el).attr("id");
                $("#" + _id + " .filter-options-content").dropdownDialog({
                    appendTo: "#" + _id, 
                    triggerTarget: "#" + _id + " .filter-options-title", 
                    buttons: null, 
                    closeOnMouseLeave: false,
                    parentClass : "active"
                });
            });*/
            this._checkMedia();
        },
        
        _enableTooltips: function () {
            jQuery(this.options.layered_navigation_element + ", " + this.options.horizontal_layered_navigation_element).tooltip({
                items: "span.ajax-layered-navigation-tooltip",
                content: function () {
                  var _content = jQuery(this).data("tooltip-content");
                  return jQuery(_content).html();
                },
                tooltipClass : 'ajaxlist-tooltip'
            });
        },
        
        /* execute only once... */
        _additionalHorizontalLayeredNavigationScripts: function () {
            /* if option is disabled, do not do anything... */
            //return;
            if (parseInt(this.options.horizontal_mode)!==1 && parseInt(this.options.horizontal_only)!==1 ) {
                return;
            }
            var _this = this;
            
            $(document).on("click", _this.options.horizontal_layered_navigation_element + " .filter-options-content", function (e) {
                e.stopPropagation();/* do not close the dropdowns... */
            });
            /* close all dropdowns */
            $(document).on("click", function (e) {
                if (!_this._open_horizontal_dropdown) {
                    $(_this.options.horizontal_layered_navigation_element + " .filter-options-item.active").collapsible("forceDeactivate");
                } else {
                    _this._open_horizontal_dropdown = false;
                }
            });

        },
        
        _startSlider: function () {

            /* price slider not defined... */
            if (typeof window._priceslider_settings === 'undefined')
                return false;

            var _this = this;
            if (jQuery("#price-slider-container").length === 0) {
                return false;
            }
            /*
                min: window._priceslider_settings.min,
                max: window._priceslider_settings.max,
                step: window._priceslider_settings.step,
                values: window._priceslider_settings.values,
            */
            
            jQuery(".price_slider_min").attr("min", window._priceslider_settings.min);
            jQuery(".price_slider_min").attr("max", window._priceslider_settings.max);
            
            jQuery(".price_slider_max").attr("min", window._priceslider_settings.min);
            jQuery(".price_slider_max").attr("max", window._priceslider_settings.max);
            
            var minPriceSlide = jQuery(".price_slider_min").attr("value", jQuery("#price_minimum").val() );
            var maxPriceSlide = jQuery(".price_slider_max").attr("value", jQuery("#price_maximum").val() );
            
            
            jQuery("#price-slider-container").slider({
                range: true,
                min: window._priceslider_settings.min,
                max: window._priceslider_settings.max,
                step: window._priceslider_settings.step,
                values: window._priceslider_settings.values,
                slide: function (a, b) {
                    jQuery("input#price_maximum").val(b.values[ 1 ]);
                    jQuery("input#price_minimum").val(b.values[ 0 ]);
                },
                change: function (a, b) {
                    jQuery("input#price_maximum").val(b.values[ 1 ]);
                    jQuery("input#price_minimum").val(b.values[ 0 ]);
                    //get new url w price filter
                    _this.ajaxOptions.reloadNavigation = true;
                    _this.changeUrl('price', b.values[ 0 ] + "-" + b.values[ 1 ], "");
                }
            });
            
            
            jQuery("#price_minimum, #price_maximum").on("keyup", function (a) {
                if (a.keyCode === 13) {
                    jQuery("button#price-filter-button").click();
                }
            });
            jQuery("button#price-filter-button").on("click", function (e) {
                e.preventDefault();
                var step_val = window._priceslider_settings.step;
                var request_price_min = Math.max(window._priceslider_settings.min, Math.floor(jQuery("input#price_minimum").val() / step_val) * step_val);
                var request_price_max = Math.min(window._priceslider_settings.max, Math.ceil(jQuery("input#price_maximum").val() / step_val) * step_val);
                _this.ajaxOptions.reloadNavigation = true;
                _this.changeUrl('price', request_price_min + "-" + request_price_max, "");
            });
        },

        _startNumericSliders: function () {

            /* numeric sliders */
            var sliders_config = this.options.sliders_configuration;
            var _this = this;
            $.each(sliders_config, function (code, config) {
                if (code.trim() === "")
                    return;
                var _slider_container_element = $("#" + code + "-slider-container");
                if (!_slider_container_element)
                    return;
                var _slider_data = _slider_container_element.data("slider-config");
                if (!_slider_data)
                    return;
                _slider_data.id = code + "-slider-container";
                _slider_data.var_id = code;
                if ($("#" + _slider_data.id).length === 0) {
                    return false;
                }
                $("#" + _slider_data.id).empty().slider({
                    range: true,
                    min: _slider_data.min,
                    max: _slider_data.max,
                    step: _slider_data.step,
                    values: [parseFloat(_slider_data.values[0]), parseFloat(_slider_data.values[1])],
                    slide: function (a, b) {
                        //console.log("slide...");
                        $("#" + _slider_data.var_id + "_minimum").val(b.values[ 0 ]);
                        $("#" + _slider_data.var_id + "_maximum").val(b.values[ 1 ]);
                    },
                    change: function (a, b) {
                        $("#" + _slider_data.var_id + "_minimum").val(b.values[ 0 ]);
                        $("#" + _slider_data.var_id + "_maximum").val(b.values[ 1 ]);
                        _this.ajaxOptions.reloadNavigation = true;
                        /* 2 attributes slider*/
                        if (_slider_data.attribute_1 && _slider_data.attribute_2) {
                            _this.changeUrl(
                                    [_slider_data.attribute_1, _slider_data.attribute_2],
                                    [b.values[ 0 ] + "-" + _slider_data.max_1, _slider_data.min_2 + "-" + b.values[ 1 ]],
                                    "");
                        } else {/*regular numeric slider...*/
                            _this.changeUrl(
                                    _slider_data.var_id,
                                    b.values[ 0 ] + "-" + b.values[ 1 ],
                                    "");
                        }
                    }
                });
                if (parseInt(_this.options.sliders_search_fields) === 1) {
                    $("input#" + code + "_minimum, input#" + code + "_maximum").on("keyup", function (a) {
                        if (a.keyCode === 13) {
                            $("button#" + code + "-filter-button").click();
                        }
                    });
                    $("button#" + code + "-filter-button").on("click", function (e) {
                        e.preventDefault();
                        var step_val = _slider_data.step;
                        var request_min = Math.max(_slider_data.min, Math.floor($("input#" + code + "_minimum").val() / step_val) * step_val);
                        var request_max = Math.min(_slider_data.max, Math.ceil($("input#" + code + "_maximum").val() / step_val) * step_val);
                        _this.ajaxOptions.reloadNavigation = true;
                        _this.changeUrl(_slider_data.var_id, request_min + "-" + request_max, "");
                    });
                }
            });

        },

        _startAttributeSliders: function () {

            /* attribute sliders */
            /* PROCESS ALL SLIDERS FOR ATTRIBUTES - SELECT AND MULTISELECT */
            var _this = this;
            var attribute_sliders_config = _this.options.attribute_sliders_configuration;
            if (Array.isArray(attribute_sliders_config)) {
                $.each(attribute_sliders_config, function (i, code) {
                    //$.each(ajaxLayeredNavigation.options.attribute_sliders_configuration, function(i, code) {
                    if ($("#" + code + "-config").length === 0) {
                        return true;
                    }
                    var _slider_data = $("#" + code + "-config").data("slider-config");
                    _slider_data.id = code + "-slider-container";
                    _slider_data.var_id = code;
                    $("#" + _slider_data.id).empty().slider({
                        range: true,
                        min: _slider_data.min,
                        max: _slider_data.max,
                        values: [parseFloat(_slider_data.values[0]), parseFloat(_slider_data.values[1])],
                        slide: function (event, ui) {
                            var _a = ui.values[ 0 ];
                            var _b = ui.values[ 1 ];
                            $("#" + _slider_data.var_id + "_slider-links > li").removeClass("active-filter-option");
                            for (var _i = _a; _i <= _b; _i++) {
                                $("#" + _slider_data.var_id + "_slider-links > li").eq(_i).addClass("active-filter-option");
                            }
                        },
                        change: function (event, ui) {
                            var _a = ui.values[ 0 ];
                            var _b = ui.values[ 1 ];
                            var _param = [];
                            for (var _i = _a; _i <= _b; _i++) {
                                _param.push($("#" + _slider_data.var_id + "_slider-links > li > a").eq(_i).data("attribute-value"));
                            }
                            var _url_param = _param.join(",");
                            _this.ajaxOptions.reloadNavigation = true;
                            _this.changeUrl(_slider_data.var_id, _url_param, "");
                        }
                    });
                });
            }

        },

        /* detect url change for history.js */
        /* for hashchange, detect url change and initial state on page load... */
        _categoriesTree: function () {
            //console.debug("categories tree..");
            /* new function to open close parents */
            $(document).on("click", "ol#category-filters li.parent > a.link-expand-collapse", function (e) {
                e.preventDefault();
                var _children = $(this).closest("li").find(" > ul");
                var childrenli = $(this).closest("li").find("ul li.level3");
                if ($(this).hasClass("category-collapse")) {
                    //_children.hide();
                    _children.attr('style','display:none');
                    childrenli.attr('style','display:none');
                    $(this).addClass("category-expand").removeClass("category-collapse");
                } else {
                   // _children.show();
                    _children.attr('style','display:block !important');
                    childrenli.attr('style','display:block !important');
                    $(this).removeClass("category-expand").addClass("category-collapse");
                }
                return false;
            });
        },
        /* expand items when loading page */
        expandCategoriesFilter: function () {
            $("ol#category-filters li.active-filter-option").parentsUntil('#category-filters', "ul").show();
            /* all parent items start with link-expand-collapse class */
            $("ol#category-filters li.active-filter-option").parentsUntil('#category-filters', "li").find("> a.link-expand-collapse").removeClass("category-expand").addClass("category-collapse");
        },
        changeUrl: function (paramName, paramValue, defaultValue) {
            window.stop();
            var urlPaths = this.options.url.split('?'),
                    baseUrl = urlPaths[0],
                    urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
                    paramData = {},
                    parameters;

            for (var i = 0; i < urlParams.length; i++) {
                parameters = urlParams[i].split('=');
                paramData[parameters[0]] = parameters[1] !== undefined
                        ? window.decodeURIComponent(parameters[1].replace(/\+/g, '%20'))
                        : '';
            }

            /* process multiple values... */
            if (!Array.isArray(paramName)) {
                paramName = [paramName];
            }

            if (!Array.isArray(paramValue)) {
                paramValue = [paramValue];
            }

            if (!Array.isArray(defaultValue)) {
                defaultValue = [defaultValue];
            }

            $.each(paramName, function (i, p) {
                paramData[p] = paramValue[i];
                if (paramValue[i] === defaultValue[i]) {
                    delete paramData[p];
                }

            });

            /* check if pagination values are set...
             *  mode, direction, order, limit 
             */
            /*if (paramData[ this.options.mode ]) {
                this.currentValues[this.options.mode] = paramData[ this.options.mode ]; }
            if (paramData[ this.options.direction ]) {
                this.currentValues[this.options.direction] = paramData[ this.options.direction ]; }
            if (paramData[ this.options.limit ]) {
                this.currentValues[this.options.limit] = paramData[ this.options.limit ]; }
            if (paramData[ this.options.order ]) {
             this.currentValues[this.options.order] = paramData[ this.options.order ]; }*/
            /* check if pagination values are set...
             *  mode, direction, order, limit 
             */
            if (paramData[ this.options.mode ]) {
                this.currentValues[this.options.mode] = paramData[ this.options.mode ];
            } else {
                delete this.currentValues[this.options.mode];
            }
            if (paramData[ this.options.direction ]) {
                this.currentValues[this.options.direction] = paramData[ this.options.direction ];
            } else {
                delete this.currentValues[this.options.direction];
            }
            if (paramData[ this.options.limit ]) {
                this.currentValues[this.options.limit] = paramData[ this.options.limit ];
            } else {
                delete this.currentValues[this.options.limit];
            }
            if (paramData[ this.options.order ]) {
                this.currentValues[this.options.order] = paramData[ this.options.order ];
            } else {
                delete this.currentValues[this.options.order];
            }

         
            paramData = $.param(paramData);
           
            
            var _url = baseUrl + (paramData.length ? '?' + paramData : '');

            _url = _url.replace(/&?p=[^&]*?/, '');
           
            //alert(_url);
           
            /* do not reload filters not compare block */
            this._changeUrlOnToolbar(_url, {}); 
        },
        _changeUrlOnToolbar: function (newUrl, additional_params) { 
            if (newUrl.indexOf('q=') != -1 && newUrl.indexOf('product_list_order=') == -1 ){
                newUrl = newUrl +'&product_list_order=personalized';              
            } 
            if (this.options.urlhandler === "hashChangeJs") {/* change hash only */
                var _additional = $.param(additional_params);
                window.location.hash = this._hashUrl(newUrl);
            } else if (this.options.urlhandler === "historyJs") { /* use history.js. will try to change the url */
                /* push using the history api */
                var History = window.History; // Note: We are using a capital H instead of a lower h
                History.pushState(null, document.title, newUrl);
            }
        },
        _hashUrl: function (a) {
            var r = a.match(/\?(.+)$/);
            if (!r) {
                return "#";
            }
            var b = RegExp.$1;
            if (b.indexOf(window._ajaxlist_parameter + "=1") >= 0) {
                b = b.replace(window._ajaxlist_parameter + "=1&", "");
                b = b.replace("&" + window._ajaxlist_parameter + "=1", "");
                b = b.replace(window._ajaxlist_parameter + "=1", "");
            }
            return b;
        },
        _initHashchangeJs: function () {
            /* run this after all functions have been assigned */
            var m = location.hash.slice(1);
            var _this = this;
            if (m !== "") {
                /* to do: check if reload navigation based on initial url!!! */
	        this.ajaxOptions.reloadNavigation = true;
                _this._handleHashChange();
            }
            $(window).hashchange(function (e) {
                //console.debug("doing smth...");
                _this._handleHashChange();
            });
        },
        _handleHashChange: function () {
            var a, path, tmp, nv, cm;
            a = location.hash.slice(1);
            path = window.location.href;
            path = path.split("#")[0];
            tmp = path;
            path = path.split("?")[0];
            path = path + "?" + a;

            this.loadAjaxProductsList(path, cm); //, false);
            if (a === "" && typeof (window.history.pushState) === 'function') {/* remove hash symbol when empty parameters */
                window.history.pushState({}, '', tmp);
            }
        },
        _initHistoryJs: function () {
            var _this = this;
            var History = window.History; // Note: We are using a capital H instead of a lower h
            if (!History.enabled) {
                // History.js is disabled for this browser.
                // This is because we can optionally choose to support HTML4 browsers or not.#
                return false;
            }
            // Bind to StateChange Event
            History.Adapter.bind(window, 'statechange', function () {
 // Note: We are using statechange instead of popstate
                var State = History.getState();
                _this.loadAjaxProductsList(State.url);
            });
        },
        loadAjaxProductsList: function (_url, _load_compare) {           
 //, _additional_params) {
            var _this = this;
            var _loaderTemplate = "<div class=\"products-list-loader\"><div></div></div>";
            jQuery(this.options.product_list_element).append(_loaderTemplate);
            if (this.ajaxOptions.reloadNavigation) {
                jQuery(this.options.layered_navigation_element).append(_loaderTemplate);
            }
            
            //var _data = {};
            var _data = {
                ajax_request: 1,
                ajaxlist: 1,
                reload_navigation: this.ajaxOptions.reloadNavigation
            };
            
            /* add additional parameters */
            /*if (typeof (_additional_params) !== "undefined") {
             jQuery.extend(_data, _additional_params);
             }*/
            //_additional_params = {};
            /*  set toolbar values when list is loaded from layered navigation... */
            if (this.ajaxOptions.reloadNavigation) {
                jQuery.extend(_data, this.currentValues);
            }
            /* trigger custom event */
            jQuery(document).trigger("beforeAjaxProductsLoaded", [_this.ajaxOptions.reloadNavigation]);
            _url = _url +'&cus=custom';
            jQuery.ajax(
                _url,
                {data: _data,
                        /* todo:test this https/http sites */
                        /* beforeSend: function(xhr) {
                         xhr.setRequestHeader('X-Requested-With', {toString: function() {
                         return '';
                         }});
                         },*/
                        success: function (a, b, c) {
                            if (b === "error") {
                                jQuery(window._product_list_element).html("<p>There was an error making the AJAX request</p>");
                            } else {
                                jQuery(_this.options.product_list_element).replaceWith(a.product_list);
                                //_this._bindEvents();
                                if (_this.ajaxOptions.reloadNavigation) {
                                    /* remove horizontal layered navigation first */
                                    if (parseInt(_this.options.horizontal_mode)===1) {
                                        jQuery(_this.options.horizontal_layered_navigation_element).remove();
                                    }
                                    jQuery(_this.options.layered_navigation_element).replaceWith(a.navigation);
                                    _this._createLayeredNavigation();
                                }
                                /* create event triggers / observers .. */
                                /*  how to use:
                                 * ADD CODE TO ANOTHER FILE:
                                 * jQuery( document ).on( "afterAjaxProductsLoaded", function( event, reloadFiltersBlock ) {
                                 *      CUSTOM CODE HERE....
                                 * })
                                 */
                                /* update widget base url */
                                _this.options.url = window._ajaxlayerednavigation_current_url;
                                jQuery(document).trigger("afterAjaxProductsLoaded", [_this.ajaxOptions.reloadNavigation]);
                                /* enable this file in the extension configuration and the edit the afterReload.js file */
                                /*if (typeof window.afterAjaxReload === 'function') {
                                 window.afterAjaxReload();
                                 }*/
                                /* reload default Magento scripts after ajax load */
                                /*product_list_element =  '#ajaxlist-reload-product_list'*/
                                jQuery(_this.options.product_list_element).trigger('contentUpdated');
                                
                                /*scroll to horizontal layered navigation block
                                 * or to products list top */
                                
                                var _scroll_element = _this.options.product_list_element;
                                if ((parseInt(_this.options.horizontal_mode)===1 || parseInt(_this.options.horizontal_only)===1) && jQuery(_this.options.horizontal_layered_navigation_element).length > 0) {
                                    _scroll_element = _this.options.horizontal_layered_navigation_element;
                                }
                                
                                jQuery('html, body').animate({
                                    scrollTop: jQuery(_scroll_element).offset().top
                                }, 500);
                            }
                        }
                }
            );
        },
        
        _toggleDesktopMode: function () {
            if (parseInt(this.options.horizontal_mode) === 1) {
                jQuery(this.options.product_list_element).before(jQuery("#horizontal-layered-navigation-container"));
            }
        },

        _toggleMobileMode: function () {
            if (parseInt(this.options.horizontal_mode) === 1) {
                jQuery("#narrow-by-list").append(jQuery("#horizontal-layered-navigation-container"));
            }
        },

        disableInfiniteScroll: function () {
            if (this.options.infinite_scroll.enable) {
                if ($("#ajaxlist-reload-product_list").data("jscroll")) {
                    /* copied from destroy function . todo: use destroy function.. */
                    $("#ajaxlist-reload-product_list").unbind('.jscroll')
                        .removeData('jscroll')
                        .find('.jscroll-inner').children().unwrap()
                        .filter('.jscroll-added').children().unwrap();
                    $(window).jscroll.destroy();
                }
            }
        },
        
        enableInfiniteScroll: function () {
            /* check if infinite scroll is enabled and whether the next page element exists.. */
            //$.jscroll = require(['jscroll']);
            //console.debug($.jscroll);

            $("div.toolbar-bottom").remove();
            if (this.options.infinite_scroll.enable && $("#ajaxlist-reload-product_list a.ajaxcatalog-infinite-scroll-next").length > 0 ) {
                var _this = this;  console.debug("callbackdfsdf...");
                var _settings = jQuery.extend(this.options.infinite_scroll , {
                    /*loadingHtml : "<div class=\"products-list-loader\"><div></div></div>",*/
                    loadingHtml: '<div class="products-list-loader infinite-scroll-loader"><div></div></div>',
                    contentSelector : "div.ajaxlist-reload-product_list",
                    nextSelector : "a.ajaxcatalog-infinite-scroll-next",
                    loadingFunction: function () {
                        var _data = $("#ajaxlist-reload-product_list").data("jscroll");
                        _data.nextHref = _data.nextHref.replace(" div.ajaxlist-reload-product_list" , "&ajaxlist=1&ajax_request=1&infinite_scroll=1 div.ajaxlist-reload-product_list");
                    },
                    callback: function () {
                       console.debug("callback...");
                        $(document).trigger("afterAjaxProductsLoaded", [false]);/* _this.ajaxOptions.reloadNavigation : reload navigation:false */
                        /* reload default Magento scripts after ajax load */
                        /*product_list_element =  '#ajaxlist-reload-product_list'*/
                        $(document).find('.ajaxlist-reload-product_list').last().trigger('contentUpdated');
                        $(document).find('.ajaxlist-reload-product_list').each(function(){
                            $(this).trigger('contentUpdated');
                        });
                        
                    }/*,
                    callback: afterAjaxReload() */
                });
                
                require(['jquery', 'jscroll'], function ($ ) {
                    
                    console.debug(_settings);
                    $("#ajaxlist-reload-product_list").jscroll(_settings);

                });
            }
        },
    
        _initInfiniteScroll: function () {

            //console.debug("will try infinite scroll");

            if (this.options.infinite_scroll.enable) {
                //var _this = this;
                    
                
                this.enableInfiniteScroll();

                var _this = this;

                jQuery(document).on("beforeAjaxProductsLoaded", function () {
                    _this.disableInfiniteScroll();
                    $(document).find('.ajaxlist-reload-product_list').last().trigger('contentUpdated');
                });
                jQuery(document).on("afterAjaxProductsLoaded", function (  ) {
                    $(document).find('.ajaxlist-reload-product_list').last().trigger('contentUpdated');
                    _this.enableInfiniteScroll();
                     $(document).find('.ajaxlist-reload-product_list').last().trigger('contentUpdated');
                });
                    
                //});
            }
        }
        
    });
    if (typeof $.custom.ajaxLayeredNavigation === "undefined") {
        return $.custom.ajaxLayeredNavigation;
    }
});
