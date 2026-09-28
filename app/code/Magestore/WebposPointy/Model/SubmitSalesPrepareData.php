<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magestore\Webpos\Model\Location\Location;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magestore\WebposPointy\Api\SubmitSalesPrepareDataInterface;
use Magestore\WebposPointy\Model\ResourceModel\Location\Location as LocationResource;
use Magestore\WebposPointy\Api\AttributeDataProviderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\WebposPointy\Api\Request\PointyKeyInterface;
use Magestore\WebposPointy\Api\Request\PointySalesInterface;

/**
 * Prepare data for submit sales
 */
class SubmitSalesPrepareData implements SubmitSalesPrepareDataInterface
{
    /**
     * @var PointyConfigManagementInterface
     */
    private $pointyConfigManagement;
    /**
     * @var LocationResource
     */
    private $locationResource;
    /**
     * @var AttributeDataProviderInterface
     */
    private $attributeDataProvider;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * SubmitSalesPrepareData constructor.
     * @param PointyConfigManagementInterface $pointyConfigManagement
     * @param LocationResource $locationResource
     * @param AttributeDataProviderInterface $attributeDataProvider
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        PointyConfigManagementInterface $pointyConfigManagement,
        LocationResource $locationResource,
        AttributeDataProviderInterface $attributeDataProvider,
        TimezoneInterface $localeDate
    ) {
        $this->pointyConfigManagement = $pointyConfigManagement;
        $this->locationResource = $locationResource;
        $this->attributeDataProvider = $attributeDataProvider;
        $this->localeDate = $localeDate;
    }

    /**
     * @inheritDoc
     */
    public function execute(Location $location): array
    {
        $items = $this->locationResource->getInvoiceData($location->getId(), $location->getPointyLastSubmitSales());
        if (empty($items)) {
            return [];
        }
        return [
            PointyKeyInterface::RETAILER_AUTH_KEY => $location->getPointyRetailerAuthKey(),
            PointyKeyInterface::CLIENT_AUTH_KEY => $this->pointyConfigManagement->getClientAuthKey(),
            PointySalesInterface::RECORDS => $this->modifySalesItemsData($items),
            PointySalesInterface::VALIDATION_OPTIONS => [
                PointySalesInterface::DISABLE_CODE_VALIDATION => false
            ]
        ];
    }

    /**
     * Modify Sales Items Data
     *
     * @param array $items
     * @return array
     */
    public function modifySalesItemsData(array $items)
    {
        $result = [];
        $codeAttribute = $this->pointyConfigManagement->getManufacturerBarcodeAttribute();
        if ($codeAttribute === 'sku') {
            foreach ($items as $item) {
                $result[] = [
                    PointySalesInterface::CODE => $item['sku'],
                    PointySalesInterface::ITEM_ID => $item['sku'],
                    PointySalesInterface::UNIT_PRICE => $item['base_unit_price'] ?: 0,
                    PointySalesInterface::QUANTITY => $item['qty'],
                    PointySalesInterface::TIMESTAMP => $this->localeDate->date($item['timestamp'], null, false)
                        ->format('Ymd\THis\Z')
                ];
            }
        } else {
            $codeAttributeData = $this->attributeDataProvider->execute([$codeAttribute]);
            foreach ($items as $item) {
                if (!isset($codeAttributeData[$item['product_id']])
                    || !isset($codeAttributeData[$item['product_id']][$codeAttribute])) {
                    continue;
                }
                $result[] = [
                    PointySalesInterface::CODE => $codeAttributeData[$item['product_id']][$codeAttribute],
                    PointySalesInterface::ITEM_ID => $item['sku'],
                    PointySalesInterface::UNIT_PRICE => $item['base_unit_price'] ?: 0,
                    PointySalesInterface::QUANTITY => $item['qty'],
                    PointySalesInterface::TIMESTAMP => $this->localeDate->date($item['timestamp'], null, false)
                        ->format('Ymd\THis\Z')
                ];
            }
        }
        return $result;
    }
}
