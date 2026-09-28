<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Slider extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('artm_product_slider', 'slider_id');
    }

    protected function _beforeSave(AbstractModel $object): self
    {
        parent::_beforeSave($object);

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['slider_id'])
            ->where('identifier = ?', (string)$object->getData('identifier'));

        if ($object->getId()) {
            $select->where('slider_id <> ?', (int)$object->getId());
        }

        if ($connection->fetchOne($select)) {
            throw new LocalizedException(__('A slider with the same identifier already exists.'));
        }

        return $this;
    }
}
