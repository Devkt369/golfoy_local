<?php

namespace ArtM\PartnersAuthentication\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use ArtM\PartnersAuthentication\Model\ContactUsFactory;


class MassApprove extends Action implements HttpPostActionInterface
{

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ContactUsFactory
     */
    protected $contactUsFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        ResultFactory $resultFactory,
        ContactUsFactory $contactUsFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->resultFactory = $resultFactory;
        $this->contactUsFactory = $contactUsFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->contactUsFactory->create());
        
        foreach ($collection as $dealerAccount) {
            // Logic to approve the dealer account
            // This could involve updating a status field, sending notifications, etc.
            $dealerAccount->setStatus('approved'); // Example status update
            $dealerAccount->save(); // Save the changes

            
        }
        $this->messageManager->addSuccessMessage(__('%1 Dealer Account(s) have been approved successfully.', $collection->getSize()));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('msipriority/order/pending');
    }
}
