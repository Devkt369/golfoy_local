<?php
namespace ArtM\AdminAI\Block\Adminhtml;

use Magento\Backend\Block\Template;

class AllChats extends Template
{
    public function getAllSessionsUrl(): string
    {
        return $this->getUrl('adminai/allchats/sessions');
    }

    public function getMessagesUrl(): string
    {
        return $this->getUrl('adminai/chat/messages');
    }
}
