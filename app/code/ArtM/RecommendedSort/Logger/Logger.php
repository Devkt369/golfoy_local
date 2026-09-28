<?php

namespace ArtM\RecommendedSort\Logger;

use Monolog\Logger as BaseLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Logger extends BaseLogger
{
    public function __construct()
    {
        parent::__construct('recommended_sort_logger');
    }

    public function logToFile($message, $filename = 'sortlog.log', $level = self::INFO)
    {
        $logPath = BP . '/var/log/SortLog/';

        if (!is_dir($logPath)) {
            mkdir($logPath, 0775, true);
        }

        $filePath = $logPath . $filename;

        $handler = new StreamHandler($filePath, $level);
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);

        $this->setHandlers([]);
        $this->pushHandler($handler);

        $this->log($level, $message);
    }
}
