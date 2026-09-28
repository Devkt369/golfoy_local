<?php

namespace ArtM\Common\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;

class UpdateProductDetails implements ObserverInterface
{
    protected $timezone;
    protected $adminSession;

    public function __construct(
        TimezoneInterface $timezone,
        AdminSession $adminSession
    ) {
        $this->timezone = $timezone;
        $this->adminSession = $adminSession;
    }

    public function execute(Observer $observer)
    {
        // Debugging statement to check if the observer is triggered
//        exit('Observer triggered');

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $originalProduct = $product->getOrigData();

        if ($originalProduct && isset($originalProduct['status'])) {
            $originalStatus = $originalProduct['status'];
            $newStatus = $product->getStatus();

            // Get the current user
            $user = $this->adminSession->getUser();
            $username = $user ? $user->getUsername() : 'unknown';

            // Get the current date and time
            $currentTime = $this->timezone->date()->format('d F Y h:i A');

            // Get the current updateddetails value
            $updatedDetails = $product->getData('updateddetails');
            $updatedDetails = $updatedDetails ? $updatedDetails . "\n" : '';
            
            if (!empty($updatedDetails)) {
                // Convert the string to an array of entries
                $entries = explode("\n", $updatedDetails);

                // Limit to the last 10 entries
                $lastTenEntries = array_slice($entries, -20);

                // print_r($lastTenEntries);
                // Convert back to string
                $updatedDetails = '';
                foreach( $lastTenEntries as $lastTenEntry ){
                    if( $lastTenEntry == '' ){
                        $updatedDetails .= "";
                    }else{
                        $updatedDetails .= $lastTenEntry;
                    }
                }
            }
            
            // Check if status changed
            if ($originalStatus != $newStatus) {
                if ($newStatus == Product\Attribute\Source\Status::STATUS_ENABLED) {
                    // Product is enabled
                    $updatedDetails .= "\n{$username} enabled this product at {$currentTime}";
                } elseif ($newStatus == Product\Attribute\Source\Status::STATUS_DISABLED) {
                    // Product is disabled
                    $updatedDetails .= "\n{$username} disabled this product at {$currentTime}";
                }
            }

            // Always log the update action
            $updatedDetails .= "\n{$username} updated this product at {$currentTime}\n\n";

            // Set the updateddetails attribute
            $product->setData('updateddetails', $updatedDetails);

            // Forcefully save the product if needed
            try {
                $product->getResource()->saveAttribute($product, 'updateddetails');
            } catch (\Exception $e) {
                // Log the exception if needed
                // Example: \Psr\Log\LoggerInterface $logger
                // $this->logger->critical($e->getMessage());
            }
        }
    }
}
