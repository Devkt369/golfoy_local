<?php
namespace ArtM\RecommendedSort\Logger;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class SortingQueueLogHandler extends Base
{
    protected $loggerType = Logger::INFO;
 
    protected $fileName = '/var/log/SortingQueueLog.log';
}