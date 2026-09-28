<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ArtM\RecommendedSort\Observer\Adminhtml;

class ProductSaveAfterObserver implements \Magento\Framework\Event\ObserverInterface
{
    protected $dataHelper;
    protected $request;

    public function __construct(
        \ArtM\RecommendedSort\Helper\Data $dataHelper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->dataHelper = $dataHelper;
        $this->request = $request;
    }

    /**
     * Set Gift Card conditions when product is saved
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        try {
            $this->dataHelper->addProductToQueue($product->getId(), 'product-save-after');
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__($exception->getMessage()));
        }
    }
}
