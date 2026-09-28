<?php
declare(strict_types=1);

namespace Magestore\Logger\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magestore\Logger\Model\Source\LogFlows;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetLogFlowStatusCommand
 *
 * Used to get disable/enable log flow status types
 */
class GetLogFlowStatusCommand extends Command
{
    const CONFIG_PATH_LOG_STATUS_FLOWS = 'magestore/log/status/flows';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LogFlows
     */
    protected $logFlows;

    /**
     * GetLogFlowStatusCommand constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param LogFlows $logFlows
     * @param string|null $name
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LogFlows $logFlows,
        string $name = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logFlows = $logFlows;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('magestore:log:flows:status');
        $this->setDescription('Get status enable/disable Magestore Logs Flow(s)');
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
        $output->writeln('Get log flow status: ');
        $logFlowsAll = $this->logFlows->getAllLogFlows();
        $config = $this->scopeConfig->getValue(
            self::CONFIG_PATH_LOG_STATUS_FLOWS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        if ($config) {
            $configKey = array_keys($config);
        } else {
            $configKey = [];
        }
        foreach ($logFlowsAll as $flow) {
            if (!in_array($flow, $configKey)) {
                $config[$flow] = (int) $this->scopeConfig->getValue(
                    AbstractLogStatusCommand::CONFIG_PATH_DEFAULT_STATUS,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
            }
        }
        foreach ($config as $flow => $status) {
            $output->writeln(sprintf('%30s: %d', $flow, $status));
        }
    }
}
