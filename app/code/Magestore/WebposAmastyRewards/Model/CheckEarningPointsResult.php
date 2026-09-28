<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Model;

use Magento\Framework\DataObject;
use Magestore\WebposAmastyRewards\Api\Data\CheckEarningPointsResultInterface;

/**
 * WebposAmastyRewards - CheckEarningPointsResult
 */
class CheckEarningPointsResult extends DataObject implements CheckEarningPointsResultInterface
{

    /**
     * @inheritDoc
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getValidRules()
    {
        return $this->getData(self::VALID_RULES);
    }

    /**
     * @inheritDoc
     */
    public function setValidRules(array $validRules = [])
    {
        return $this->setData(self::VALID_RULES, $validRules);
    }
}
