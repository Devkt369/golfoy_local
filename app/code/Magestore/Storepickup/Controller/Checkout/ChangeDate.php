<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Giftvoucher
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Storepickup\Controller\Checkout;

use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Class ChangeDate
 *
 * Used to change date
 */
class ChangeDate extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
    const XML_PATH_STOREPICKUP_TIME_TO_PREPARE_PICKUP = 'storepickup/general/time_to_prepare_pickup';
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magestore\Storepickup\Model\StoreFactory
     */
    protected $_storeCollection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_formatdate;
    /**
     * @var \Magestore\Storepickup\Helper\Data
     */
    protected $_storepickupHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * ChangeDate constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magestore\Storepickup\Model\StoreFactory $storeCollection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $gmtdate
     * @param \Magestore\Storepickup\Helper\Data $storepickupHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magestore\Storepickup\Model\StoreFactory $storeCollection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $gmtdate,
        \Magestore\Storepickup\Helper\Data $storepickupHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_storeCollection = $storeCollection;
        $this->_checkoutSession = $checkoutSession;
        $this->_storepickupHelper = $storepickupHelper;
        $this->_formatdate = $gmtdate;
        $this->localeDate = $localeDate;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }
    
    /**
     * Execute
     *
     * @return mixed
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        $date = [];
        $date['error'] = 0;
        $storeId = $this->getRequest()->getParam('store_id');

        /** Fix issue #2179 upstream 4 develop: Store pickup time selection is wrong  */
        $configTimeZone = $this->_formatdate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        date_default_timezone_set($configTimeZone);
        /** End code #2179 */

        $currentDate = $this->localeDate->scopeDate($storeId, null, true);
        $today = $currentDate->format('Y-m-d');
        $thisTime = $currentDate->format('H:i');
        $timePreparePickup = $this->scopeConfig->getValue(
            self::XML_PATH_STOREPICKUP_TIME_TO_PREPARE_PICKUP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($timePreparePickup) {
            $thisTime = strtotime($thisTime);
            $thisTime = date("H:i", strtotime('+' . $timePreparePickup . ' minutes', $thisTime));
        }
        $shippingDateString = $this->getRequest()->getParam('shipping_date');
        $shippingDate = date('Y-m-d', strtotime($shippingDateString));
        $dayofweek = strtolower(date('l', strtotime($shippingDate)));
        // save pickup date to data
        $collectionstore = $this->_storeCollection->create();
        $store = $collectionstore->load($storeId, 'storepickup_id');
        $hasBreakTime = $store->hasBreakTime($dayofweek);
        // check special days
        $specialsData = $store->getSpecialdaysData();
        $specialday = false;
        foreach ($specialsData as $specialID) {
            $isSpecialday = array_search($shippingDate, $specialID['date'], false);
            if ($isSpecialday !== false) {
                $specialday = true;
                $date['time_open'] = $specialID['time_open'];
                $date['time_close'] = $specialID['time_close'];
            }
        }
        // if shipping date is today
        if ($shippingDate == $today) {
            if ($specialday) {
                $date['html'] = $this->_storepickupHelper->generateTimes(
                    $date['time_open'],
                    $date['time_close'],
                    $thisTime
                );
                return $this->getResponse()->setBody(\Zend_Json::encode($date));
            } else {
                $date['time_open'] = $store->getData($dayofweek . '_open');
                $date['time_close'] = $store->getData($dayofweek . '_close');
                if ($thisTime > $date['time_close']) {
                    $date['error'] = __('The worktime has been finished. Please select an other day');
                    return $this->getResponse()->setBody(\Zend_Json::encode($date));
                }
                if (!$hasBreakTime) {
                    $date['html'] = $this->_storepickupHelper->generateTimes(
                        $date['time_open'],
                        $date['time_close'],
                        $thisTime
                    );
                } else {
                    $date['open_break'] = $store->getData($dayofweek . '_open_break');
                    $date['html'] = $this->_storepickupHelper->generateTimes(
                        $date['time_open'],
                        $date['open_break'],
                        $thisTime
                    );
                    $date['close_break'] = $store->getData($dayofweek . '_close_break');
                    $date['html'] .= $this->_storepickupHelper->generateTimes(
                        $date['close_break'],
                        $date['time_close'],
                        $thisTime
                    );
                }
                return $this->getResponse()->setBody(\Zend_Json::encode($date));
            }
        }
        // shipping date is a specialday
        if ($specialday) {
            $date['html'] = $this->_storepickupHelper->generateTimes(
                $date['time_open'],
                $date['time_close']
            );
            return $this->getResponse()->setBody(\Zend_Json::encode($date));
        }
        //shipping date is a normal day
        if (!$hasBreakTime) {
            $date['time_open'] = $store->getData($dayofweek . '_open');
            $date['time_close'] = $store->getData($dayofweek . '_close');
            $date['html'] = $this->_storepickupHelper->generateTimes($date['time_open'], $date['time_close']);
        } else {
            $date['time_open'] = $store->getData($dayofweek . '_open');
            $date['open_break'] = $store->getData($dayofweek . '_open_break');
            $date['html'] = $this->_storepickupHelper->generateTimes($date['time_open'], $date['open_break']);
            $date['close_break'] = $store->getData($dayofweek . '_close_break');
            $date['time_close'] = $store->getData($dayofweek . '_close');
            $date['html'] .= $this->_storepickupHelper->generateTimes($date['close_break'], $date['time_close']);
        }
        return $this->getResponse()->setBody(\Zend_Json::encode($date));
    }
}
