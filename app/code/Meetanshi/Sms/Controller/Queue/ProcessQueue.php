<?php

namespace Meetanshi\Sms\Controller\Queue;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Meetanshi\Sms\Model\ResourceModel\SMSQueue\CollectionFactory;
use Meetanshi\Sms\Helper\Data as SmsHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Class ProcessQueue
 * @package Meetanshi\Sms\Controller\Queue
 */
class ProcessQueue extends Action implements CsrfAwareActionInterface
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
            // Simple security check - you can add your own token validation here
            $token = $this->getRequest()->getParam('token');
            $configuredToken = $this->scopeConfig->getValue(
                'sms/smsscheduler/cron_token',
                ScopeInterface::SCOPE_STORE
            );
            
            if ($configuredToken && $token !== $configuredToken) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => 'Invalid token'
                ]);
            }

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

            // Debug: Check SMS module configuration
            $smsEnabled = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_ENABLE,
                ScopeInterface::SCOPE_STORE
            );
            
            $apiProvider = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_APIPROVIDER,
                ScopeInterface::SCOPE_STORE
            );
            
            $this->smsHelper->customLog("SMS Configuration - SMS Enabled: " . ($smsEnabled ? 'Yes' : 'No') . ", API Provider: " . ($apiProvider ?: 'Not Set'));

            // Check if current time is within allowed sending hours
            $restrictFromTime = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_SCHEDULER_RESTRICT_FROM_TIME,
                ScopeInterface::SCOPE_STORE
            );
            
            $restrictToTime = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_SCHEDULER_RESTRICT_TO_TIME,
                ScopeInterface::SCOPE_STORE
            );

            // Debug log the configuration values
            $this->smsHelper->customLog("SMS Scheduler Config - Restrict From: '{$restrictFromTime}', Restrict To: '{$restrictToTime}'");

            // If restriction times are set, check if current time allows sending
            if ($restrictFromTime && $restrictToTime) {
                // Validate time format before processing
                if (!$this->isValidTimeFormat($restrictFromTime) || !$this->isValidTimeFormat($restrictToTime)) {
                    $this->smsHelper->customLog("Invalid time format in configuration - From: {$restrictFromTime}, To: {$restrictToTime}");
                    return $resultJson->setData([
                        'success' => false,
                        'message' => 'Invalid time format in SMS scheduler configuration'
                    ]);
                }

                $configuredTimezone = $this->scopeConfig->getValue(
                    \Meetanshi\Sms\Helper\Data::SMS_SCHEDULER_TIMEZONE,
                    ScopeInterface::SCOPE_STORE
                ) ?: 'Asia/Kolkata';

                // Get current time in configured timezone
                $currentDateTime = $this->timezone->date(null, null, $configuredTimezone);
                $currentTime = $currentDateTime->format('H:i');

                // Convert times to comparable format
                $currentTimeMinutes = $this->timeToMinutes($currentTime);
                $restrictFromMinutes = $this->timeToMinutes($restrictFromTime);
                $restrictToMinutes = $this->timeToMinutes($restrictToTime);

                $isRestrictedTime = false;
                
                // Handle time range spanning midnight (e.g., 21:00 to 10:00)
                if ($restrictFromMinutes > $restrictToMinutes) {
                    // Restriction spans midnight
                    $isRestrictedTime = ($currentTimeMinutes >= $restrictFromMinutes || $currentTimeMinutes <= $restrictToMinutes);
                } else {
                    // Normal time range within same day
                    $isRestrictedTime = ($currentTimeMinutes >= $restrictFromMinutes && $currentTimeMinutes <= $restrictToMinutes);
                }

                if ($isRestrictedTime) {
                    $this->smsHelper->customLog("SMS Queue processing skipped - Current time ({$currentTime}) is within restricted hours ({$restrictFromTime} to {$restrictToTime})");
                    return $resultJson->setData([
                        'success' => false,
                        'message' => "Processing skipped - Current time ({$currentTime}) is within restricted hours ({$restrictFromTime} to {$restrictToTime})",
                        'current_time' => $currentTime,
                        'restricted_from' => $restrictFromTime,
                        'restricted_to' => $restrictToTime
                    ]);
                }
                
                $this->smsHelper->customLog("SMS Queue processing allowed - Current time ({$currentTime}) is outside restricted hours ({$restrictFromTime} to {$restrictToTime})");
            }

            // Get max retry attempts
            $maxRetryAttempts = $this->scopeConfig->getValue(
                \Meetanshi\Sms\Helper\Data::SMS_SCHEDULER_MAX_RETRY,
                ScopeInterface::SCOPE_STORE
            ) ?: 3;

            // Load pending SMS messages - process all pending SMS regardless of scheduled time
            $collection = $this->smsQueueCollectionFactory->create();
            
            // Debug: Log max retry attempts
            $this->smsHelper->customLog("SMS Queue Debug - Processing all pending SMS, Max Retry Attempts: {$maxRetryAttempts}");
            
            $collection->addFieldToFilter('status', \Meetanshi\Sms\Model\SMSQueue::STATUS_PENDING)
                      ->addFieldToFilter('attempts', ['lt' => $maxRetryAttempts])
                      ->setOrder('created_at', 'ASC')
                      ->setPageSize(100); // Process in batches

            // Debug: Log the SQL query to see what's being executed
            $this->smsHelper->customLog("SMS Queue SQL Query: " . $collection->getSelect()->__toString());
            
            // Debug: Check all pending messages to see what's in the queue
            $debugCollection = $this->smsQueueCollectionFactory->create();
            $debugCollection->addFieldToFilter('status', \Meetanshi\Sms\Model\SMSQueue::STATUS_PENDING);
            
            $this->smsHelper->customLog("Debug - Total pending messages in queue: " . $debugCollection->getSize());
            
            foreach ($debugCollection as $debugItem) {
                $this->smsHelper->customLog("Debug - Pending SMS ID: " . $debugItem->getId() . 
                    ", Mobile: " . $debugItem->getMobileNumber() . 
                    ", Attempts: " . $debugItem->getAttempts() . 
                    ", Scheduled: " . ($debugItem->getScheduledAt() ?: 'NULL') . 
                    ", Created: " . $debugItem->getCreatedAt());
            }
            
            $this->smsHelper->customLog("Processing SMS Queue - Found " . $collection->getSize() . " messages to process (ignoring scheduled time)");

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

                    // Log the payload and mobile number before sending
                    $this->smsHelper->customLog("Processing SMS ID: " . $smsItem->getId());
                    $this->smsHelper->customLog("Mobile Number: " . $smsItem->getMobileNumber());
                    $this->smsHelper->customLog("Observer Name: " . $smsItem->getObserverName());
                    $this->smsHelper->customLog("Payload: " . $smsItem->getMessagePayload());
                    
                    // Attempt to send SMS
                    $result = $this->smsHelper->apiCall(
                        $payload,
                        $smsItem->getMobileNumber(),
                        $smsItem->getObserverName() . '_queued'
                    );

                    // Log the result from API call
                    $this->smsHelper->customLog("API Call Result: " . ($result !== false ? json_encode($result) : 'FALSE'));

                    if ($result !== false && $result !== 'DUPLICATE_PREVENTED') {
                        // Success
                        $smsItem->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_SENT);
                        $smsItem->setUpdatedAt($this->dateTime->gmtDate());
                        $smsItem->save();
                        $processed++;
                        
                        $this->smsHelper->customLog("SMS sent successfully to: " . $smsItem->getMobileNumber() . " with result: " . json_encode($result));
                    } else {
                        // Failed or duplicate - increment attempts
                        $attempts = $smsItem->getAttempts() + 1;
                        $smsItem->setAttempts($attempts);
                        
                        $errorMessage = ($result === 'DUPLICATE_PREVENTED') ? 'Duplicate SMS prevented' : 'API call failed';
                        
                        if ($attempts >= $maxRetryAttempts) {
                            $smsItem->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_FAILED);
                            $smsItem->setErrorMessage($errorMessage);
                        }
                        
                        $smsItem->setUpdatedAt($this->dateTime->gmtDate());
                        $smsItem->save();
                        $failed++;
                        
                        $this->smsHelper->customLog("SMS failed for: " . $smsItem->getMobileNumber() . " (Attempt: {$attempts}), Reason: " . $errorMessage);
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

            $message = "SMS Queue processed: {$processed} sent, {$failed} failed";
            $this->smsHelper->customLog($message);

            return $resultJson->setData([
                'success' => true,
                'message' => $message,
                'processed' => $processed,
                'failed' => $failed,
                'total_in_queue' => $collection->getSize()
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
     * Create CSRF validation exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Validate time format
     *
     * @param string $time
     * @return bool
     */
    private function isValidTimeFormat($time)
    {
        if (empty($time)) {
            return false;
        }
        
        // Handle different time formats: HH:MM or HH,MM,SS
        $time = str_replace(',', ':', $time);
        $parts = explode(':', $time);
        
        if (count($parts) < 2) {
            return false;
        }
        
        $hours = intval($parts[0]);
        $minutes = intval($parts[1]);
        
        // Validate range
        return ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59);
    }

    /**
     * Convert time string to minutes since midnight
     *
     * @param string $time
     * @return int
     */
    private function timeToMinutes($time)
    {
        // Handle different time formats: HH:MM or HH,MM,SS
        $time = str_replace(',', ':', $time); // Convert commas to colons
        $parts = explode(':', $time);
        
        // Take only hours and minutes (ignore seconds if present)
        $hours = intval($parts[0] ?? 0);
        $minutes = intval($parts[1] ?? 0);
        
        return ($hours * 60) + $minutes;
    }
}