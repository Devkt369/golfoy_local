<?php
namespace ArtM\ReservedProducts\Model;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class YourDataCollection extends AbstractCollection
{
    protected $_idFieldName = 'reservation_id';

    protected $_eventPrefix = 'your_module_data_collection';

    protected function _construct()
    {
        $this->_init(\ArtM\ReservedProducts\Model\ReservedProducts::class, \ArtM\ReservedProducts\Model\ResourceModel\ReservedProducts::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();
    }

    public function load($printQuery = false, $logQuery = false)
    {
        parent::load($printQuery, $logQuery);

        $skuFilter = $this->request->getParam('filters')['sku'];
        if ($skuFilter) {
            $this->addFieldToFilter('sku', ['like' => '%' . $skuFilter . '%']);
        }

        return $this;
    }
}