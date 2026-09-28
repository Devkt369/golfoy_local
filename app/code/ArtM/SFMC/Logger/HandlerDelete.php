<?php
namespace ArtM\SFMC\Logger;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class HandlerDelete extends Base
{
    protected $loggerType = Logger::INFO;
 
    protected $fileName = '/var/log/sfmcdelete.log';
}