<?php

namespace ArtM\VinGetInvoice\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use ArtM\VinGetInvoice\Api\Data\VinInvoiceItemInterface;

class VinItem extends AbstractExtensibleObject implements VinInvoiceItemInterface
{
    public function getId()
    {
        return $this->_get('id');
    }

    public function setId($id)
    {
        return $this->setData('id', $id);
    }

    public function getQty()
    {
        return $this->_get('qty');
    }

    public function setQty($qty)
    {
        return $this->setData('qty', $qty);
    }
}
