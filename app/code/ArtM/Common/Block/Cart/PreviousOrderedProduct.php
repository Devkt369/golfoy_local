<?php

namespace ArtM\Common\Block\Cart;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use ArtM\Optimization\Api\ProductCacheRepositoryInterface;
use Magento\Catalog\Helper\Image;
use ArtM\Optimization\Block\Product\ListProduct;

class PreviousOrderedProduct extends Template
{
    protected $customerSession;
    protected $orderCollectionFactory;
    protected $productCacheRepository;
    protected $Image;
    protected $listProduct;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param CustomerSession $customerSession
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        ProductCacheRepositoryInterface $productCacheRepository,
        OrderCollectionFactory $orderCollectionFactory,
        ListProduct $listProduct,
        Image $Image,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->listProduct = $listProduct;
        $this->productCacheRepository = $productCacheRepository;
        $this->Image = $Image;
        parent::__construct($context, $data);
    }

    /**
     * Get last purchase information of the logged-in customer
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function getLastPurchase()
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            return null; // No customer logged in
        }

        $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', $customerId)
                ->setOrder('created_at', 'DESC')
                ->setPageSize(15);

        return !empty($orderCollection) ? $orderCollection : null;
    }

    public function getConfigValue($storeConfigPath)
    {
        return $this->scopeConfig->getValue(
            $storeConfigPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getStoreId()
        );
    }

    public function getProductImage($product){
        return $this->Image->init($product, 'product_listing_thumbnail')
                ->resize(380)
                ->getUrl();
    }

    public function getCachePriceHtml($item)
    {
        try {
            $productCache = $this->productCacheRepository->getByProductId($item->getId());
            return $productCache->getPriceHtml();
        } catch (\Exception $e) {
            $this->listProduct->checkAndUpdateProductCacheData($item);
            return $this->getCachePriceHtml($item);
        }
    }
}
