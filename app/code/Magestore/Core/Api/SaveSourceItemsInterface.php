<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Api;

/**
 * Save multi source items from array data
 */
interface SaveSourceItemsInterface
{
    /**
     * Save source items
     *
     * @param array $sourceItems [[sku, source_code, quantity, status = null], ...]
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute(array $sourceItems);
}
