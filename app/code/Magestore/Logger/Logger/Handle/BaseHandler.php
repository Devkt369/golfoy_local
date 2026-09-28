<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Logger\Logger\Handle;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;
use Monolog\LogRecord;

/**
 * Class BaseHandler
 *
 * Base handler for the logger
 */
class BaseHandler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '';

    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @inheritDoc
     */
    protected function writeRecord(array $record)
    {
        $record['formatted'] = $record['message'] . "\n";
        $this->write($record);
    }

    /**
     * @inheritdoc
     */
    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);

        $formatted = $this->getFormatter()->format($record);

        $this->writeRecord([
            'record' => $record,
            'formatted' => $formatted
        ]);

        return false === $this->bubble;
    }
}
