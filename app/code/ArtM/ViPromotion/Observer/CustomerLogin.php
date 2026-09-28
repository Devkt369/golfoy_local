<?php

namespace ArtM\ViPromotion\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use ArtM\ViPromotion\Helper\Data;
use Magento\Framework\Registry;

class CustomerLogin implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(
        Data $helper,
        Registry $registry
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
    }

    /**
     * Execute observer
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->helper->shouldShowPopup()) {
            // Register flag to show popup after login
            $this->registry->register('vipromotion_show_popup', true);
            // Don't mark as shown here - only when user closes it
        }
    }
}