<?php
namespace ArtM\GraphqlCache\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use ArtM\GraphqlCache\Model\Cache\GraphqlCache;

class CacheCommand extends Command
{
    const COMMAND_NAME = 'artm:graphql:cache';
    
    protected $graphqlCache;

    public function __construct(
        GraphqlCache $graphqlCache,
        string $name = null
    ) {
        $this->graphqlCache = $graphqlCache;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Manage ArtM GraphQL Cache')
            ->addOption(
                'clean',
                'c',
                InputOption::VALUE_NONE,
                'Clean GraphQL cache'
            )
            ->addOption(
                'status',
                's',
                InputOption::VALUE_NONE,
                'Show cache status and statistics'
            )
            ->addOption(
                'test',
                't',
                InputOption::VALUE_NONE,
                'Test cache functionality'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('clean')) {
            $this->graphqlCache->clean();
            $output->writeln('<info>GraphQL cache cleaned successfully.</info>');
            return 0;
        }

        if ($input->getOption('test')) {
            $output->writeln('<info>Testing GraphQL Cache...</info>');
            
            try {
                // Test direct cache operations
                $testKey = GraphqlCache::CACHE_PREFIX . 'test_' . time();
                $testData = ['test' => 'data', 'timestamp' => time()];
                
                // Test save
                $output->writeln('Testing cache save...');
                $saved = $this->graphqlCache->getCacheInstance()->save(
                    json_encode($testData),
                    $testKey,
                    [GraphqlCache::CACHE_TYPE],
                    GraphqlCache::TTL
                );
                
                if ($saved) {
                    $output->writeln('<info>✓ Cache save test passed</info>');
                } else {
                    $output->writeln('<error>✗ Cache save test failed</error>');
                    return 1;
                }
                
                // Test load
                $output->writeln('Testing cache load...');
                $cached = $this->graphqlCache->getCacheInstance()->load($testKey);
                
                if ($cached !== false) {
                    $output->writeln('<info>✓ Cache load test passed</info>');
                    $output->writeln('Cached data: ' . $cached);
                    
                    // Clean up test data
                    $this->graphqlCache->getCacheInstance()->remove($testKey);
                    $output->writeln('<info>✓ Test cache cleaned</info>');
                } else {
                    $output->writeln('<error>✗ Cache load test failed</error>');
                    return 1;
                }
                
                $output->writeln('<info>All cache tests passed!</info>');
                
            } catch (\Exception $e) {
                $output->writeln('<error>Cache test failed: ' . $e->getMessage() . '</error>');
                return 1;
            }
            
            return 0;
        }

        if ($input->getOption('status')) {
            $output->writeln('<info>GraphQL Cache Status:</info>');
            $output->writeln('Cache Type: ' . GraphqlCache::CACHE_TYPE);
            $output->writeln('Cache TTL: ' . GraphqlCache::TTL . ' seconds');
            $output->writeln('Cache Prefix: ' . GraphqlCache::CACHE_PREFIX);
            $output->writeln('');
            $output->writeln('<comment>Check var/log/system.log for cache hit/miss statistics</comment>');
            return 0;
        }

        $output->writeln('<info>ArtM GraphQL Cache Management</info>');
        $output->writeln('Available options:');
        $output->writeln('  --clean   Clean GraphQL cache');
        $output->writeln('  --status  Show cache status');
        $output->writeln('  --test    Test cache functionality');
        
        return 0;
    }
}