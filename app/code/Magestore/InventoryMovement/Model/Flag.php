<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

/**
 * Flag Model
 */
class Flag extends \Magento\Reports\Model\Flag
{
    const REPORT_ORDERS_POTENTIAL_ERROR_FLAG_CODE = "orders_potential_error";
    const ORDERS_POTENTIAL_ERROR_LAST_ID_KEY = 'last_id';

    /**
     * Get Orders Potential Error Updated At
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrdersPotentialErrorUpdatedAt()
    {
        $flag = $this->setReportFlagCode(self::REPORT_ORDERS_POTENTIAL_ERROR_FLAG_CODE)->loadSelf();
        return $flag->hasData() ? $flag->getLastUpdate() : '';
    }
}
