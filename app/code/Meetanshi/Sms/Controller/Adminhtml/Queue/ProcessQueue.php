<?php

namespace Meetanshi\Sms\Controller\Adminhtml\Queue;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meetanshi\Sms\Model\ResourceModel\SMSQueue\CollectionFactory;
use Meetanshi\Sms\Helper\Data as SmsHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class ProcessQueue
 * @package Meetanshi\Sms\Controller\Adminhtml\Queue
 */
class ProcessQueue extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CollectionFactory
     */
    protected $smsQueueCollectionFactory;

    /**
     * @var SmsHelper
     */
    protected $smsHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * ProcessQueue constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CollectionFactory $smsQueueCollectionFactory
     * @param SmsHelper $smsHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectionFactory $smsQueueCollectionFactory,
        SmsHelper $smsHelper,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime,
        TimezoneInterface $timezone
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->smsQueueCollectionFactory = $smsQueueCollectionFactory;
        $this->smsHelper = $smsHelper;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $processed = 0;
            $failed = 0;
            $skipped = 0;

            // Check if SMS scheduler is enabled
            $schedulerEnabled = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_SCHEDULER_ENABLE,
                ScopeInterface::SCOPE_STORE
            );

            if (!$schedulerEnabled) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => 'SMS Scheduler is disabled'
                ]);
            }

            // Get max retry attempts
            $maxRetryAttempts = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_SCHEDULER_MAX_RETRY,
                ScopeInterface::SCOPE_STORE
            ) ?: 3;

            // Get current time in GMT
            $currentGmtTime = $this->dateTime->gmtDate();

            // Load pending SMS messages
            $collection = $this->smsQueueCollectionFactory->create();
            $collection->addFieldToFilter('status', \Meetanshi\Sms\Model\SMSQueue::STATUS_PENDING)
                      ->addFieldToFilter('attempts', ['lt' => $maxRetryAttempts])
                      ->addFieldToFilter(
                          ['scheduled_at', 'scheduled_at'],
                          [
                              ['null' => true],
                              ['lteq' => $currentGmtTime]
                          ]
                      );

            $this->smsHelper->customLog("Processing SMS Queue - Found " . $collection->getSize() . " messages");

            foreach ($collection as $smsItem) {
                try {
                    // Decode payload
                    $payload = json_decode($smsItem->getMessagePayload(), true);
                    
                    if (!$payload) {
                        $smsItem->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_FAILED);
                        $smsItem->setErrorMessage('Invalid payload format');
                        $smsItem->setAttempts($smsItem->getAttempts() + 1);
                        $smsItem->save();
                        $failed++;
                        continue;
                    }

                    // Attempt to send SMS
                    $result = $this->smsHelper->apiCall(
                        $payload,
                        $smsItem->getMobileNumber(),
                        $smsItem->getObserverName() . '_queued'
                    );

                    if ($result !== false) {
                        // Success
                        $smsItem->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_SENT);
                        $smsItem->setUpdatedAt($this->dateTime->gmtDate());
                        $smsItem->save();
                        $processed++;
                        
                        $this->smsHelper->customLog("SMS sent successfully to: " . $smsItem->getMobileNumber());
                    } else {
                        // Failed - increment attempts
                        $attempts = $smsItem->getAttempts() + 1;
                        $smsItem->setAttempts($attempts);
                        
                        if ($attempts >= $maxRetryAttempts) {
                            $smsItem->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_FAILED);
                            $smsItem->setErrorMessage('Maximum retry attempts reached');
                        }
                        
                        $smsItem->setUpdatedAt($this->dateTime->gmtDate());
                        $smsItem->save();
                        $failed++;
                        
                        $this->smsHelper->customLog("SMS failed for: " . $smsItem->getMobileNumber() . " (Attempt: {$attempts})");
                    }
                } catch (\Exception $e) {
                    // Error processing individual SMS
                    $attempts = $smsItem->getAttempts() + 1;
                    $smsItem->setAttempts($attempts);
                    $smsItem->setErrorMessage($e->getMessage());
                    
                    if ($attempts >= $maxRetryAttempts) {
                        $smsItem->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_FAILED);
                    }
                    
                    $smsItem->setUpdatedAt($this->dateTime->gmtDate());
                    $smsItem->save();
                    $failed++;
                    
                    $this->smsHelper->customLog("Error processing SMS for " . $smsItem->getMobileNumber() . ": " . $e->getMessage());
                }
            }

            $message = "SMS Queue processed: {$processed} sent, {$failed} failed, {$skipped} skipped";
            $this->smsHelper->customLog($message);

            return $resultJson->setData([
                'success' => true,
                'message' => $message,
                'processed' => $processed,
                'failed' => $failed,
                'skipped' => $skipped
            ]);

        } catch (\Exception $e) {
            $this->smsHelper->customLog("Error processing SMS queue: " . $e->getMessage());
            
            return $resultJson->setData([
                'success' => false,
                'message' => 'Error processing SMS queue: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Check if user has permission to access this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Meetanshi_Sms::sms');
    }
}