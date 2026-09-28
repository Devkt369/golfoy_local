<?php
namespace ArtM\AdminAI\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ChatSession extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_adminai_chat_session', 'session_id');
    }
}
