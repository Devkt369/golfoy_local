<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magestore\ClickAndCollectMigrate\Api\Console\MigrateServiceInterface;

/**
 * Migrate store pickup command
 */
class MigrateCommand extends Command
{

    /**
     * @var MigrateServiceInterface
     */
    protected $migrateService;

    /**
     * Migrate Command Constructor
     *
     * @param MigrateServiceInterface $migrateService
     */
    public function __construct(
        MigrateServiceInterface $migrateService
    ) {
        $this->migrateService = $migrateService;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('clickandcollect:migrate-storepickup')
            ->setDescription('Migrate data from Store Pickup to Click And Collect')
            ->setDefinition([
                new InputOption(
                    'manual',
                    'm',
                    InputOption::VALUE_NONE,
                    'Manually created the mapping between stores and locations'
                ),
                new InputOption(
                    'ignore-unmapped-store',
                    'i',
                    InputOption::VALUE_NONE,
                    "Ignore stores that are not mapped with any stock"
                ),
                new InputOption(
                    'force',
                    'f',
                    InputOption::VALUE_NONE,
                    'Force to re-migrate data from Store Pickup to Click And Collect'
                )
            ]);
        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $force = $input->getOption('force');
            $manual = $input->getOption('manual');
            $ignoreUnmappedStore = $input->getOption('ignore-unmapped-store');
            $this->migrateService->migrate($output, $manual, $ignoreUnmappedStore, $force);
            return Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }
    }
}
