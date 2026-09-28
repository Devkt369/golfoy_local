<?php
declare(strict_types=1);

namespace Magestore\Logger\Console\Command\LogFlow;

use Magestore\Logger\Console\Command\AbstractLogFlowChangeStatusCommand;

/**
 * Class EnableLogFlow
 *
 * Use to enable log flow
 */
class EnableLogFlow extends AbstractLogFlowChangeStatusCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('magestore:log:flows:enable');
        $this->setDescription('Enables Magestore Logs Flows(s)');
        parent::configure();
    }

    /**
     * Is enable log flow
     *
     * @return int
     */
    protected function isEnable()
    {
        return 1;
    }
}
