<?php

namespace Meetanshi\Sms\Observer\Frontend\Customer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Meetanshi\Sms\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

/**
 * Class CreateAfter
 * @package Meetanshi\Sms\Observer
 */
class CreateAfter implements ObserverInterface
{
    /**
     *
     */
    const SMS_ADMIN_MOBILE = 'sms/general/mobilenumber';

    /**
     *
     */
    const SMS_CUSTOMERCREATEAFTER_ENABLE = 'sms/customerregisterafter/enabled';
    /**
     *
     */
    const SMS_CUSTOMERCREATEAFTER_MSGTOADMIN = 'sms/customerregisterafter/msgtoadmin';
    /**
     *
     */
    const SMS_CUSTOMERCREATEAFTER_SMSTEXT = 'sms/customerregisterafter/smstext';
    /**
     *
     */
    const SMS_CUSTOMERCREATEAFTER_SMSTEXTADMIN = 'sms/customerregisterafter/smstextadmin';

    /**
     *
     */
    const MSG91_TEMPLATE_ID = 'sms/customerregisterafter/templateid_msg91';
    const MSG91_PARAMETER = 'sms/customerregisterafter/parameter_msg91';
    /**
     *
     */
    const TEMPLATE_ID_ADMIN = 'sms/customerregisterafter/templateidadmin';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * Orderplaceafter constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Data $data,
        StoreManagerInterface $storeManager,
        PriceHelper $priceHelper
    ) {
        $this->logger = $logger;
        $this->helper = $data;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->helper->customLog("create customer apiCall call");
        try {
            $customer = $observer->getCustomer();
            $customerData = $customer->getData();

            if ($customer && isset($customerData['entity_id'])) {
                try {
                    $sfmcHelper = isset($this->sfmcHelper) ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\ArtM\SFMC\Helper\Data::class);
                    
                    if ($sfmcHelper && method_exists($sfmcHelper, 'SyncCustomer')) {
                        $sfmcHelper->SyncCustomer($customerData['entity_id']);
                    }
                } catch (\Exception $sfmcEx) {
                    $this->logger->info('SFMC Sync Fallback Error: ' . $sfmcEx->getMessage());
                }
            }

            if ($this->helper->smsEnable()) {
                $data = array(100);
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                $enableCustomerCreate = $this->scopeConfig->getValue(self::SMS_CUSTOMERCREATEAFTER_ENABLE, $storeScope);
                
                if ($enableCustomerCreate) {
                    $msgVar = $this->scopeConfig->getValue(self::MSG91_PARAMETER, $storeScope);
                    $temp_id = $this->scopeConfig->getValue(self::MSG91_TEMPLATE_ID, $storeScope);
                    $paramKeys = array_filter(array_map('trim', explode(',', $msgVar)));
                    $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope) ?: 0;
                    $recipient = ['mobiles' => '91' . $customerData['phone_number']];
                    foreach ($paramKeys as $index => $key) {
                        $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                    }

                    $payload = [
                        'template_id' => $temp_id,
                        'short_url' => $shortUrl,
                        'recipients' => [$recipient]
                    ];
                    $observerName = 'customer_register_success';
                    $this->helper->customLog("create customer apiCall call customer_register_success CheckRestrictedTime");
                    $this->helper->CheckRestrictedTime($payload, $customerData['phone_number'], $observerName);
                }   
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}