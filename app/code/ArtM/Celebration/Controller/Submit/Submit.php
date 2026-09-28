<?php

namespace ArtM\Celebration\Controller\Submit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\Celebration\Model\CelebrationFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Escaper;

class Submit extends Action
{
    protected $resultPageFactory;

    /**
     * @var Customer
     */
    protected $customerModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * constructor.
     * @param StoreManagerInterface $storeManager
     */


    /**
     * @var Session
     */
    protected $customerSession;

    public function __construct(
        Context $context,
        Customer $customerModel,
        PageFactory $resultPageFactory,
        Session $customerSession,
        JsonFactory $jsonResultFactory,
        StoreManagerInterface $storeManager,
        Escaper $escaper,
        CelebrationFactory $CelebrationFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->customerModel = $customerModel;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->CelebrationFactory = $CelebrationFactory;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    public function execute()
    {
        $uniqueId = time();
        $CelebrationFactoryModel = $this->CelebrationFactory->create();
        $CelebrationFactoryModel->addData([
            "to" => strip_tags($this->getRequest()->getParam('to')),
            "from" => strip_tags($this->getRequest()->getParam('from')),
            "param" => $uniqueId
        ]);
        $CelebrationFactoryModel->save();
        $resultJson = $this->jsonResultFactory->create();
        return $resultJson->setData(['success' => $uniqueId]);
    }


}