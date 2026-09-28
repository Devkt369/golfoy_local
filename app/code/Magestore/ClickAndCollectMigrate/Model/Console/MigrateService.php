<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\Console;

use Magestore\ClickAndCollectMigrate\Api\Console\MigrateServiceInterface;
use Magestore\ClickAndCollectMigrate\Api\Data\LogInterface;
use Magestore\ClickAndCollectMigrate\Api\Data\LogInterfaceFactory;
use Magestore\ClickAndCollectMigrate\Api\LogRepositoryInterface;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\MigrateData;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Click and collect - Migrate service
 */
class MigrateService implements MigrateServiceInterface
{
    /**
     * @var LogInterfaceFactory
     */
    protected $logFactory;

    /**
     * @var LogRepositoryInterface
     */
    protected $logRepository;

    /**
     * @var MigrateData
     */
    protected $migrateData;

    /**
     * MigrateService constructor.
     * @param LogInterfaceFactory $logFactory
     * @param LogRepositoryInterface $logRepository
     * @param MigrateData $migrateData
     */
    public function __construct(
        LogInterfaceFactory $logFactory,
        LogRepositoryInterface $logRepository,
        MigrateData $migrateData
    ) {
        $this->logFactory = $logFactory;
        $this->logRepository = $logRepository;
        $this->migrateData = $migrateData;
    }

    /**
     * @inheritDoc
     */
    public function migrate(
        OutputInterface $output,
        bool $manual = false,
        bool $ignoreUnmappedStore = false,
        bool $force = false
    ) {
        if (!$force && count($this->logRepository->getAllLog())) {
            // Check for migrated
            $output->writeln('Data was migrated from Store Pickup to Click And Collect');
            $output->writeln('To re-migrate data, please append -f or --force to your command');
            return;
        }

        $startTime = microtime(true);
        /* Process migrate data and return result message to save log */
        $resultMessage = $this->migrateData->execute($manual, $ignoreUnmappedStore);
        /** @var LogInterface $log */
        $log = $this->logFactory->create();
        $log->setMessage($resultMessage);
        $this->logRepository->save($log);
        $resultTime = microtime(true) - $startTime;
        $resultTime = round($resultTime, 2).'s';
        $output->writeln('Data of Store Pickup has been migrated to Click And Collect successfully!');
        $output->writeln('Execution time : '.$resultTime);
    }
}
