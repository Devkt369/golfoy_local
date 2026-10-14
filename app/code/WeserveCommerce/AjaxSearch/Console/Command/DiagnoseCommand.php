<?php
namespace WeserveCommerce\AjaxSearch\Console\Command;

use WeserveCommerce\AjaxSearch\Api\SearchServiceInterface;
use WeserveCommerce\AjaxSearch\Model\Config;
use WeserveCommerce\AjaxSearch\Api\ProductIndexResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class DiagnoseCommand extends Command
{
    /** @var SearchServiceInterface */
    private $searchService;

    /** @var Config */
    private $config;

    /** @var ProductIndexResolverInterface */
    private $indexResolver;

    protected function configure()
    {
        $this->setName('fastsearch:diagnose')
            ->setDescription('Diagnose Fast Search configuration and OpenSearch connectivity');
    }

    public function __construct(
        SearchServiceInterface $searchService,
        Config $config,
        ProductIndexResolverInterface $indexResolver
    ) {
        $this->searchService = $searchService;
        $this->config = $config;
        $this->indexResolver = $indexResolver;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storeId = $this->config->getCurrentStoreId();
        $output->writeln('<info>Fast Search Diagnostic for Store ID: ' . $storeId . '</info>');
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Setting', 'Value']);
        $table->addRow(['Enabled', $this->config->isEnabled($storeId) ? 'Yes' : 'No']);
        $table->addRow(['Autocomplete Enabled', $this->config->isAutocompleteEnabled($storeId) ? 'Yes' : 'No']);
        $table->addRow(['Search Engine', $this->config->getSearchEngine($storeId)]);
        $table->addRow(['OpenSearch Host', $this->config->getOpenSearchHost($storeId)]);
        $table->addRow(['OpenSearch Port', $this->config->getOpenSearchPort($storeId)]);
        $table->addRow(['Index Prefix', $this->config->getOpenSearchIndexPrefix($storeId)]);
        $indexPattern = $this->indexResolver->resolve($storeId);
        $table->addRow(['Resolved Index Pattern', $indexPattern]);
        $table->render();

        // Test connectivity by performing a lightweight request (HEAD) to the resolved index pattern
        $client = new \GuzzleHttp\Client([
            'base_uri' => $this->config->getOpenSearchHost($storeId) . ':' . $this->config->getOpenSearchPort($storeId),
            'timeout' => $this->config->getOpenSearchTimeout($storeId)
        ]);
        try {
            $response = $client->request('GET', '/' . $indexPattern . '/_search', [
                'query' => ['size' => 0]
            ]);
            $status = $response->getStatusCode();
            $output->writeln("<info>OpenSearch connectivity test succeeded (HTTP $status).</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>OpenSearch connectivity test failed: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
?>
