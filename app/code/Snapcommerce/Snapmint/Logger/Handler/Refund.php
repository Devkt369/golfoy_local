<?php

namespace Snapcommerce\Snapmint\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Refund extends Base
{
    protected $fileName = '/var/log/Snapmint_payment.log';
    protected $loggerType = Logger::INFO;
}
