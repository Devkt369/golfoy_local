<?php
/**
 * Copyright © 2024 ArtM. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ArtM\QueryChatBox\Block\ChatBox;

use Magento\Customer\Model\Session;
use ArtM\QueryChatBox\Model\QueryChatBoxFactory;
use ArtM\QueryChatBox\Model\Source\Config\ExpertList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

/**
 * Class ChatBox
 * @package ArtM\QueryChatBox\Block
 */
class ChatBox extends \Magento\Framework\View\Element\Template
{
    protected $scopeConfig;
    /**
     * Session variable
     *
     * @var Session
     */
    protected $customer;

    /**
     * query model
     *
     * @var QueryChatBoxFactory
     */
    protected $queryChatBoxFactory;

    /**
     * Get details of customer
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param Session $customer
     * @param QueryChatBoxFactory $queryChatBoxFactory
     * @param ExpertList $expertList
     * @param AssetRepository $assetRepo
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Session $customer,
        QueryChatBoxFactory $queryChatBoxFactory,
        ExpertList $expertList,
        AssetRepository $assetRepo,
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->customer = $customer;
        $this->expertList = $expertList;
        $this->assetRepo = $assetRepo;
        $this->scopeConfig = $scopeConfig;
        $this->_queryChatBoxFactory = $queryChatBoxFactory;
        parent::__construct($context);
    }

    /**
     * Get custom details
     *
     * @return mixed
     */
    public function getCustomerDetails(){
        $customerDetails = [];
        $customerSession = $this->customer;
        if($customerSession->isLoggedIn()) {
            $customer = $customerSession->getCustomer();
            $customerDetails[] = $customerSession->getCustomer()->getEmail();
            $customerDetails[] = $customerSession->getCustomer()->getName();
            $customerDetails[] = $customerSession->getCustomer()->getPhoneNumber();
            $customerDetails[] = $customerSession->getCustomer()->getId();
            return $customerDetails;
        }else{
            return false;
        }
    }

    public function getQueryTypeConfig($configPath)
    {
        return $this->scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get logged in customer email
     *
     * @return mixed
     */
    public function getCustomerEmail(){
        $customerSession = $this->customer;
        if($customerSession->isLoggedIn()) {
            $customer = $customerSession->getCustomer();
            return $customerSession->getCustomer()->getEmail();
        }else{
            return false;
        }
    }

    /**
     * Query collection get
     *
     * @param string $cEmail
     * @return Collection
     */
    public function getQueryList(){
        $cEmail = $this->getCustomerEmail();
        $queryChatBox = $this->_queryChatBoxFactory->create();
        $collection = $queryChatBox->getCollection()
            ->addFieldToFilter('customer_email', ['eq' => $cEmail])
            ->setOrder('id', 'DESC'); // Sorting by id in descending order
        return $collection->getData();        
    }

    public function isBlockUser($email){
        try{
            $cEmail = $this->getCustomerEmail();
            $queryChatBox = $this->_queryChatBoxFactory->create();
            $collection = $queryChatBox->getCollection()
                ->addFieldToFilter('customer_email', ['eq' => $email])
                ->setOrder('id', 'DESC');
            foreach($collection->getData() as $blockStatus){
                if($blockStatus['blk_unblk_user'] == 1){
                    return true;
                }
            }
            return false;
        } catch(\Exception){
            return false;
        }
    }

    public function getQueryData($id)
    {
        $queryModel = $this->_queryChatBoxFactory->create()->load($id);
        return $queryModel->getData();
    }

    public function getExpertOptions()
    {
        return $this->expertList->getAllOptions();
    }

    /**
     * GET image URL
     *
     * @param string $imagePath
     * @return string
     */
    public function getImageUrl($imagePath)
    {
        return $this->assetRepo->getUrl("ArtM_QueryChatBox::images/{$imagePath}");
    }
}