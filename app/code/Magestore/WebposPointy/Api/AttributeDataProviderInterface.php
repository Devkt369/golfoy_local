<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Get data provider for product attribute
 */
interface AttributeDataProviderInterface
{
    /**
     * Get data provider for product attribute
     *
     * @param array $attributeCodes
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $attributeCodes): array;
}
