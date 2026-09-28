<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PaymentOffline\Plugin\Model\Source\Adminhtml\Payment;

use Magestore\PaymentOffline\Model\ResourceModel\PaymentOffline\CollectionFactory;
use Magestore\PaymentOffline\Model\ResourceModel\PaymentOffline\Collection;
use Magestore\Webpos\Model\Source\Adminhtml\Payment\Method as WebposPaymentMethodSource;

/**
 * Class Method
 *
 * Add offline payment to source
 */
class Method
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Constructor
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * After get option hash to add method
     *
     * @param WebposPaymentMethodSource $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetOptionHash(
        WebposPaymentMethodSource $subject,
        array $result
    ) {
        /* @var Collection $collection*/
        $collection = $this->collectionFactory->create();
        foreach ($collection as $payment) {
            $result[$payment['payment_code']] = __($payment['title']);
        }
        return $result;
    }
}
