<?php
declare(strict_types=1);

namespace Magestore\Logger\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Logger\Model\Source\LogFlows;
use Magestore\Logger\Model\Structure\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetLogTypeStatusCommand
 *
 * Used to get disable/enable log flow status
 */
class GetLogTypeStatusCommand extends Command
{
    const CONFIG_PATH_LOG_STATUS_TYPES = 'magestore/log/status/types';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LogFlows
     */
    protected $logFlows;

    /**
     * @var Config
     */
    protected $config;

    /**
     * GetLogTypeStatusCommand constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LogFlows $logFlows
     * @param Config $config
     * @param string|null $name
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LogFlows $logFlows,
        Config $config,
        string $name = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logFlows = $logFlows;
        $this->config = $config;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('magestore:log:types:status');
        $this->setDescription('Get status enable/disable Magestore Logs Types(s)');
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Get log types status: ');
        $logTypeAll = array_keys($this->config->getAllType());
        $config = $this->scopeConfig->getValue(
            self::CONFIG_PATH_LOG_STATUS_TYPES,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if ($config) {
            $configKey = array_keys($config);
        } else {
            $configKey = [];
        }
        foreach ($logTypeAll as $type) {
            if (!in_array($type, $configKey)) {
                $config[$type] = (int) $this->scopeConfig->getValue(
                    AbstractLogStatusCommand::CONFIG_PATH_DEFAULT_STATUS,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
            }
        }
        foreach ($config as $type => $status) {
            $output->writeln(sprintf('%30s: %d', $type, $status));
        }
    }
}
