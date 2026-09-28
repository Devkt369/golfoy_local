<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Cron;

use Meetanshi\Sms\Helper\Data as SMSHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Class AutoSendMail
 *
 * Cron Auto sent email
 */
class AutoSendMail
{
    /**
     * @var \Magestore\Giftvoucher\Helper\Data
     */
    protected $helperGiftvoucher;

    /**
     * @var \Magestore\Giftvoucher\Model\GiftvoucherFactory
     */
    protected $giftvoucherFactory;

    /**
     * @var SMSHelper
     */
    protected $SMSHelper;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * ResourceConnection
     *
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @param \Magestore\Giftvoucher\Helper\Data $helper
     * @param \Magestore\Giftvoucher\Model\GiftvoucherFactory $giftvoucherFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param SMSHelper $SMSHelper
     * @param CustomerFactory $customerModel
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magestore\Giftvoucher\Helper\Data $helper,
        \Magestore\Giftvoucher\Model\GiftvoucherFactory $giftvoucherFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        SMSHelper $SMSHelper,
        CustomerFactory $customerModel,
        ResourceConnection $resourceConnection
    ) {
        $this->helperGiftvoucher = $helper;
        $this->giftvoucherFactory = $giftvoucherFactory;
        $this->dateTime = $dateTime;
        $this->SMSHelper = $SMSHelper;
        $this->customerModel = $customerModel;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return void
     */
    public function execute()
    {
        if ($this->helperGiftvoucher->getEmailConfig('autosend')) {
            $giftVouchers = $this->giftvoucherFactory->create()->getCollection()
                ->addFieldToFilter('status', ['neq' => \Magestore\Giftvoucher\Model\Status::STATUS_DELETED])
                ->addFieldToFilter('balance', ['gt' => 49])
                ->addFieldToFilter('expiry_reminder', [
                    ['eq' => false],
                    ['null' => true] 
                ])
                ->addFieldToFilter('expired_at', [
                    'to' => date('Y-m-d H:i:s', strtotime($this->dateTime->gmtDate()) + $this->helperGiftvoucher->getEmailConfig('daybefore') * 86400)
                    ])
                ->addFieldToFilter('expired_at', ['from' => $this->dateTime->gmtDate()]);
            $giftVoucherReminderSendId = [];
            foreach ($giftVouchers as $giftVoucher) {
                try{
                    if($giftVoucher->getData("customer_id")){
                        $account = $this->customerModel->create()->load($giftVoucher->getData("customer_id"));
                        $phoneNumber = $account->getData("phone_number");
                        if($phoneNumber){
                            $this->SMSHelper->sendGiftCardExpirySms($phoneNumber, $this->helperGiftvoucher->getEmailConfig('daybefore'), $giftVoucher->getData("balance"), $giftVoucher->getData("gift_code"));
                            $giftVoucherReminderSendId[] = $giftVoucher->getData("giftvoucher_id");
                        }else{
                            $this->SMSHelper->customLog("Phone number not found ".$giftVoucher->getData("customer_id"));
                        }
                    }
                }catch(\Exception $e){
                    // $this->SMSHelper->customLog("Exception not found");
                }
                // $giftVoucher->sendEmail(); //this is default function
            }
            if (!empty($giftVoucherReminderSendId)) {
                $connection = $this->resourceConnection->getConnection();
                $giftvoucherTable = $connection->getTableName('giftvoucher');

                $connection->update(
                    $giftvoucherTable,
                    ['expiry_reminder' => 1],
                    ['giftvoucher_id IN (?)' => $giftVoucherReminderSendId]
                );
                // $this->SMSHelper->customLog(json_encode($giftVoucherReminderSendId));
            }
        }

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/crontest.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('expired points running cron');
    }
}
