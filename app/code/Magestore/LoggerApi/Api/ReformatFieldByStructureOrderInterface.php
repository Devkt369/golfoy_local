<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface ReformatFieldByStructureOrderInterface
 *
 * Reformat Field By Structure Order Service Interface
 * @api
 */
interface ReformatFieldByStructureOrderInterface
{
    /**
     * Execute service
     *
     * @param array $message
     * @param string $type
     * @return array
     */
    public function execute(array $message, string $type): array;
}
