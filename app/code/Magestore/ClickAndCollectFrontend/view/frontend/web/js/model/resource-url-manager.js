/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'Magento_Checkout/js/model/resource-url-manager', 'graphql-query'], function (
    $,
    resourceUrlManager,
    graphqlQuery
) {
    'use strict';

    return {
        /**
         * Returns graphql to search get location list
         */
        getLocationList: function (curPage, pageSize, stockId) {
            return graphqlQuery(`query msGetAllLocationListAtFrontend($curPage: Int, $pageSize: Int, $stockId: Int) {
                  locations(
                      currentPage: $curPage,
                      pageSize: $pageSize,
                      stockId: $stockId,
                      filter: {is_allow_pickup: { eq: "1" }}
                  ) {
                    total_count
                    items {
                        location_id,
                        name,
                        description,
                        telephone,
                        street,
                        city,
                        region,
                        region_id,
                        country_id,
                        country,
                        postcode,
                        latitude,
                        longitude,
                        schedule {
                            monday_status,
                            tuesday_status,
                            wednesday_status,
                            thursday_status,
                            friday_status,
                            saturday_status,
                            sunday_status,
                            monday_open,
                            tuesday_open,
                            wednesday_open,
                            thursday_open,
                            friday_open,
                            saturday_open,
                            sunday_open,
                            monday_close,
                            tuesday_close,
                            wednesday_close,
                            thursday_close,
                            friday_close,
                            saturday_close,
                            sunday_close
                        }
                        special_days {
                            store_status,
                            repeat,
                            date_from,
                            date_to,
                            time_open,
                            time_close
                        },                  
                        timezone
                    }
                  }
                }`, {curPage: curPage, pageSize: pageSize, stockId: stockId});
        },

        /**
         * Search Store by search key
         */
        searchStore: function (searchKey, radius, stockId) {
            return graphqlQuery(`query msGetSortedLocationByDistanceAtFrontend($searchKey: String!, $radius: Float!, $stockId: Int) {
                  sortedLocationByDistance(
                      address: $searchKey,
                      radius: $radius,
                      stockId: $stockId
                  ) {
                    items {
                        location_id,
                        name,
                        description,
                        telephone,
                        street,
                        city,
                        region,
                        region_id,
                        country_id,
                        country,
                        postcode,
                        latitude,
                        longitude,
                        schedule {
                            monday_status,
                            tuesday_status,
                            wednesday_status,
                            thursday_status,
                            friday_status,
                            saturday_status,
                            sunday_status,
                            monday_open,
                            tuesday_open,
                            wednesday_open,
                            thursday_open,
                            friday_open,
                            saturday_open,
                            sunday_open,
                            monday_close,
                            tuesday_close,
                            wednesday_close,
                            thursday_close,
                            friday_close,
                            saturday_close,
                            sunday_close
                        }
                        special_days {
                            store_status,
                            repeat,
                            date_from,
                            date_to,
                            time_open,
                            time_close
                        },
                        distance,
                        timezone
                    }
                  }
                }`, {searchKey: searchKey, radius: radius, stockId: stockId});
        }
    };
});
