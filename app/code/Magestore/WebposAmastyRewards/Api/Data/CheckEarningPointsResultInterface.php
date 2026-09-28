<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Api\Data;

/**
 * WebposAmastyRewards - CheckEarningPointsResultInterface
 */
interface CheckEarningPointsResultInterface
{
    const QUOTE_ID = 'quote_id';
    const VALID_RULES = 'valid_rules';

    /**
     * Get Quote Id
     *
     * @return int|null
     */
    public function getQuoteId();

    /**
     * Set Quote Id
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId($quoteId);

    /**
     * Get Valid Rules
     *
     * @return array|null
     */
    public function getValidRules();

    /**
     * Set Valid Rules
     *
     * @param array $validRules
     * @return $this
     */
    public function setValidRules(array $validRules = []);
}
