<?php

namespace ArtM\RecommendedSort\Controller\Adminhtml\Background;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Review\Model\ReviewFactory;
use Magento\Review\Model\RatingFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class GetProductData extends Action
{
    protected $resultJsonFactory;
    protected $productRepository;
    protected $imageHelper;
    protected $priceHelper;
    protected $reviewFactory;
    protected $configurable;
    protected $sourceItemsBySku;
    protected $storeManager;
    protected $ratingFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        ImageHelper $imageHelper,
        PriceHelper $priceHelper,
        ReviewFactory $reviewFactory,
        Configurable $configurable,
        GetSourceItemsBySkuInterface $sourceItemsBySku,
        StoreManagerInterface $storeManager,
        RatingFactory $ratingFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->imageHelper = $imageHelper;
        $this->priceHelper = $priceHelper;
        $this->reviewFactory = $reviewFactory;
        $this->configurable = $configurable;
        $this->sourceItemsBySku = $sourceItemsBySku;
        $this->storeManager = $storeManager;
        $this->ratingFactory = $ratingFactory;
    }

    public function execute()
    {
        $productId = (int) $this->getRequest()->getParam('product_id');
        $resultJson = $this->resultJsonFactory->create();

        try {
            $hasChild = false;
            $product = $this->productRepository->getById($productId);
            $isfeatured = $product->getData('isfeatured');
            $store = $this->storeManager->getStore();

            $imageUrl = $product->getThumbnail();//$this->imageHelper->init($product, 'product_base_image')->getUrl();
            $priceHtml = $this->priceHelper->currency($product->getData('special_price'), true, false);

            $regularPrice = $this->priceHelper->currency($product->getData('price'), true, false);
            $finalPrice = $product->getData('special_price');
            $percentOff = ($product->getData('price') > $finalPrice)
                ? round(100 - ($finalPrice / $product->getData('price')) * 100)
                : 0;

            $ratingSummary = $this->ratingFactory->create()->getEntitySummary($product->getId());
            $total = $ratingSummary->getSum();
            
            $ratingHtml = '';

            if ($total != '') {
                $outof =  $ratingSummary->getCount() * 100;
                $ratingpercent = ($total * 100) / $outof;
                $ratingAvg = $ratingpercent / 20;
                $ratingf = round($ratingAvg, 1);
                $ratingShow =  $ratingf . "/" . "5";
                $numberOfRatings = $ratingSummary->getCount();

                $ratingHtml = '<div class="customratinghtml star_back"><span style="font-size: 20px;color: #F0C808;">&starf;</span> ' . $ratingShow .   '<div class="people_rate">' . $numberOfRatings . ' <span class="total_rev">' . ($numberOfRatings == 1 ? 'Review' : ($numberOfRatings == 0 ? '' : 'Reviews')) . '</span>' . '</div>' . "</div>";
            }else{
                $ratingHtml = '';
            }

            $sources = [];
            if ($product->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {

                $sourceItems = $this->sourceItemsBySku->execute($product->getSku());
                foreach ($sourceItems as $item) {
                    $sources[] = [
                        'source_code' => $item->getSourceCode(),
                        'qty' => (float) $item->getQuantity()
                    ];
                }
                
                usort($sources, function ($a, $b) {
                    $priority = ['G5', 'CGC'];
                    $posA = array_search($a['source_code'], $priority);
                    $posB = array_search($b['source_code'], $priority);
                    return ($posA === false ? 999 : $posA) - ($posB === false ? 999 : $posB);
                });
            }

            $data = [
                'product_id' => $product->getId(),
                'type' => $product->getTypeId(),
                'name' => $product->getName(),
                'image' => '/media/catalog/product'.$imageUrl,
                'rating_html' => $ratingHtml,
                'regular_price' => $regularPrice,
                'price_html' => $priceHtml,
                'percent_off' => $percentOff,
                'sources' => $sources,
                'row_price' => $product->getData('price'), 
                'row_final_price' => $product->getData('final_price'),
                'created_at' => date('M d Y H:i:s', strtotime($product->getCreatedAt())),
                'updated_at' => date('M d Y H:i:s', strtotime($product->getUpdatedAt())),
                'isfeatured' => $isfeatured,
            ];

            // If configurable, load child products
            if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $hasChild = true;
                $childProductIds = $this->configurable->getChildrenIds($product->getId())[0] ?? [];
                $children = [];

                foreach ($childProductIds as $childId) {
                    $child = $this->productRepository->getById($childId);
                    if ((int)$child->getStatus() === ProductStatus::STATUS_DISABLED) {
                        continue;
                    }

                    $childImage = '';//$this->imageHelper->init($child, 'product_base_image')->getUrl();
                    $childPriceHtml = $this->priceHelper->currency($child->getFinalPrice(), true, false);
                    $childRegular = $this->priceHelper->currency($child->getPrice(), true, false);
                    $childFinal = $child->getFinalPrice();
                    $childOff = ($child->getPrice() > $childFinal)
                        ? round(100 - ($childFinal / $child->getPrice()) * 100)
                        : 0;

                    // Source-wise stock
                    $sourceItems = $this->sourceItemsBySku->execute($child->getSku());
                    $sources = [];
                    foreach ($sourceItems as $item) {
                        $sources[] = [
                            'source_code' => $item->getSourceCode(),
                            'qty' => (float) $item->getQuantity()
                        ];
                    }

                    // Prioritize G5, CGC
                    // usort($sources, function ($a, $b) {
                    //     $priority = ['G5', 'CGC'];
                    //     $posA = array_search($a['source_code'], $priority);
                    //     $posB = array_search($b['source_code'], $priority);
                    //     return ($posA === false ? 999 : $posA) - ($posB === false ? 999 : $posB);
                    // });

                    $children[] = [
                        'sku' => $child->getSku(),
                        'name' => $child->getName(),
                        'image' => $childImage,
                        'regular_price' => $childRegular,
                        'price_html' => $childPriceHtml,
                        'percent_off' => $childOff,
                        'sources' => $sources
                    ];
                }

                $data['children'] = $children;
            }
            // echo '<pre>';
            // print_r($data);
            if($data['row_price'] == 0 && $hasChild) {
                $data['regular_price'] = $childRegular;
                $data['percent_off'] = $childOff;
            }
            if($data['row_final_price'] == 0 && $hasChild) {
                $data['price_html'] = $childPriceHtml;
                $data['percent_off'] = $childOff;
            }

            return $resultJson->setData([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    
}
