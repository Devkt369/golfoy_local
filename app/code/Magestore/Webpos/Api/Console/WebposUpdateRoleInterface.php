<?php

namespace Magestore\Webpos\Api\Console;

interface WebposUpdateRoleInterface
{
    const MAGESTORE_ROLE_SEARCH = 'Magestore%';

    /**
     * Execute
     *
     * @param mixed $output
     * @return mixed
     */
    public function execute($output);
}
