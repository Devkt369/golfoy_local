<?php
namespace ArtM\PartnersAuthentication\Controller\Index;

use Magento\Contact\Controller\Index\Post as MagentoPost;
use Magento\Framework\App\Action\Context;
use Magento\Contact\Model\ConfigInterface;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Customer\Model\Session as CustomerSession;
use ArtM\PartnersAuthentication\Model\ContactUs as ContactUsModel;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Post extends MagentoPost
{
    protected $logger;
    protected $contactUsModel;
    protected $customerSession;
    protected $storeManager;
    
    public function __construct(
        Context $context,
        ConfigInterface $contactsConfig,
        MailInterface $mail,
        DataPersistorInterface $dataPersistor,
        CustomerSession $customerSession,
        ContactUsModel $contactUsModel,
        StoreManagerInterface $storeManager,
        ?LoggerInterface $logger = null
    ) {
        $this->contactUsModel = $contactUsModel;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context, $contactsConfig, $mail, $dataPersistor);
    }

    public function execute()
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        if ($storeId !== 5) {
            return parent::execute();
        }

        $post = $this->getRequest()->getPostValue();

        if ($post) {
            if ($this->customerSession->isLoggedIn()) {
                $post['query_type'] = "Query";
            } else {
                $post['query_type'] = "Create Account";
            }

            $this->contactUsModel->setData($post);

            try {
                $this->contactUsModel->save();
                $this->messageManager->addSuccessMessage(
                    __('Your message has been received. We’ll get back to you soon.')
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Unable to send your message at this time.')
                );
                if ($this->logger) {
                    $this->logger->critical($e);
                }
            }
        }
        return parent::execute();
    }
}
