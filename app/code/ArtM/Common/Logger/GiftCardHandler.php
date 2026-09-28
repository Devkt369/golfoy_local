<?php
namespace ArtM\Common\Logger;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class GiftCardHandler extends Base
{
    protected $loggerType = Logger::INFO;
 
    protected $fileName = '/var/log/GiftCardInvoice.log';
}