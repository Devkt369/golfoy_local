<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\InventoryMovement\Model\Flag;
use Magestore\InventoryMovement\Model\FlagFactory;
use Magestore\InventoryMovement\Model\Source\Adminhtml\CheckOrdersSince;

/**
 * Class Data
 *
 * Use for helper data
 */
class Data extends AbstractHelper
{
    const CHECK_ORDERS_SINCE_CONFIG_XML_PATH = 'inventory_movement/orders_potential_error/check_orders_since';
    const ENABLE_ADMIN_NOTIFICATION_CONFIG_XML_PATH =
        'inventory_movement/orders_potential_error/enable_admin_notification';
    const ENABLE_EMAIL_NOTIFICATION_CONFIG_XML_PATH =
        'inventory_movement/orders_potential_error/enable_email_notification';
    const RECIPIENT_EMAIL_CONFIG_XML_PATH = 'inventory_movement/orders_potential_error/recipient_email';
    const ADDITIONAL_EMAILS_CONFIG_XML_PATH = 'inventory_movement/orders_potential_error/additional_emails';

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var FlagFactory
     */
    protected $flagFactory;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ResolverInterface $localeResolver
     * @param TimezoneInterface $timezone
     * @param FlagFactory $flagFactory
     */
    public function __construct(
        Context $context,
        ResolverInterface $localeResolver,
        TimezoneInterface $timezone,
        FlagFactory $flagFactory
    ) {
        parent::__construct($context);
        $this->localeResolver = $localeResolver;
        $this->localeDate = $timezone;
        $this->flagFactory = $flagFactory;
    }

    /**
     * Get Config Check Order Since
     *
     * @return mixed
     */
    public function getConfigCheckOrderSince()
    {
        return $this->scopeConfig->getValue(self::CHECK_ORDERS_SINCE_CONFIG_XML_PATH);
    }

    /**
     * Get day from value to check orders
     *
     * @return \DateTime
     */
    public function getDayFrom()
    {
        $config = $this->scopeConfig->getValue(self::CHECK_ORDERS_SINCE_CONFIG_XML_PATH);
        $this->localeResolver->emulate(0);
        $currentDate = $this->localeDate->date();
        $date = $currentDate;

        switch ($config) {
            case CheckOrdersSince::SINCE_24H:
                $date = $currentDate->sub(new \DateInterval('PT24H'));
                break;
            case CheckOrdersSince::SINCE_7DAYS:
                $date = $currentDate->sub(new \DateInterval('P7D'));
                break;
            case CheckOrdersSince::SINCE_30DAYS:
                $date = $currentDate->sub(new \DateInterval('P30D'));
                break;
            case CheckOrdersSince::SINCE_2MTD:
                $date = $currentDate->sub(new \DateInterval('P2M'));
                break;
            case CheckOrdersSince::SINCE_6MTD:
                $date = $currentDate->sub(new \DateInterval('P6M'));
                break;
            default:
                break;
        }
        $this->localeResolver->revert();
        return $date;
    }

    /**
     * Get Last Execution Time
     *
     * @return string|null
     */
    public function getLastExecutionTime()
    {
        /** @var Flag $flag */
        $flag = $this->flagFactory->create();
        try {
            $updatedAt = $flag->getOrdersPotentialErrorUpdatedAt();
            $flagData = $flag->getFlagData();
            /* If config check orders since config was changed, return null to recollect all report data */
            $oldConfig = isset($flagData[self::CHECK_ORDERS_SINCE_CONFIG_XML_PATH]) ?
                $flagData[self::CHECK_ORDERS_SINCE_CONFIG_XML_PATH]
                : null;
            if ($oldConfig && $oldConfig != $this->getConfigCheckOrderSince()) {
                $updatedAt = null;
            }
        } catch (LocalizedException $e) {
            $updatedAt = null;
            $this->_logger->error($e->getMessage());
        }
        return $updatedAt;
    }

    /**
     * Is Enable Admin Notification
     *
     * @return boolean
     */
    public function isEnableAdminNotification()
    {
        return $this->scopeConfig->getValue(self::ENABLE_ADMIN_NOTIFICATION_CONFIG_XML_PATH);
    }

    /**
     * Is Enable Email Notification
     *
     * @return boolean
     */
    public function isEnableEmailNotification()
    {
        return $this->scopeConfig->getValue(self::ENABLE_EMAIL_NOTIFICATION_CONFIG_XML_PATH);
    }

    /**
     * Get Recipient Email
     *
     * @return mixed
     */
    public function getRecipientEmail()
    {
        return $this->scopeConfig->getValue(self::RECIPIENT_EMAIL_CONFIG_XML_PATH);
    }

    /**
     * Get Additional Emails
     *
     * @return string[]
     */
    public function getAdditionalEmails()
    {
        $additionalEmails = $this->scopeConfig->getValue(self::ADDITIONAL_EMAILS_CONFIG_XML_PATH);
        if ($additionalEmails) {
            $emailList = explode(',', $additionalEmails);
            foreach ($emailList as &$email) {
                $email = trim($email);
            }
            return $emailList;
        } else {
            return [];
        }
    }

    /**
     * Is Webpos Enabled
     *
     * @return bool
     */
    public function isWebposEnabled()
    {
        return $this->_moduleManager->isEnabled('Magestore_Webpos');
    }
}
