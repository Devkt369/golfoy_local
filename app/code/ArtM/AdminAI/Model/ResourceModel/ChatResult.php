<?php
namespace ArtM\AdminAI\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ChatResult extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_adminai_chat_results', 'result_id');
    }
}
