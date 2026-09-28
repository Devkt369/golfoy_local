<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Logger\Console\Command\AbstractLogStatusCommand;
use Magestore\LoggerApi\Api\IsEnabledLogTypeInterface;

/**
 * Service for is enabled log type
 *
 * @api
 */
class IsEnabledLogType implements IsEnabledLogTypeInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * CheckCanLogConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute service
     *
     * @param string $logType
     * @return int
     */
    public function execute(string $logType): int
    {
        $isEnabledLogType = $this->scopeConfig->getValue(
            AbstractLogStatusCommand::CONFIG_PATH_LOG_STATUS . '/types/' . $logType,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if ($isEnabledLogType == null) {
            $isEnabledLogType = (int) $this->scopeConfig->getValue(
                AbstractLogStatusCommand::CONFIG_PATH_DEFAULT_STATUS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
        return (int) $isEnabledLogType;
    }
}
