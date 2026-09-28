<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Console\Command;

use Magento\Framework\App\Config\ConfigResource\ConfigInterface as ResourceConfig;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Logger\Model\Structure\Config;
use Magestore\Logger\Model\Source\LogFlows;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract class for Enable and Disable commands to consolidate common logic
 */
abstract class AbstractLogStatusCommand extends Command
{
    const CONFIG_PATH_LOG_STATUS = 'magestore/log/status';

    const INPUT_KEY_TYPES = '';

    const CONFIG_PATH_DEFAULT_STATUS = 'logger/default/enabled';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LogFlows
     */
    protected $logFlows;

    /**
     * @var ResourceConfig
     */
    protected $resourceConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ReinitableConfigInterface
     */
    protected $reinitableConfig;

    /**
     * AbstractLogStatusCommand constructor.
     *
     * @param Config $config
     * @param LogFlows $logFlows
     * @param ResourceConfig $resourceConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param ReinitableConfigInterface $reinitableConfig
     * @param string|null $name
     */
    public function __construct(
        Config $config,
        LogFlows $logFlows,
        ResourceConfig $resourceConfig,
        ScopeConfigInterface $scopeConfig,
        ReinitableConfigInterface $reinitableConfig,
        ?string $name = null
    ) {
        $this->config = $config;
        $this->logFlows = $logFlows;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
        $this->reinitableConfig = $reinitableConfig;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->addArgument(
            static::INPUT_KEY_TYPES,
            InputArgument::IS_ARRAY,
            'Space-separated list of log ' . static::INPUT_KEY_TYPES. 'or omit to apply to all log '
                . static::INPUT_KEY_TYPES . '.'
        );
        parent::configure();
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isEnable = $this->isEnable();
        $types = $this->getRequestedTypes($input);
        $changedTypes = $this->setEnabled($types, $isEnable);
        if ($changedTypes) {
            $output->writeln('Changed log ' . static::INPUT_KEY_TYPES . ' status:');
            foreach ($changedTypes as $type) {
                $output->writeln(sprintf('%30s: %d -> %d', $type, !$isEnable, $isEnable));
            }
        } else {
            $output->writeln('There is nothing to change in log status');
        }
    }

    /**
     * Default is enabled log
     *
     * @return int
     */
    protected function getDefaultStatus()
    {
        return (int) $this->scopeConfig->getValue(
            self::CONFIG_PATH_DEFAULT_STATUS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * Set enable for types
     *
     * @param array $items
     * @param int $isEnable
     * @return array
     */
    protected function setEnabled(array $items, int $isEnable)
    {
        $enablePath = self::CONFIG_PATH_LOG_STATUS . '/'. static::INPUT_KEY_TYPES. '/';
        $changeItems = [];
        foreach ($items as $item) {
            $currentStatus = $this->scopeConfig->getValue(
                $enablePath . $item,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            if ($currentStatus == null) {
                $currentStatus = $this->getDefaultStatus();
            }
            if ((int) $currentStatus !== $isEnable) {
                $this->resourceConfig->saveConfig(
                    $enablePath . $item,
                    $isEnable,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
                $changeItems[] = $item;
            }
        }
        $this->reinitableConfig->reinit();
        return $changeItems;
    }

    /**
     * Get requested types for logs
     *
     * @param InputInterface $input
     * @return array
     */
    protected function getRequestedTypes(InputInterface $input)
    {
        $requestedTypes = [];
        if ($input->getArgument(static::INPUT_KEY_TYPES)) {
            $requestedTypes = $input->getArgument(static::INPUT_KEY_TYPES);
            $requestedTypes = array_filter(array_map('trim', $requestedTypes), 'strlen');
        }
        $availableTypes = $this->getAvailableType();
        if (empty($requestedTypes)) {
            return $availableTypes;
        } else {
            $unsupportedTypes = array_diff($requestedTypes, $availableTypes);
            if ($unsupportedTypes) {
                throw new \InvalidArgumentException(
                    "The following requested types are not supported: '" . join("', '", $unsupportedTypes)
                    . "'." . PHP_EOL . 'Supported types: ' . join(", ", $availableTypes)
                );
            }
            return array_values(array_intersect($availableTypes, $requestedTypes));
        }
    }

    /**
     * Get available log types
     *
     * @return array
     */
    abstract public function getAvailableType();

    /**
     * Is enable log or not
     *
     * @return int
     */
    abstract protected function isEnable();
}
