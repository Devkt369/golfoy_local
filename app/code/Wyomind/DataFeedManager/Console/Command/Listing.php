<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * $ bin/magento help wyomind:datafeedmanager:list
 * Usage:
 * wyomind:datafeedmanager:list
 *
 * Options:
 * --help (-h)           Display this help message
 * --quiet (-q)          Do not output any message
 * --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 * --version (-V)        Display this application version
 * --ansi                Force ANSI output
 * --no-ansi             Disable ANSI output
 * --no-interaction (-n) Do not ask any interactive question
 */
class Listing extends Command
{

    /**
     * @var null|\Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory
     */
    protected $_feedsCollectionFactory = null;
    /**
     * @var \Magento\Framework\App\State|null
     */
    protected $_state = null;
    /**
     * @var null|\Wyomind\DataFeedManager\Helper\DataFactory
     */
    protected $_dataHelperFactory = null;

    /**
     * Listing constructor.
     * @param \Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory $feedsCollectionFactory
     * @param \Wyomind\DataFeedManager\Helper\DataFactory $dataHelperFactory
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Wyomind\DataFeedManager\Model\ResourceModel\Feeds\CollectionFactory $feedsCollectionFactory,
        \Wyomind\DataFeedManager\Helper\DataFactory $dataHelperFactory,
        \Magento\Framework\App\State $state
    ) {
    

        $this->_state = $state;
        $this->_feedsCollectionFactory = $feedsCollectionFactory;
        $this->_dataHelperFactory = $dataHelperFactory;
        parent::__construct();
    }

    /**
     *
     */
    protected function configure()
    {
        $this->setName('wyomind:datafeedmanager:list')
            ->setDescription(__('Data Feed Manager : get list of available feeds'))
            ->setDefinition([]);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
    


        try {
            try {
                $this->_state->setAreaCode('adminhtml');
            } catch (\Exception $e) {
            }
            $collection = $this->_feedsCollectionFactory->create();
            foreach ($collection as $feed) {
                $row = sprintf(
                    "%-6d %-45s %-22s %s",
                    $feed->getId(),
                    $feed->getPath() . $feed->getName() . $this->_dataHelperFactory->create()->getExtFromType($feed->getType()),
                    $feed->getUpdatedAt(),
                    $feed->getStatus() ? __("Enabled") : __("Disabled")
                );
                $output->writeln($row);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $output->writeln($e->getMessage());
            $returnValue = \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $returnValue = \Magento\Framework\Console\Cli::RETURN_FAILURE;

        return $returnValue;
    }
}
