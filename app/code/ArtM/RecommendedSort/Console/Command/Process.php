<?php
declare(strict_types=1);

namespace ArtM\RecommendedSort\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Process extends Command
{
    private const ARG_NAME = 'name';

    protected function configure(): void
    {
        $this->setName('artm_recommendedsort:process');
        $this->setDescription('Process selected categories in background');
        $this->addArgument(
            self::ARG_NAME,
            InputArgument::OPTIONAL,
            'Your name or identifier'
        );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument(self::ARG_NAME) ?? 'Anonymous';

        $output->writeln("<info>✅ Processing started for: $id</info>");

        if(is_numeric($id)) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $helper = $objectManager->get(\ArtM\RecommendedSort\Helper\Data::class);
            $helper->updateForSingleBatch($id);
        }
        // Check Symfony's built-in verbosity
        if ($output->isVerbose()) {
            $output->writeln("<comment>Verbose mode is ON</comment>");
        }

        $output->writeln("<info>🎉 Done processing for: $id</info>");

        return Command::SUCCESS;
    }
}
