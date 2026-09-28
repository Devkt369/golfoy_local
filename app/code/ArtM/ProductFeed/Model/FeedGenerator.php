<?php
namespace ArtM\ProductFeed\Model;

use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class FeedGenerator
{
    protected $reviewCollectionFactory;
    protected $voteCollectionFactory;
    protected $productRepository;
    protected $storeManager;
    protected $directoryList;
    protected $filesystem;

    public function __construct(
        ReviewCollectionFactory $reviewCollectionFactory,
        VoteCollectionFactory $voteCollectionFactory,
        ProductRepository $productRepository,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        Filesystem $filesystem
    ) {
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->voteCollectionFactory = $voteCollectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
    }

    public function generateFeed()
    {
        $collection = $this->reviewCollectionFactory->create();
        $collection->addStatusFilter(1); // Approved reviews only
        $collection->addStoreFilter($this->storeManager->getStore()->getId());
        // $collection->setPageSize(5500);

        $feed = new \SimpleXMLElement('<feed xmlns:vc="http://www.w3.org/2007/XMLSchema-versioning" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.google.com/shopping/reviews/schema/product/2.4/product_reviews.xsd"/>');
        $feed->addChild('version', '2.4');

        $publisher = $feed->addChild('publisher');
        $publisher->addChild('name', 'Golfoy');
        $publisher->addChild('favicon', 'https://staging-golfoy.hyperx.cloud/media/favicon/stores/1/Golfoy-favicon_1.png');

        $reviews = $feed->addChild('reviews');

        foreach ($collection as $review) {
            try {
                $product = $this->productRepository->getById($review->getEntityPkValue());
            } catch (\Exception $e) {
                continue;
            }

            $reviewNode = $reviews->addChild('review');
            $reviewNode->addChild('review_id', $review->getReviewId());

            // Reviewer
            $reviewer = $reviewNode->addChild('reviewer');
            $reviewer->addChild('name', htmlspecialchars($review->getNickname() ?? ''));
            $reviewer->addChild('reviewer_id', htmlspecialchars($review->getCustomerId() ?? ''));

            // Review details
            $reviewNode->addChild('review_timestamp', date('c', strtotime($review->getCreatedAt())));
            $reviewNode->addChild('title', htmlspecialchars($review->getTitle() ?? ''));
            $reviewNode->addChild(
                'content',
                htmlspecialchars(
                    $review->getDetail() === "|empty|" ? "no review" : ($review->getDetail() ?? ''),
                    ENT_QUOTES | ENT_XML1,
                    'UTF-8'
                )
            );
            $reviewNode->addChild('review_language', 'en');
            $reviewNode->addChild('review_country', 'IND');
            $reviewUrlNode = $reviewNode->addChild('review_url', htmlspecialchars($product->getProductUrl() . '#reviews'));
            $reviewUrlNode->addAttribute('type', 'singleton');

            $votes = $this->voteCollectionFactory->create()
                ->setReviewFilter($review->getReviewId())
                ->addOptionInfo()
                ->load();

            $ratingValue = 0;
            $ratingCount = 0;

            foreach ($votes as $vote) {
                $ratingValue += $vote->getPercent(); 
                $ratingCount++;
            }

            $ratingAvg = 0;
            if ($ratingCount > 0) {
                $ratingAvg = round(($ratingValue / $ratingCount) / 20, 1); 
            }

            $ratings = $reviewNode->addChild('ratings');
            $overall = $ratings->addChild('overall', $ratingAvg);
            $overall->addAttribute('min', '1');
            $overall->addAttribute('max', '5');

            // Product info
            $productsNode = $reviewNode->addChild('products');
            $productNode = $productsNode->addChild('product');
            $productIds = $productNode->addChild('product_ids');

            $productIds->addChild('skus')->addChild('sku', htmlspecialchars($product->getSku()));
            if ($product->getData('gtin')) {
                $productIds->addChild('gtins')->addChild('gtin', htmlspecialchars($product->getData('gtin')));
            }
            if ($product->getAttributeText('brand')) {
                $productIds->addChild('brands')->addChild('brand', htmlspecialchars($product->getAttributeText('brand')));
            }

            $productNode->addChild('product_name', htmlspecialchars($product->getName()));
            $productNode->addChild('product_url', htmlspecialchars($product->getProductUrl()));

            $reviewNode->addChild('collection_method', 'post_fulfillment');
            echo ".";
        }

        // Save XML
        $fileName = 'google_reviews_feed.xml';
        $filePath = $this->directoryList->getPath('var') . '/productreview/' . $fileName;

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        $feed->asXML($filePath);

        $mediaFilePath = $this->directoryList->getPath('media') . '/productreview/' . $fileName;
        if (!is_dir(dirname($mediaFilePath))) {
            mkdir(dirname($mediaFilePath), 0777, true);
        }
        $feed->asXML($mediaFilePath);

        return $filePath;
    }

    public function getFeedFile($fileName)
    {
        $filePath = $this->directoryList->getPath('var') . '/productreview/' . $fileName;
        if (file_exists($filePath)) {
            return file_get_contents($filePath); 
        }

        return null; 
    }
}
