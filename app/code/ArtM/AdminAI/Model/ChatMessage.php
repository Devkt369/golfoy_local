<?php
namespace ArtM\AdminAI\Model;

use Magento\Framework\Model\AbstractModel;

class ChatMessage extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\AdminAI\Model\ResourceModel\ChatMessage::class);
    }
}
