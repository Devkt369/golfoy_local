<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Observer\Adminhtml;
use ArtM\SFMC\Logger\Logger;
use ArtM\SFMC\Helper\Data;

class CustomerSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    protected $sfmcLogger;
    protected $_helperData;

    public function __construct(
        Logger $sfmcLogger,
        Data $_helperData
        ){
            $this->sfmcLogger = $sfmcLogger;
            $this->_helperData = $_helperData;
        }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $this->sfmcLogger->info("customer save from admin");
        $customer = $observer->getCustomer();
        $this->_helperData->disableSfmcAttribute($customer->getId());
        $this->sfmcLogger->info("sfmcsync disable - > ".$customer->getId());
        $this->_helperData->removeCustomerIdFromFailedtable($customer->getId());
        $this->sfmcLogger->info("customer deleted - > ".$customer->getId());
    }
}

