<?php

namespace ArtM\SSO\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Log\LoggerInterface;

class PreservePhoneNumber
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * Before saving customer, preserve phone_number if it's missing from the save data.
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return array
     */
    public function beforeSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer,
        $passwordHash = null
    ) {
        try {
            $customerId = $customer->getId();
            if ($customerId) {
                $phoneNumber = $customer->getCustomAttribute('phone_number');
                $phoneValue = $phoneNumber ? $phoneNumber->getValue() : null;

                if (empty($phoneValue)) {
                    $existingCustomer = $subject->getById($customerId);
                    $existingPhone = $existingCustomer->getCustomAttribute('phone_number');

                    if ($existingPhone && !empty($existingPhone->getValue())) {
                        $customer->setCustomAttribute('phone_number', $existingPhone->getValue());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('ArtM_SSO PreservePhoneNumber: ' . $e->getMessage());
        }

        return [$customer, $passwordHash];
    }
}
