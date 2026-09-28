<?php
declare(strict_types=1);

namespace Magestore\Logger\Console\Command\LogType;

use Magestore\Logger\Console\Command\AbstractLogTypeChangeStatusCommand;

/**
 * Class DisableLogStatus
 *
 * Use to Disable log type class
 */
class DisableLogType extends AbstractLogTypeChangeStatusCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('magestore:log:types:disable');
        $this->setDescription('Disables Magestore Logs Type(s)');
        parent::configure();
    }

    /**
     * Is disable log type
     *
     * @return int
     */
    protected function isEnable()
    {
        return 0;
    }
}
