<?php
declare(strict_types=1);

namespace Magestore\Logger\Console\Command\LogType;

use Magestore\Logger\Console\Command\AbstractLogTypeChangeStatusCommand;

/**
 * Class EnableLogType
 *
 * Use to enable log type
 */
class EnableLogType extends AbstractLogTypeChangeStatusCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('magestore:log:types:enable');
        $this->setDescription('Enables Magestore Logs Type(s)');
        parent::configure();
    }

    /**
     * Is enable log type
     *
     * @return int
     */
    protected function isEnable()
    {
        return 1;
    }
}
