<?php

namespace ArtM\BulkOrder\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_productRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        parent::__construct($context);
        $this->_productRepository = $productRepository;
    }

    public function getSkuAvailability(array $sku)
    {
        foreach ($sku as $item) {
            if (!$this->isSkuAvailable($item)) {
                return false;
            }
        }
        return true;
    }

    public function isSkuAvailable($sku)
    {
        try {
            $product = $this->_productRepository->get($sku);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
        if (!$product) {
            return false;
        }
        return true;
    }

}