<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_ClickAndCollectAdminHtml
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\ClickAndCollectAdminUi\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Model SaveSpecialDayLocation
 */
class SaveScheduleLocation implements ObserverInterface
{

    /**
     * Function execute event
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $model = $observer->getData('model');
        $data = $observer->getData('data');

        if (isset($data['click_and_collect'])) {
            $scheduleId = $data['click_and_collect']['section_store_schedule']['schedule_id'] ?? null;
            $isAllowPickUp = $data['click_and_collect']['section_store_schedule']['is_allow_pickup'] ?? 0;
            $model->setIsAllowPickup($isAllowPickUp);
            $model->setScheduleId($scheduleId);
        }
    }
}
