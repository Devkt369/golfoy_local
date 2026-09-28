<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * WebposAmastyRewards - Helper
 */
class Data extends AbstractHelper
{
    /**
     * Is Enable Amasty Rewards
     *
     * @return bool
     */
    public function isEnableAmastyRewards()
    {
        return $this->_moduleManager->isEnabled('Amasty_Rewards');
    }
}
