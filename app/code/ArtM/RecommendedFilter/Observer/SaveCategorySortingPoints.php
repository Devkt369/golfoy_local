<?php
namespace ArtM\RecommendedFilter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;

class SaveCategorySortingPoints implements ObserverInterface
{
    protected $request;
    protected $resource;
    protected $json;

    public function __construct(
        RequestInterface $request,
        ResourceConnection $resource,
        Json $json
    ) {
        $this->request = $request;
        $this->resource = $resource;
        $this->json = $json;
    }

    public function execute(Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        $categoryId = (int)$category->getId();

        if (!$categoryId) {
            return;
        }

        $raw = $this->request->getParam('category_sorting_points');

        if (!$raw) {
            return;
        }

        try {
            $sortingPoints = $this->json->unserialize($raw);
        } catch (\Exception $e) {
            return;
        }

        if (!is_array($sortingPoints) || empty($sortingPoints)) {
            return;
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_category_product');

        foreach ($sortingPoints as $productId => $points) {
            $connection->update(
                $table,
                ['sorting_points' => (int)$points],
                [
                    'category_id = ?' => $categoryId,
                    'product_id = ?' => (int)$productId
                ]
            );
        }
    }
}