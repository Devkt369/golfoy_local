<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Logger\Console\Command\AbstractLogStatusCommand;
use Magestore\LoggerApi\Api\IsEnabledLogFlowInterface;

/**
 * Service for is enabled log flow
 *
 * @api
 */
class IsEnabledLogFlow implements IsEnabledLogFlowInterface
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
     * @param string $logFlow
     * @return int
     */
    public function execute(string $logFlow): int
    {
        $isEnabledLogFlows = $this->scopeConfig->getValue(
            AbstractLogStatusCommand::CONFIG_PATH_LOG_STATUS . '/flows/' . $logFlow,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if ($isEnabledLogFlows == null) {
            $isEnabledLogFlows = (int) $this->scopeConfig->getValue(
                AbstractLogStatusCommand::CONFIG_PATH_DEFAULT_STATUS,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
        return (int) $isEnabledLogFlows;
    }
}
