<?php
namespace ArtM\SFMC\Logger;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class HandlerOrder extends Base
{
    protected $loggerType = Logger::INFO;
 
    protected $fileName = '/var/log/sfmcorder.log';
}