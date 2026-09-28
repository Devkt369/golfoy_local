<?php

namespace ArtM\ViPromotion\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENABLED = 'vipromotion/general/enabled';
    const XML_PATH_TARGET_CUSTOMER_GROUP = 'vipromotion/general/target_customer_group';
    const XML_PATH_LOGO_URL = 'vipromotion/logo/logo_url';
    const XML_PATH_INITIAL_IMAGE_URL = 'vipromotion/initial_image/initial_image_url';
    const XML_PATH_POPUP_ENABLED = 'vipromotion/popup/enabled';
    const XML_PATH_POPUP_TITLE = 'vipromotion/popup/title';
    const XML_PATH_POPUP_CONTENT = 'vipromotion/popup/content';
    const XML_PATH_BANNER_ENABLED = 'vipromotion/banner/enabled';
    const XML_PATH_BANNER_CONTENT = 'vipromotion/banner/content';

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Check if module is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get target customer group ID
     * @return int
     */
    public function getTargetCustomerGroup()
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TARGET_CUSTOMER_GROUP,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if current customer belongs to target group
     * @return bool
     */
    public function isCustomerInTargetGroup()
    {
        if (!$this->isEnabled() || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        $targetGroupId = $this->getTargetCustomerGroup();
        $customerGroupId = $this->customerSession->getCustomer()->getGroupId();

        // Use dummy group ID 5 if no target group is set
        $targetGroupId = $targetGroupId ?: 5;

        return $customerGroupId == $targetGroupId;
    }

    /**
     * Get custom logo URL
     * @return string|null
     */
    public function getCustomLogoUrl()
    {
        if (!$this->isCustomerInTargetGroup()) {
            return null;
        }

        return $this->scopeConfig->getValue(
            self::XML_PATH_LOGO_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get custom initial image URL
     * @return string|null
     */
    public function getCustomInitialImageUrl()
    {
        if (!$this->isCustomerInTargetGroup()) {
            return null;
        }

        return $this->scopeConfig->getValue(
            self::XML_PATH_INITIAL_IMAGE_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if popup should be shown
     * @return bool
     */
    public function shouldShowPopup()
    {
        if (!$this->isCustomerInTargetGroup()) {
            return false;
        }

        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_POPUP_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return false;
        }

        // Check if popup was already manually closed today
        $lastClosed = $this->sessionManager->getViPromotionPopupLastClosed();
        $today = date('Y-m-d');

        return $lastClosed !== $today;
    }

    /**
     * Mark popup as manually closed for today
     */
    public function markPopupAsShown()
    {
        $this->sessionManager->setViPromotionPopupLastClosed(date('Y-m-d'));
    }

    /**
     * Get popup title
     * @return string
     */
    public function getPopupTitle()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_POPUP_TITLE,
            ScopeInterface::SCOPE_STORE
        ) ?: 'Special Offer for You!';
    }

    /**
     * Get popup content
     * @return string
     */
    public function getPopupContent()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_POPUP_CONTENT,
            ScopeInterface::SCOPE_STORE
        ) ?: 'Welcome to our exclusive member benefits!';
    }

    /**
     * Check if banner should be shown
     * @return bool
     */
    public function shouldShowBanner()
    {
        if (!$this->isCustomerInTargetGroup()) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_BANNER_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get banner content
     * @return string
     */
    public function getBannerContent()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BANNER_CONTENT,
            ScopeInterface::SCOPE_STORE
        ) ?: 'You have exclusive member benefits available!';
    }
}