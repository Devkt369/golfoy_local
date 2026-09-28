<?php
namespace ArtM\QueryChatBox\Controller\ChatBox;

use Magento\Framework\View\Result\Page;
use Magento\Customer\Controller\Account\Index as CustomerAccountIndex;

/**
 * Class Index
 * @package Mageplaza\RewardPoints\Controller\Customer
 */
class Index extends CustomerAccountIndex
{
    /**
     * @return Page
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Your Query'));

        return $resultPage;
    }
}
