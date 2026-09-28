<?php

namespace Magestore\Webpos\Console\Command;

use Magestore\Webpos\Api\Console\WebposUpdateRoleInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Role Update Command Class
 */
class RoleUpdateCommand extends Command
{
    protected $webposUpdateRoleService;

    /**
     * Constructor
     *
     * @param WebposUpdateRoleInterface $webposUpdateRole
     */
    public function __construct(WebposUpdateRoleInterface $webposUpdateRole)
    {
        parent::__construct();
        $this->webposUpdateRoleService = $webposUpdateRole;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('role:update');
        $this->setDescription('Update roles resource to fix missing Magestore rules resource');
        parent::configure();
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->webposUpdateRoleService->execute($output);
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
        }
    }
}
