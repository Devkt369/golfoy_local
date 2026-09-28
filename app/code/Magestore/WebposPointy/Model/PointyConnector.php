<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magento\Framework\Webapi\Rest\Request;
use Magestore\Core\Model\Connector\Http\ClientInterface;
use Magestore\WebposPointy\Api\PointyConnectorInterface;
use Magestore\WebposPointy\Log\Logger;
use Zend_Http_Response;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;

/**
 * Pointy - connector
 */
class PointyConnector implements PointyConnectorInterface
{
    const API_URL_SIGNUP = 'https://www.pointy.com/api/v1/integration.signup';
    const API_URL_SUBMIT_INVENTORY = 'https://www.pointy.com/api/v1/integration.submit_inventory';
    const API_URL_SUBMIT_SALES = 'https://www.pointy.com/api/v1/integration.submit_sales';

    const SANDBOX_API_URL_SIGNUP = 'https://www.pomo-staging.com/api/v1/integration.signup';
    const SANDBOX_API_URL_SUBMIT_INVENTORY = 'https://www.pomo-staging.com/api/v1/integration.submit_inventory';
    const SANDBOX_API_URL_SUBMIT_SALES = 'https://www.pomo-staging.com/api/v1/integration.submit_sales';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var PointyConfigManagementInterface
     */
    private $pointyConfigManagement;

    /**
     * PointyConnector constructor.
     * @param Logger $logger
     * @param ClientInterface $httpClient
     * @param PointyConfigManagementInterface $pointyConfigManagement
     */
    public function __construct(
        Logger $logger,
        ClientInterface $httpClient,
        PointyConfigManagementInterface $pointyConfigManagement
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->pointyConfigManagement = $pointyConfigManagement;
    }

    /**
     * @inheritDoc
     */
    public function signupLocation(array $params): Zend_Http_Response
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $url = $this->pointyConfigManagement->isSandboxMode()
            ? self::SANDBOX_API_URL_SIGNUP
            : self::API_URL_SIGNUP;
        return $this->doRequest(
            $url,
            $headers,
            $params,
            Request::HTTP_METHOD_POST
        );
    }

    /**
     * @inheritDoc
     */
    public function submitInventory(array $params): Zend_Http_Response
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $url = $this->pointyConfigManagement->isSandboxMode()
            ? self::SANDBOX_API_URL_SUBMIT_INVENTORY
            : self::API_URL_SUBMIT_INVENTORY;
        return $this->doRequest(
            $url,
            $headers,
            $params,
            Request::HTTP_METHOD_POST
        );
    }

    /**
     * @inheritDoc
     */
    public function submitSales(array $params): Zend_Http_Response
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];
        $url = $this->pointyConfigManagement->isSandboxMode()
            ? self::SANDBOX_API_URL_SUBMIT_SALES
            : self::API_URL_SUBMIT_SALES;
        return $this->doRequest(
            $url,
            $headers,
            $params,
            Request::HTTP_METHOD_POST
        );
    }

    /**
     * Do API request with provided params
     *
     * @param string $url
     * @param array $headers
     * @param array $body
     * @param string $requestMethod
     * @return Zend_Http_Response
     */
    private function doRequest(
        string $url,
        array $headers = [],
        array $body = [],
        string $requestMethod = Request::HTTP_METHOD_GET
    ): Zend_Http_Response {
        $this->logger->info($url, $body);
        $response = $this->httpClient->request(
            $requestMethod,
            $url,
            $body,
            $headers
        );
        $this->logger->info($response->getBody());
        return $response;
    }
}
