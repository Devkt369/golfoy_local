<?php
namespace ArtM\PartnersAuthentication\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\PartnersAuthentication\Model\ContactUsFactory;
use ArtM\SSO\Helper\Data as SSOHelper;

class Approve extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ContactUsFactory
     */
    protected $contactUsFactory;

    /**
     * @var SSOHelper
     */
    protected $ssoHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ContactUsFactory $contactUsFactory,
        SSOHelper $ssoHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->contactUsFactory = $contactUsFactory;
        $this->ssoHelper = $ssoHelper;
    }

    public function execute()
    {
        // 1. Decode combined data field
        $data = $this->getRequest()->getParam('data');
        list($id, $email, $name, $telephone, $queryType) = explode('||', $data);

        // 2. Validate type of request
        if ($queryType == "Query") {
            $this->messageManager->addWarningMessage(__("This request is not for account creation."));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        // 3. Check if email or phone already exist
        $telephoneFound = $this->ssoHelper->isAvailableMobileNumber($telephone);
        $emailFound     = $this->ssoHelper->isEmailRegistered($email);

        if ($telephoneFound || $emailFound) {
            $message = '';
            if ($telephoneFound) {
                $message .= __('The telephone number is already in use. ');
            }
            if ($emailFound) {
                $message .= __('The email address is already in use. ');
            }
            $this->messageManager->addErrorMessage($message);
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        // 4. Create account
        $password = 'golf@123';

        $postData = [
            'email'    => $email,
            'name'     => $name,
            'phone'    => $telephone,
            'password' => $password
        ];

        if ($this->ssoHelper->createAccount($postData, 5)) {

            $contactUs = $this->contactUsFactory->create()->load($id);
            $contactUs->setData('has_account', true)->save();
            $this->messageManager->addSuccessMessage(__("Dealer account created successfully."));

        } else {
            $this->messageManager->addErrorMessage(__("Error occurred while creating dealer account."));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}