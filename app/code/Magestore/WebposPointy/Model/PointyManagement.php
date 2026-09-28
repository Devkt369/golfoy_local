<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Model\Location\Location;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magestore\WebposPointy\Api\PointyConnectorInterface;
use Magestore\WebposPointy\Api\PointyIntegrationInterface;
use Magestore\WebposPointy\Api\PointyManagementInterface;
use Magestore\WebposPointy\Api\PointySignupLocationPrepareDataInterface;
use Magestore\WebposPointy\Api\PointySignupLocationValidationInterface;
use Magestore\WebposPointy\Api\SubmitInventoryPrepareDataInterface;
use Magestore\WebposPointy\Api\SubmitSalesPrepareDataInterface;
use Magestore\WebposPointy\Log\Logger;
use Magestore\WebposPointy\Api\Request\PointyKeyInterface;
use Magestore\WebposPointy\Api\Request\PointyInventoryInterface;

/**
 * Pointy management service
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PointyManagement implements PointyManagementInterface
{
    /**
     * @var PointySignupLocationValidationInterface
     */
    private $pointySignupValidation;
    /**
     * @var PointySignupLocationPrepareDataInterface
     */
    private $pointySignupPreparation;
    /**
     * @var PointyConnectorInterface
     */
    private $pointyConnector;
    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;
    /**
     * @var Json
     */
    private $serializer;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var SubmitSalesPrepareDataInterface
     */
    private $submitSalesPrepareDataInterface;

    /**
     * @var SubmitInventoryPrepareDataInterface
     */
    private $submitInventoryPrepareData;

    /**
     * @var PointyConfigManagementInterface
     */
    private $pointyConfigManagement;

    /**
     * PointyManagement constructor.
     * @param PointySignupLocationValidationInterface $pointySignupValidation
     * @param PointySignupLocationPrepareDataInterface $pointySignupPreparation
     * @param PointyConnectorInterface $pointyConnector
     * @param LocationRepositoryInterface $locationRepository
     * @param Json $serializer
     * @param Logger $logger
     * @param SubmitInventoryPrepareDataInterface $submitInventoryPrepareData
     * @param PointyConfigManagementInterface $pointyConfigManagement
     * @param SubmitSalesPrepareDataInterface $submitSalesPrepareDataInterface
     */
    public function __construct(
        PointySignupLocationValidationInterface $pointySignupValidation,
        PointySignupLocationPrepareDataInterface $pointySignupPreparation,
        PointyConnectorInterface $pointyConnector,
        LocationRepositoryInterface $locationRepository,
        Json $serializer,
        Logger $logger,
        SubmitInventoryPrepareDataInterface $submitInventoryPrepareData,
        PointyConfigManagementInterface $pointyConfigManagement,
        SubmitSalesPrepareDataInterface $submitSalesPrepareDataInterface
    ) {
        $this->pointySignupValidation = $pointySignupValidation;
        $this->pointySignupPreparation = $pointySignupPreparation;
        $this->pointyConnector = $pointyConnector;
        $this->locationRepository = $locationRepository;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->submitInventoryPrepareData = $submitInventoryPrepareData;
        $this->pointyConfigManagement = $pointyConfigManagement;
        $this->submitSalesPrepareDataInterface = $submitSalesPrepareDataInterface;
    }

    /**
     * @inheritDoc
     */
    public function signupLocation(int $locationId): array
    {
        $location = $this->locationRepository->getById($locationId);
        $result = ['has_error' => false];
        $validationResult = $this->pointySignupValidation->execute($location);
        if ($validationResult['error']) {
            $result['has_error'] = true;
            $result['message'] = $validationResult['messages'] ? implode(' ', $validationResult['messages']) : '';
            return $result;
        }

        $signupData = $this->pointySignupPreparation->execute($location);
        $response = $this->pointyConnector->signupLocation($signupData);
        $body = $response->getBody() ? $this->serializer->unserialize($response->getBody()) : [];
        if (!$body) {
            $result['has_error'] = true;
            $result['message'] = 'Some thing wrong while signing up this location on Pointy.';
            return $result;
        }
        if ($body['status']['code'] != "ok") {
            $result['has_error'] = true;
            $result['message'] = isset($body['status']['error_description'])
                ? $body['status']['error_description']
                : '';
            $result['message'] .= isset($body['status']['error_descriptions'])
                ? implode('. ', $body['status']['error_descriptions'])
                : '';
            return $result;
        }

        $result['signup_result'] = [
            'shop_id' => $body['shop_id'],
            'retailer_auth_key' => $body['retailer_auth_key']
        ];
        $this->updateLocationWithSignupInfo($location, $result['signup_result']);

        return $result;
    }

    /**
     * Update Location With Signup Info
     *
     * @param Location $location
     * @param array $signupResult
     */
    public function updateLocationWithSignupInfo(Location $location, array $signupResult)
    {
        $location->setPointyEnable(PointyIntegrationInterface::VALUE_ENABLED)
            ->setPointyRetailerAuthKey($signupResult['retailer_auth_key'])
            ->setPointyShopId($signupResult['shop_id']);
        $this->locationRepository->save($location);
    }

    /**
     * Submit sales
     *
     * @param int $locationId
     */
    public function submitSales(int $locationId)
    {
        try {
            $location = $this->locationRepository->getById($locationId);
            if (!$location->getPointyRetailerAuthKey() || !$location->getPointyEnable()) {
                $this->logger->info('The location has not been enabled pointy yet.');
                return;
            }
            $params = $this->submitSalesPrepareDataInterface->execute($location);
            if (empty($params)) {
                $this->logger->info('The location has not have new sales.');
                return;
            }
            $response = $this->pointyConnector->submitSales($params);
            $body = $response->getBody() ? $this->serializer->unserialize($response->getBody()) : [];
            if ($body['code'] == "ok") {
                $location->setPointyLastSubmitSales(
                    (new \DateTime())->format('Y-m-d H:i:s')
                );
                $this->locationRepository->save($location);
            } else {
                $this->logger->info("Submit sales failed", $body);
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function submitInventory(int $locationId)
    {
        try {
            $location = $this->locationRepository->getById($locationId);
            $inventoryData = $this->submitInventoryPrepareData->execute($location);
            // Request enabling products
            foreach ($inventoryData[SubmitInventoryPrepareDataInterface::PRODUCTS] as $data) {
                $result = $this->requestSubmitInventory($location, $data);
                if ($result['has_error']) {
                    $this->logger->info("Submit inventory failed", $result['error_info']);
                    return;
                }
            }

            // Request deleted products
            foreach ($inventoryData[SubmitInventoryPrepareDataInterface::DELETED_PRODUCTS] as $data) {
                $result = $this->requestSubmitInventory($location, $data);
                if ($result['has_error']) {
                    $this->logger->info("Submit inventory failed", $result['error_info']);
                    return;
                }
            }
            $time = new \Datetime();
            $location->setPointyLastSubmitInventory($time->format('Y-m-d H:i:s'));
            $this->locationRepository->save($location);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Request Submit Inventory
     *
     * @param LocationInterface $location
     * @param array $records
     * @return array
     */
    public function requestSubmitInventory(LocationInterface $location, array $records)
    {
        $params = [
            PointyKeyInterface::RETAILER_AUTH_KEY => $location->getPointyRetailerAuthKey(),
            PointyKeyInterface::CLIENT_AUTH_KEY => $this->pointyConfigManagement->getClientAuthKey(),
            PointyInventoryInterface::RECORDS => $records,
            PointyInventoryInterface::VALIDATION_OPTIONS => [
                PointyInventoryInterface::DISABLE_CODE_VALIDATION => false
            ]
        ];
        $response = $this->pointyConnector->submitInventory($params);
        $body = $response->getBody() ? $this->serializer->unserialize($response->getBody()) : [];
        if (empty($body) || $body['code'] != "ok") {
            return [
                'has_error' => true,
                'error_info' => $body ?? []
            ];
        }
        return [
            'has_error' => false,
            'error_info' => []
        ];
    }
}
