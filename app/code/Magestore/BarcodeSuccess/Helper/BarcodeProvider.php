<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BarcodeSuccess\Helper;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\BarcodeSuccess\Helper\Data as BarcodeHelper;

/**
 * Class BarcodeProvider
 *
 * Provide barcode from product's attribute
 */
class BarcodeProvider extends AbstractHelper
{
    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $barcodeHelper;

    /**
     * @param Context $context
     * @param DataProvider $dataProvider
     * @param StoreManagerInterface $storeManager
     * @param Data $barcodeHelper
     */
    public function __construct(
        Context $context,
        DataProvider $dataProvider,
        StoreManagerInterface $storeManager,
        BarcodeHelper $barcodeHelper
    ) {
        $this->dataProvider = $dataProvider;
        $this->storeManager = $storeManager;
        $this->barcodeHelper = $barcodeHelper;
        parent::__construct($context);
    }

    /**
     * Get ProductAttribute
     *
     * @param array $productIds
     * @param string|null $barcodeAttributeCode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductAttribute($productIds, $barcodeAttributeCode = null)
    {
        if (empty($barcodeAttributeCode)) {
            $barcodeAttributeCode = $this->barcodeHelper->getAttributeCode();
        }
        $barcodeAttribute = $this->dataProvider->getSearchableAttribute($barcodeAttributeCode);
        $attributeTypes[$barcodeAttribute->getBackendType()] = [$barcodeAttribute->getAttributeId()];
        $storeId = $this->storeManager->getStore()->getId();

        return $this->dataProvider->getProductAttributes($storeId, $productIds, $attributeTypes);
    }

    /**
     * Get BarcodeAttribute
     *
     * @param string|null $barcodeAttributeCode
     * @return \Magento\Eav\Model\Entity\Attribute
     */
    public function getBarcodeAttribute($barcodeAttributeCode = null)
    {
        if (empty($barcodeAttributeCode)) {
            $barcodeAttributeCode = $this->barcodeHelper->getAttributeCode();
        }
        return $this->dataProvider->getSearchableAttribute($barcodeAttributeCode);
    }
}
