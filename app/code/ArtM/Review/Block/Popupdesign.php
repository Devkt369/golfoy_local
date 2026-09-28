<?php
namespace ArtM\Review\Block;

class Popupdesign extends \Magento\Framework\View\Element\Template
{
    protected $helper;
    protected $_reviewFactory;
    protected $_ratingFactory;
    protected $_storeManager;
    protected $_getcron;
    protected $_getreviewedorder;
    protected $productCollectionFactory;
    protected $imageHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \ArtM\Review\Helper\Data $helper,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \ArtM\Review\Model\CronFactory $cron,
        \ArtM\Review\Model\GetreviewedorderFactory $getreviewedorder,
        // ADD THESE TWO — replaces ObjectManager product loading
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        array $data = []
    ) {
        $this->_reviewFactory           = $reviewFactory;
        $this->_ratingFactory           = $ratingFactory;
        $this->_storeManager            = $storeManager;
        $this->helper                   = $helper;
        $this->_getcron                 = $cron;
        $this->_getreviewedorder        = $getreviewedorder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelper              = $imageHelper;

        parent::__construct($context, $data);
    }

    // ── EXACT SAME as your original ──────────────────────────
    public function canshowpopup()
    {
        if ($this->helper->checkifcustomerloggedin()) {
        } else {
            return "Customer is not logged in";
        }

        $moduleStatus = $this->helper->getConfig('review/general/module');
        if ($moduleStatus == 1) {
        } else {
            return "module is not enabled";
        }

        return '1';
    }

    public function checkifcustomerloggedin()
    {
        return $this->helper->checkifcustomerloggedin();
    }

    public function getloggedincustomerid()
    {
        return $this->helper->getloggedincustomerid();
    }

    // ── EXACT SAME as your original ──────────────────────────
    public function setclicked($order_id, $clicked_value)
    {
        $cronobject = $this->_getcron->create()
            ->getCollection()
            ->addFieldToFilter('orderid', $order_id);
        foreach ($cronobject as $cronData) {
            $cronData->setData('clicked', $clicked_value);
            $cronData->save();
        }
    }

    // ── EXACT SAME as your original ──────────────────────────
    public function checkIfAlreadyShown($order_id, $params = null)
    {
        $reviewordercollection = $this->_getreviewedorder
            ->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $order_id);

        if (count($reviewordercollection) > 0) {
            return true;
        }

        $_getcroncollection = $this->_getcron
            ->create()
            ->getCollection()
            ->addFieldToFilter('orderid', $order_id);

        if ($params == 'check_clicked') {
            $_getcroncollection->addFieldToFilter('clicked', 'yes');
        }

        if (count($_getcroncollection) > 0) {
            return true;
        }

        return false;
    }

    // ── EXACT SAME as your original ──────────────────────────
    public function getOrderIdbyHash($hash)
    {
        $croncollection = $this->_getcron
            ->create()
            ->getCollection()
            ->addFieldToFilter('hashid', $hash);

        if (count($croncollection) > 0) {
            return $croncollection->getdata();
        }

        return false;
    }

    // ── EXACT SAME as your original ──────────────────────────
    public function getorders($customer_id)
    {
        if ($this->helper->checkifcustomerloggedin()) {
            $customerId = $this->helper->getloggedincustomerid();

            $to_days   = (int)$this->helper->getConfig('review/general/to_days_default_60');
            $from_days = (int)$this->helper->getConfig('review/general/from_days_default_30');

            if ($to_days == '') {
                $to_days = 60;
            }
            if ($from_days == '') {
                $from_days = 30;
            }

            $get_order_and_products = $this->helper->getorders(
                $customerId,
                -$to_days,
                -$from_days
            );

            if ($get_order_and_products == false) {
                return false;
            }

            return $get_order_and_products;
        }

        return false;
    }

    // ── NEW: replaces ObjectManager product loading in PHTML ─
    // public function getProductsByIds(array $prdIdArr)
    // {
    //     $products   = [];
    //     $collection = $this->productCollectionFactory->create()
    //         ->addAttributeToSelect(['name', 'image', 'thumbnail'])
    //         ->addFieldToFilter('entity_id', ['in' => $prdIdArr]);

    //     foreach ($collection as $product) {
    //         $imageUrl = $this->imageHelper
    //             ->init($product, 'product_thumbnail_image')
    //             ->getUrl();

    //         $products[$product->getId()] = [
    //             'id'    => $product->getId(),
    //             'name'  => $product->getName(),
    //             'image' => $imageUrl,
    //         ];
    //     }

    //     return $products;
    // }

    public function getProductsByIds(array $prdIdArr)
    {
        $products = [];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $imageHelper = $objectManager->get(\Magento\Catalog\Helper\Image::class);

        foreach ($prdIdArr as $productId) {
            try {
                // Load product by ID
                $product = $productRepository->getById($productId, false, 0); // storeId=0 (admin scope)

                // Image
                $imageUrl = $imageHelper
                    ->init($product, 'product_thumbnail_image')
                    ->getUrl();

                $products[$product->getId()] = [
                    'id'     => $product->getId(),
                    'name'   => $product->getName(),
                    'image'  => $imageUrl
                ];

            } catch (\Exception $e) {
                // skip invalid product
                continue;
            }
        }

        return $products;
    }

    // ── NEW: replaces ObjectManager storeManager in PHTML ────
    public function getStoreBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    // ── NEW: helper access in PHTML ──────────────────────────
    public function addInReviewfolder($order_id)
    {
        return $this->helper->addInReviewfolder($order_id);
    }
}