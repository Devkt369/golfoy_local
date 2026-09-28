<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Pointy APIs
 */
interface PointyConnectorInterface
{
    /**
     * Sign up Pointy
     *
     * @param array $params
     * @return \Zend_Http_Response
     */
    public function signupLocation(array $params): \Zend_Http_Response;

    /**
     * Get Client Auth Key
     *
     * @param array $params
     * @return \Zend_Http_Response
     */
    public function submitInventory(array $params): \Zend_Http_Response;

    /**
     * Get Manufacturer Barcode Attribute
     *
     * @param array $params
     * @return \Zend_Http_Response
     */
    public function submitSales(array $params): \Zend_Http_Response;
}
