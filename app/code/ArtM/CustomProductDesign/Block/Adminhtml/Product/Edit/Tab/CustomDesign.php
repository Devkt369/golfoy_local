<?php
namespace ArtM\CustomProductDesign\Block\Adminhtml\Product\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use \Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;


class CustomDesign extends Template
{
    protected $_template = 'ArtM_CustomProductDesign::product/edit/tab/custom_design.phtml';
    protected $registry;
    protected $storeManager;
    protected $resource;

    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->resource = $resource;
        parent::__construct($context, $data);
    }
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Returns array: [image_file => area_count]
     */
    public function getCustomPrintAreasByImage($productId)
    {
        $result = [];
        if (!$productId) return $result;
        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_customprint_area');
        $select = $connection->select()
            ->from($table, ['image', 'cnt' => 'COUNT(*)'])
            ->where('product_id = ?', $productId)
            ->group('image');
        $rows = $connection->fetchAll($select);
        foreach ($rows as $row) {
            $result[$row['image']] = (int)$row['cnt'];
        }
        return $result;
    }
}
