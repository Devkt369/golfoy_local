<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\BranchRequest\Ui\Component\Container;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Ui\Component\Container;

/**
 * Component - Barcode Container
 */
class BarcodeContainer extends Container
{
    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $moduleManager = ObjectManager::getInstance()->get(ModuleManager::class);
        if (!$moduleManager->isEnabled('Magestore_BarcodeSuccess')) {
            $this->_data['config']['componentDisabled'] = true;
        }
        parent::prepare();
    }
}
