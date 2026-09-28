<?php
declare(strict_types=1);

namespace Magestore\Logger\Console\Command\LogFlow;

use Magestore\Logger\Console\Command\AbstractLogFlowChangeStatusCommand;

/**
 * Class DisableLogFlow
 *
 * Use to Disable log flow class
 */
class DisableLogFlow extends AbstractLogFlowChangeStatusCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('magestore:log:flows:disable');
        $this->setDescription('Disables Magestore Logs Flow(s)');
        parent::configure();
    }

    /**
     * Is disable log flow
     *
     * @return int
     */
    protected function isEnable()
    {
        return 0;
    }
}
