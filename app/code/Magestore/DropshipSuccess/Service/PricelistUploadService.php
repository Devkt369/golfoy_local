<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\DropshipSuccess\Service;

use Magento\Framework\Data\Collection\ModelFactory;
use Magestore\DropshipSuccess\Api\Data\SupplierPricelistUploadInterface;
use Magestore\DropshipSuccess\Model\ResourceModel\Supplier\PricelistUpload\CollectionFactory
    as PricelistUploadCollectionFactory;
use Magestore\DropshipSuccess\Model\Supplier\PricelistUploadFactory;
use Magestore\DropshipSuccess\Model\ResourceModel\Supplier\PricelistUpload\Collection;

/**
 * Class PricelistUploadService
 *
 * PricelistUploadService for dropship
 */
class PricelistUploadService
{
    /**
     * @var PriceListUploadFactory
     */
    protected $priceListUploadFactory;

    /**
     * @var PricelistUploadCollectionFactory
     */
    protected $pricelistUploadCollectionFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PricelistUploadService constructor.
     *
     * @param PriceListUploadFactory $priceListUploadFactory
     * @param PricelistUploadCollectionFactory $pricelistUploadCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        PriceListUploadFactory $priceListUploadFactory,
        PricelistUploadCollectionFactory $pricelistUploadCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->priceListUploadFactory = $priceListUploadFactory;
        $this->pricelistUploadCollectionFactory = $pricelistUploadCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get pricelist upload by supplier id
     *
     * @param int $supplierId
     * @return \Magestore\DropshipSuccess\Model\ResourceModel\Supplier\PricelistUpload\Collection
     */
    public function getPricelistUploadBySupplierId($supplierId)
    {
        /**
         * @var Collection $pricelistUploadCollection
         */
        $pricelistUploadCollection = $this->pricelistUploadCollectionFactory->create();
        $pricelistUploadCollection->addFieldToFilter(SupplierPricelistUploadInterface::SUPPLIER_ID, $supplierId);
        $pricelistUploadCollection->setOrder(SupplierPricelistUploadInterface::SUPPLIER_PRICELIST_UPLOAD_ID, 'DESC');

        return $pricelistUploadCollection;
    }

    /**
     * Get pricelist upload by id
     *
     * @param int $id
     * @return \Magestore\DropshipSuccess\Model\Supplier\PricelistUpload
     */
    public function getPricelistUploadById($id)
    {
        return $this->priceListUploadFactory->create()->load($id);
    }

    /**
     * Dropship path to upload pricelist
     *
     * @param int $supplierId
     * @return string
     */
    public function getDropshipUploadPath($supplierId)
    {
        return 'dropship/pricelist/'.$supplierId;
    }

    /**
     * Get Price List Upload link
     *
     * @param SupplierPricelistUploadInterface $pricelistUpload
     * @return string
     */
    public function getPricelistUploadLink(SupplierPricelistUploadInterface $pricelistUpload)
    {
        $supplierId = $pricelistUpload->getSupplierId();
        $pricelistUploadLink = $this->getDropshipUploadPath($supplierId) . '/'.$pricelistUpload->getFileUpload();
        return $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA). $pricelistUploadLink;
    }

    /**
     * Get Pricelist link by supplierId and Upload file
     *
     * @param int $supplierId
     * @param string $fileUpload
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPriceListLinkBySupplierAndUpload($supplierId, $fileUpload)
    {
        $pricelistUploadLink = $this->getDropshipUploadPath($supplierId) . '/'.$fileUpload;
        return $pricelistUploadLink;
    }
}
