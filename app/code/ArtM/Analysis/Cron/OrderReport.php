<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\SFMC\Cron;

use ArtM\Analysis\Helper\Data;

class OrderReport
{
    protected $helperData;

    public function __construct(
        Data $helperData
        )
    {
        $this->_helperData = $helperData;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        if($this->_helperData->getSalesOrderCronStatus()){
            $endDate = date('Y-m-d 23:59:59', strtotime('yesterday'));
            $startDate = date('Y-m-d 00:00:00', strtotime('yesterday'));
            $this->_helperData->getSalesDate($startDate, $endDate);        
        }
    }
}