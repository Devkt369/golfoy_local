<?php
namespace ArtM\RecommendedSort\Logger;
 
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;
 
class SortingLogHandler extends Base
{
    protected $loggerType = Logger::INFO;
 
    protected $fileName = '/var/log/SortingLog.log';
}