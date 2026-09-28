<?php

declare(strict_types=1);

namespace Magestore\Core\Model\DataProvider;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\State;
use Magento\Framework\Registry;
use Magestore\Core\Api\Data\CurrentUserInterface;
use Magestore\Core\Api\Data\CurrentUserInterfaceFactory;
use Magestore\Appadmin\Api\Data\Staff\StaffInterface;
use Magestore\Core\Api\GetCurrentUserInterface;

/**
 * Provider get current user
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GetCurrentUser implements GetCurrentUserInterface
{
    /**
     * @var AuthSession
     */
    private $authSession;

    /**
     * @var State
     */
    private $state;

    /**
     * @var CurrentUserInterfaceFactory
     */
    private $currentUserFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CurrentUserInterface|null
     */
    private $currentUser = null;

    /**
     * GetCurrentUser constructor.
     *
     * @param AuthSession $authSession
     * @param State $state
     * @param CurrentUserInterfaceFactory $currentUserFactory
     * @param CustomerSession $customerSession
     * @param Registry $coreRegistry
     */
    public function __construct(
        AuthSession $authSession,
        State $state,
        CurrentUserInterfaceFactory $currentUserFactory,
        CustomerSession $customerSession,
        Registry $coreRegistry
    ) {
        $this->authSession = $authSession;
        $this->state = $state;
        $this->currentUserFactory = $currentUserFactory;
        $this->customerSession = $customerSession;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @inheritDoc
     */
    public function setCurrentUser(CurrentUserInterface $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentUser()
    {
        if (!$this->currentUser) {
            $areaCode = $this->state->getAreaCode();
            if ($areaCode == 'adminhtml') {
                $this->getAdminUser();
            } elseif ($areaCode == 'webapi_rest') {
                $this->getRestApiUser();
            } elseif ($areaCode == 'webapi_soap') {
                // Do something
                $this->currentUser = null;
            } elseif ($areaCode == 'frontend') {
                $this->getFrontendUser();
            } else {
                $this->currentUser = null;
            }
        }
        return $this->currentUser;
    }

    /**
     * Get Admin User
     */
    public function getAdminUser()
    {
        $adminUser = $this->authSession->getUser();
        if ($adminUser && $adminUser->getId()) {
            /** @var CurrentUserInterface $currentUser */
            $currentUser = $this->currentUserFactory->create();
            $currentUser->setUserType('admin');
            $currentUser->setUserId((int)$adminUser->getId());
            $currentUser->setUserName($adminUser->getUserName());
            $this->currentUser = $currentUser;
        } else {
            $this->currentUser = null;
        }
    }

    /**
     * Get Rest Api User
     */
    public function getRestApiUser()
    {
        /** @var StaffInterface $currentStaff */
        $currentStaff = $this->coreRegistry->registry('currrent_webpos_staff');
        if ($currentStaff && $currentStaff->getStaffId()) {
            /** @var CurrentUserInterface $currentUser */
            $currentUser = $this->currentUserFactory->create();
            $currentUser->setUserType('pos_staff');
            $currentUser->setUserId((int)$currentStaff->getStaffId());
            $currentUser->setUserName($currentStaff->getUsername());
            $this->currentUser = $currentUser;
        } else {
            $this->currentUser = null;
        }
    }

    /**
     * Get Frontend User
     */
    public function getFrontendUser()
    {
        $customer = $this->customerSession->getCustomer();
        if ($customer && $customer->getId()) {
            $currentUser = $this->currentUserFactory->create();
            $currentUser->setUserType('customer');
            $currentUser->setUserId((int)$customer->getId());
            $currentUser->setUserName($customer->getEmail());
            $this->currentUser = $currentUser;
        } else {
            $this->currentUser = null;
        }
    }
}
