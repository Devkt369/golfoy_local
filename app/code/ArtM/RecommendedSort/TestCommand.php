<?php
namespace ArtM\RecommendedSort;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected function configure()
    {
        $this->setName('artm:test')
            ->setDescription('Test Command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Test successful.');
        return 0;
    }
}
