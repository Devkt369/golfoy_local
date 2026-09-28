<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Conditions for Magento 2
 */

namespace Amasty\Conditions\Model;

use Magento\Customer\Model\Customer;

class CurrentCustomerStorage
{
    /**
     * @var Customer|null
     */
    private $currentCustomer;

    public function getCurrentCustomer(): ?Customer
    {
        return $this->currentCustomer;
    }

    public function setCurrentCustomer(Customer $customer): void
    {
        $this->currentCustomer = $customer;
    }

    public function _resetState(): void
    {
        $this->currentCustomer = null;
    }
}
