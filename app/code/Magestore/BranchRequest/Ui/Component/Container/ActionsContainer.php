<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\BranchRequest\Ui\Component\Container;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Ui\Component\Container;

/**
 * Component - Actions Container
 */
class ActionsContainer extends Container
{
    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $authorization = ObjectManager::getInstance()->get(AuthorizationInterface::class);
        if (!$authorization->isAllowed('Magestore_BranchRequest::save')) {
            $this->_data['config']['componentDisabled'] = true;
        }
        parent::prepare();
    }
}
