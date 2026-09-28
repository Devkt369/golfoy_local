/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'apollo-boost',
    'mage/url'
], function (Apollo, urlBuilder) {
    'use strict';
    const {ApolloClient, gql} = Apollo;
    var graphQlUrl = urlBuilder.build('graphql');
    return function (query, variables = {}, token = '') {
        var graphqlClient = new ApolloClient(
            {
                uri: graphQlUrl,
                request: (operation) => {
                    operation.setContext({
                        headers: {
                            authorization: token ? `Bearer ${token}` : ''
                        }
                    })
                }
            }
        );
        return graphqlClient.query(
            {
                query: gql(query),
                variables: variables
            }
        )
    };
});
