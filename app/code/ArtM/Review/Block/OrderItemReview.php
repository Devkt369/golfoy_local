<?php
namespace ArtM\Review\Block;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderItemReview extends Template
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Template\Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->productRepository = $productRepository;
        $this->urlBuilder = $context->getUrlBuilder();
        parent::__construct($context, $data);
    }

    /**
     * Get Submit Controller URL
     * * @return string
     */
    public function getSubmitActionUrl()
    {
        return $this->urlBuilder->getUrl('artm_review/submit/index');
    }

    /**
     * Prepare data structure expected by your custom snippet
     * * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    public function getReviewTemplateData($orderItem)
    {
        $order = $orderItem->getOrder();
        $productId = $orderItem->getProductId();
        
        $productsArray = [];
        try {
            $product = $this->productRepository->getById($productId);
            // Re-creating image mapping context
            $imageUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) 
                . 'catalog/product' . $product->getImage();
                
            $productsArray[(string)$productId] = [
                'name'  => $product->getName(),
                'image' => $imageUrl
            ];
        } catch (NoSuchEntityException $e) {
            // Product no longer exists in catalog
        }

        return [
            'prd_id_arr'  => [$productId],
            'products'    => $productsArray,
            'customer_id' => $order->getCustomerId() ? $order->getCustomerId() : '0',
            'order_id'    => $order->getIncrementId(),
            'starImgUrl'  => $this->getViewFileUrl('ArtM_Review::images/star.png') // Fallback path
        ];
    }

    /**
     * Check if the product in the template data has already been reviewed for this order
     * * @param array $templateData
     * @return bool
     */
    public function isProductReviewedForOrder(array $templateData)
    {
        // Extract variables from the passed template data array
        $orderId = $templateData['order_id'] ?? '';
        $currentProductIds = $templateData['prd_id_arr'] ?? [];

        if (empty($orderId) || empty($currentProductIds)) {
            return false;
        }

        // Load the collection filtered by order_id and review status
        $reviewCollection = $this->getreviewedorderFactory->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('review_given', 'Yes');

        foreach ($reviewCollection as $reviewRow) {
            $dbProductIdsString = $reviewRow->getProductIds(); // Example: "18889,19347,15399"
            
            if ($dbProductIdsString) {
                // Convert comma-separated string from DB into a clean array
                $reviewedProductIdsArray = explode(',', $dbProductIdsString);
                
                // Trim any accidental whitespaces just in case
                $reviewedProductIdsArray = array_map('trim', $reviewedProductIdsArray);

                // Check if any of our current product IDs exist in the reviewed array
                foreach ($currentProductIds as $currentId) {
                    if (in_array((string)$currentId, $reviewedProductIdsArray)) {
                        return true; // Review found!
                    }
                }
            }
        }

        return false; // No matching review found
    }

    /**
     * Get an associative array of [product_id => star_rating] already reviewed by this customer across ALL orders
     * Handles both legacy simple data lists and new id:rating pairs.
     *
     * @param int $customerId
     * @return array
     */
    public function getAlreadyReviewedProductIdsByCustomer($customerId)
    {
        $reviewedProductsMap = [];
        
        // Fallback safeguard if it's a guest checkout
        if (!$customerId) {
            return $reviewedProductsMap;
        }

        // Filter collection ONLY by customer identity and successful completion status
        $reviewCollection = $this->getreviewedorderFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('review_given', 'Yes');

        foreach ($reviewCollection as $reviewRow) {
            // 1. Process New Format (order_item_review column containing id:star pairs)
            $orderItemReviewField = $reviewRow->getOrderItemReview();
            if (!empty($orderItemReviewField)) {
                foreach (explode(',', $orderItemReviewField) as $pair) {
                    if (strpos($pair, ':') !== false) {
                        list($reviewedProductId, $savedStarRating) = explode(':', $pair);
                        
                        $pId = trim($reviewedProductId);
                        $star = (int)trim($savedStarRating);
                        
                        if (!empty($pId)) {
                            // Map the product ID to its exact rating value
                            $reviewedProductsMap[$pId] = $star;
                        }
                    }
                }
            }

            // 2. Fallback for Legacy Format (product_ids column without stars)
            // Only adds if the product hasn't already been mapped with a star rating above
            $legacyProductIdsField = $reviewRow->getProductIds();
            if (!empty($legacyProductIdsField)) {
                foreach (explode(',', $legacyProductIdsField) as $legacyId) {
                    $pId = trim($legacyId);
                    if (!empty($pId) && !isset($reviewedProductsMap[$pId])) {
                        // Default legacy records to 5 stars or null based on preference
                        $reviewedProductsMap[$pId] = 5; 
                    }
                }
            }
        }

        return $reviewedProductsMap;
    }
}