<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\POSConnect\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var PageFactory
     */
    private $resultPageFactory;   
    private $cookieManager;
    private $cookieMetadataFactory;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;  
        $this->messageManager = $messageManager;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    
    public function execute()
    {   
        
       $page = $this->resultPageFactory->create();
       $block = $page->getLayout()->getBlock('POSConnect.index');
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDuration(1800);
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setHttpOnly(true);
        
        $posCookie = $this->cookieManager->getCookie('posconnect');
        if($posCookie && $posCookie == 'valid'){
            $block->setData('toshow', 'data');
        }    
        if(isset($_POST['pospin'])){
            if($_POST['pospin'] == 'g0lf0y'){
                $this->cookieManager->setPublicCookie(
                    'posconnect',
                    'valid',
                    $publicCookieMetadata
                ); 
                $block->setData('toshow', 'data');
            }
        }  
        return $page;
    }
}

