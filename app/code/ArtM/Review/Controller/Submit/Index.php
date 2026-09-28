<?php
namespace ArtM\Review\Controller\Submit;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_reviewFactory;
    protected $_ratingFactory;
    protected $helper;
    protected $_storeManager;
    protected $_getcron;
    protected $_reviewMediaFactory;
    protected $customer;
    protected $productRepository;
    protected $resultJsonFactory;
    protected $_getreviewedorder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customer,
        \Magento\Review\Model\ReviewFactory $_reviewFactory,
        \Magento\Review\Model\RatingFactory $_ratingFactory,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \ArtM\Review\Model\GetreviewedorderFactory $_getreviewedorder,
        \ArtM\Review\Helper\Data $helper,
        \ArtM\Review\Model\CronFactory $_getcron,
        \RLTSquare\ProductReviewImages\Model\ReviewMediaFactory $_reviewMediaFactory
    ) {
        $this->_pageFactory        = $pageFactory;
        $this->_getreviewedorder   = $_getreviewedorder;
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->productRepository   = $productRepository;
        $this->customer            = $customer;
        $this->_reviewFactory      = $_reviewFactory;
        $this->_ratingFactory      = $_ratingFactory;
        $this->_storeManager       = $_storeManager;
        $this->helper              = $helper;
        $this->_getcron            = $_getcron;
        $this->_reviewMediaFactory = $_reviewMediaFactory;

        return parent::__construct($context);
    }

    public function get_rating($i)
    {
        if ($i == 1) {
            $comment = $this->helper->getConfig('review/general/comments_for_1_star');
        } else if ($i == 2) {
            $comment = $this->helper->getConfig('review/general/comments_for_2_star');
        } else if ($i == 3) {
            $comment = $this->helper->getConfig('review/general/comments_for_3_star');
        } else if ($i == 4) {
            $comment = $this->helper->getConfig('review/general/comments_for_4_star');
        } else if ($i == 5) {
            $comment = $this->helper->getConfig('review/general/comments_for_5_star');
        } else {
            return 'no comment';
        }

        $comment_arr   = explode(',', $comment);
        $comment_index = array_rand($comment_arr, 1);
        return $comment_arr[$comment_index];
    }

    public function execute()
    {
        $return_html = '';
        $result      = $this->resultJsonFactory->create();

        date_default_timezone_set("Asia/Kolkata");
        $date = date('Y-m-d H:i:s');

        $number_of_products = ($this->getRequest()->getParam('number_of_products') == '') ? ' ' : $this->getRequest()->getParam('number_of_products');
        $customer_id        = ($this->getRequest()->getParam('customer_id') == '')        ? ' ' : $this->getRequest()->getParam('customer_id');
        $submitted_data     = ($this->getRequest()->getParam('submitted_data') == '')     ? ' ' : $this->getRequest()->getParam('submitted_data');
        $order_id           = ($this->getRequest()->getParam('order_id') == '')           ? ' ' : $this->getRequest()->getParam('order_id');

        $continue = true;
        if ($this->helper->checkifcustomerloggedin()) {
            $customerId_from_session = $this->helper->getloggedincustomerid();
        }

        if ($number_of_products == '' || $customer_id == '' || $submitted_data == '' || $continue == false) {
            // missing params
        } else {

            $customer     = $this->customer->getById($customer_id);
            $customername = $customer->getFirstname();

            // collect product ids and order status for DB save
            $all_prd_ids   = [];
            $order_status  = '';

            // get order status from order
            $order_instance = $this->helper->getOrderByIncrementId($order_id);
            if ($order_instance) {
                $order_status = $order_instance->getState();
            }

            $submitted_data_arr = explode('<><><>', $submitted_data);

            foreach ($submitted_data_arr as $single_prd_data) {

                $single_prd_data_arr = explode('<><>', $single_prd_data);

                $star_rating    = $single_prd_data_arr[0];
                $product_id     = $single_prd_data_arr[1];
                $comment        = trim($single_prd_data_arr[2]);
                $uploadedImages = trim($single_prd_data_arr[3]);

                // collect product id
                $all_prd_ids[] = $product_id;

                $product      = $this->productRepository->getById($product_id);
                $product_name = $product->getName();

                $reviewFinalData['ratings'][1] = $star_rating;
                $reviewFinalData['ratings'][2] = $star_rating;
                $reviewFinalData['ratings'][3] = $star_rating;
                $reviewFinalData['nickname']   = $customername;
                $reviewFinalData['title']      = "Reviewing " . $product_name;
                $reviewFinalData['detail']     = $comment;

                $review = $this->_reviewFactory->create()->setData($reviewFinalData);
                $review->unsetData('review_id');
                $review->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE))
                    ->setEntityPkValue($product_id)
                    ->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING)
                    ->setStoreId($this->_storeManager->getStore()->getId())
                    ->setStores([$this->_storeManager->getStore()->getId()])
                    ->setCustomerId($customer_id)
                    ->save();

                foreach ($reviewFinalData['ratings'] as $ratingId => $optionId) {
                    $this->_ratingFactory->create()
                        ->setRatingId($ratingId)
                        ->setReviewId($review->getId())
                        ->addOptionVote($optionId, $product_id);
                }

                $review->aggregate();
                $return_html = 'review_submitted';

                // insert uploaded images
                if ($uploadedImages != '|empty|') {
                    $imgdata = explode(',', $uploadedImages);
                    foreach ($imgdata as $v) {
                        $reviewMedia = $this->_reviewMediaFactory->create();
                        $reviewMedia->setMediaUrl($v);
                        $reviewMedia->setReviewId($review->getId());
                        $reviewMedia->save();
                    }
                }
            }

            // ── UPDATE review_given — ONCE after all products ────
            $this->markOrderAsReviewed(
                $order_id,
                $date,
                $customer_id,
                implode(',', $all_prd_ids), // all product ids
                $order_status
            );

            // ── UPDATE cron table ────────────────────────────────
            $this->markCronAsReviewed($order_id);
        }

        return $result->setData(['html' => $return_html]);
    }

    /**
     * Mark order as reviewed in artm_review_module
     * Handles both cases:
     * 1. Record exists (cron saved it) → update review_given = 1
     * 2. Record does not exist → create new record
     */
    private function markOrderAsReviewed($order_id, $date, $customer_id, $prd_ids, $order_status)
    {
        $getreviewedorder_model = $this->_getreviewedorder->create();
        $review_collection      = $getreviewedorder_model
            ->getCollection()
            ->addFieldToFilter('order_id', $order_id);

        if (count($review_collection) > 0) {
            // record exists — just update review_given
            foreach ($review_collection as $item) {
                $item->setReviewGiven('Yes');
                $item->save();
            }
        } else {
            // record does not exist — create with all fields
            $getreviewedorder_model->addData([
                'order_id'     => $order_id,
                'customer_id'  => $customer_id,
                'order_status' => $order_status,
                'product_ids'  => $prd_ids,
                'review_given' => 'Yes',
                'updated_at'   => $date,
            ]);
            $getreviewedorder_model->save();
        }
    }

    /**
     * Mark order as reviewed in cron table
     */
    private function markCronAsReviewed($order_id)
    {
        $getcron_model       = $this->_getcron->create();
        $cron_collection     = $getcron_model
            ->getCollection()
            ->addFieldToFilter('orderid', $order_id);

        if (count($cron_collection) > 0) {
            foreach ($cron_collection as $item) {
                $item->setReviewed('yes');
                $item->save();
            }
        }
    }
}