<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\OrderSuccess\Model\DataProvider;

use Magento\Framework\DataObject;
use Magestore\OrderSuccess\Api\DataProvider\IsGettingStateForOrderInterface;

/**
 * Data provider: IsGettingStateForOrder
 */
class IsGettingStateForOrder extends DataObject implements IsGettingStateForOrderInterface
{

    /**
     * @inheritDoc
     */
    public function setIsGettingStateForOrder(int $isGettingStateForOrder): void
    {
        $this->setData(self::IS_GETTING_STATE_FOR_ORDER, $isGettingStateForOrder);
    }

    /**
     * @inheritDoc
     */
    public function getIsGettingStateForOrder(): int
    {
        return $this->getData(self::IS_GETTING_STATE_FOR_ORDER) ? 1 : 0;
    }
}
