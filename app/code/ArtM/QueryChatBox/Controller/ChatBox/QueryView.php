<?php
namespace ArtM\QueryChatBox\Controller\ChatBox;

use Magento\Framework\View\Result\Page;
use ArtM\QueryChatBox\Controller\ChatBox\Index as CustomerAccountIndex;

/**
 * Class Index
 * @package Mageplaza\RewardPoints\Controller\Customer
 */
class QueryView extends CustomerAccountIndex
{
    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Your Query View'));
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('custom-tab');
        }
        return $resultPage;
    }
}
