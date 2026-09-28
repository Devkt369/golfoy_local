<?php
/**
 * Copyright © 2021 Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Plugin\MessageQueue\Model\Cron;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\MessageQueue\ConnectionTypeResolver;
use Magento\Framework\MessageQueue\Consumer\Config\ConsumerConfigItemInterface;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface as ConsumerConfigInterface;
use Magento\Framework\ShellInterface;
use Magestore\Webpos\Api\LogHandle\ProcessLogExceptionInterface;
use Magestore\Webpos\Model\Checkout\PosOrder\Consumer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Magento\MessageQueue\Model\Cron\ConsumersRunner as CoreConsumersRunner;
use Magestore\Webpos\Helper\PosOrder as PosOrderHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Add multiple thread for message queue
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConsumersRunner
{
    /**
     * Shell command line wrapper for executing command in background
     *
     * @var ShellInterface
     */
    private $shellBackground;

    /**
     * Consumer config provider
     *
     * @var ConsumerConfigInterface
     */
    private $consumerConfig;

    /**
     * Application deployment configuration
     *
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * The executable finder specifically designed for the PHP executable
     *
     * @var PhpExecutableFinder
     */
    private $phpExecutableFinder;

    /**
     * @var ConnectionTypeResolver
     */
    private $mqConnectionTypeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Lock Manager
     *
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @var PosOrderHelper
     */
    private $posOrderHelper;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * ConsumersRunner constructor
     *
     * @param PhpExecutableFinder $phpExecutableFinder The executable finder specifically designed
     *        for the PHP executable
     * @param ConsumerConfigInterface $consumerConfig The consumer config provider
     * @param DeploymentConfig $deploymentConfig The application deployment configuration
     * @param ShellInterface $shellBackground The shell command line wrapper for executing command in background
     * @param LockManagerInterface $lockManager The lock manager
     * @param ConnectionTypeResolver $mqConnectionTypeResolver Consumer connection resolver
     * @param LoggerInterface $logger Logger
     * @param PosOrderHelper $posOrderHelper
     * @param ObjectManagerInterface $objectManager
     * @param ProductMetadataInterface $productMetadata
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PhpExecutableFinder $phpExecutableFinder,
        ConsumerConfigInterface $consumerConfig,
        DeploymentConfig $deploymentConfig,
        ShellInterface $shellBackground,
        LockManagerInterface $lockManager,
        ConnectionTypeResolver $mqConnectionTypeResolver,
        LoggerInterface $logger,
        PosOrderHelper $posOrderHelper,
        ObjectManagerInterface $objectManager,
        ProductMetadataInterface $productMetadata
    ) {
        $this->phpExecutableFinder = $phpExecutableFinder;
        $this->consumerConfig = $consumerConfig;
        $this->deploymentConfig = $deploymentConfig;
        $this->shellBackground = $shellBackground;
        $this->lockManager = $lockManager;
        $this->mqConnectionTypeResolver = $mqConnectionTypeResolver;
        $this->logger = $logger;
        $this->posOrderHelper = $posOrderHelper;
        $this->objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Runs consumers processes
     *
     * @param CoreConsumersRunner $subject
     * @param \Closure $proceed
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\RuntimeException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function aroundRun(CoreConsumersRunner $subject, \Closure $proceed)
    {
        $version = $this->productMetadata->getVersion();
        // Only support multiple thread for magento 2.3.5 and above
        if (version_compare($version, '2.3.4', '<=') || $version === 'No version set (parsed as 1.0.0)') {
            return $proceed();
        }
        $runByCron = $this->deploymentConfig->get('cron_consumers_runner/cron_run', true);

        if (!$runByCron) {
            return;
        }

        $maxMessages = (int) $this->deploymentConfig->get('cron_consumers_runner/max_messages', 10000);
        $allowedConsumers = $this->deploymentConfig->get('cron_consumers_runner/consumers', []);
        $php = $this->phpExecutableFinder->find() ?: 'php';

        foreach ($this->consumerConfig->getConsumers() as $consumer) {
            if (!$this->canBeRun($consumer, $allowedConsumers)) {
                continue;
            }

            // Run multiple thread for Magestore consumer
            // And only allow multiple thread if config consumers_wait_for_messages = 0
            if ($consumer->getName() == Consumer::QUEUE_CONSUMER_NAME
                && !$this->isWaitingNextMessage()
                && $this->posOrderHelper->getNumberOfThreadWorker() > 1) {
                $this->executeMultipleThread(
                    $php,
                    $maxMessages,
                    $consumer,
                    $this->posOrderHelper->getNumberOfThreadWorker()
                );
            } else {
                $this->executeStartConsumerCommand($php, $maxMessages, $consumer);
            }
        }
    }

    /**
     * Execute Multiple Thread
     *
     * @param string $php
     * @param int $maxMessages
     * @param ConsumerConfigItemInterface $consumer
     * @param int $numberOfThread
     */
    public function executeMultipleThread(
        string $php,
        int $maxMessages,
        ConsumerConfigItemInterface $consumer,
        int $numberOfThread
    ) {
        $i = 1;
        while ($i <= $numberOfThread) {
            if ($i == 1) {
                $this->executeStartConsumerCommand($php, $maxMessages, $consumer);
            } else {
                $this->executeStartConsumerCommand($php, $maxMessages, $consumer, false);
            }
            $i++;
        }
    }

    /**
     * Execute Start Consumer Command
     *
     * @param string $php
     * @param int $maxMessages
     * @param ConsumerConfigItemInterface $consumer
     * @param bool $isSingeThread
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function executeStartConsumerCommand(
        string $php,
        int $maxMessages,
        ConsumerConfigItemInterface $consumer,
        bool $isSingeThread = true
    ) {
        $arguments = [
            $consumer->getName()
        ];
        if ($isSingeThread) {
            $arguments[] = '--single-thread';
        }

        if ($maxMessages) {
            $arguments[] = '--max-messages=' . min($consumer->getMaxMessages() ?? $maxMessages, $maxMessages);
        }

        $command = $php . ' ' . BP . '/bin/magento queue:consumers:start %s'
            . ($isSingeThread ? ' %s' : '')
            . ($maxMessages ? ' %s' : '');

        $this->shellBackground->execute($command, $arguments);
    }

    /**
     * Checks that the consumer can be run
     *
     * @param ConsumerConfigItemInterface $consumerConfig The consumer config
     * @param array $allowedConsumers The list of allowed consumers
     *        If $allowedConsumers is empty it means that all consumers are allowed
     * @return bool Returns true if the consumer can be run
     * @throws \Magento\Framework\Exception\FileSystemException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function canBeRun(ConsumerConfigItemInterface $consumerConfig, array $allowedConsumers = []): bool
    {
        $consumerName = $consumerConfig->getName();
        if (!empty($allowedConsumers) && !in_array($consumerName, $allowedConsumers)) {
            return false;
        }

        if ($this->lockManager->isLocked(md5($consumerName))) { //phpcs:ignore
            return false;
        }

        $connectionName = $consumerConfig->getConnection();
        try {
            $this->mqConnectionTypeResolver->getConnectionType($connectionName);
        } catch (\LogicException $e) {
            /* @var ProcessLogExceptionInterface $loggerException */
            $loggerException = $this->objectManager->get(ProcessLogExceptionInterface::class);
            $loggerException->execute(
                'place_order',
                'place_order',
                sprintf(
                    'Consumer "%s" skipped as required connection "%s" is not configured. %s',
                    $consumerName,
                    $connectionName,
                    $e->getMessage()
                ),
                'critical',
                $e
            );
            return false;
        }

        $version = $this->productMetadata->getVersion();
        // Only have that config on magento 2.4.1 and above
        if (version_compare($version, '2.4.1', '>=') || $version === 'No version set (parsed as 1.0.0)') {
            $globalOnlySpawnWhenMessageAvailable = (bool)$this->deploymentConfig->get(
                'queue/only_spawn_when_message_available',
                true
            );
            if ($consumerConfig->getOnlySpawnWhenMessageAvailable() === true
                || ($consumerConfig->getOnlySpawnWhenMessageAvailable() === null
                    && $globalOnlySpawnWhenMessageAvailable)) {
                try {
                    $checkIsAvailableMessages = $this->objectManager->create(
                        'Magento\MessageQueue\Model\CheckIsAvailableMessagesInQueue' // phpcs:ignore
                    );
                    return $checkIsAvailableMessages->execute(
                        $connectionName,
                        $consumerConfig->getQueue()
                    );
                } catch (\LogicException $e) {
                    /* @var ProcessLogExceptionInterface $loggerException */
                    $loggerException = $this->objectManager->get(ProcessLogExceptionInterface::class);
                    $loggerException->execute(
                        'place_order',
                        'place_order',
                        sprintf(
                            'Consumer "%s" skipped as its related queue "%s" is not available. %s',
                            $consumerName,
                            $consumerConfig->getQueue(),
                            $e->getMessage()
                        ),
                        'critical',
                        $e
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks if consumers should wait for message from the queue
     *
     * @return bool
     * @see \Magento\Framework\MessageQueue\CallbackInvoker::isWaitingNextMessage
     */
    private function isWaitingNextMessage(): bool
    {
        return $this->deploymentConfig->get('queue/consumers_wait_for_messages', 1) === 1;
    }
}
