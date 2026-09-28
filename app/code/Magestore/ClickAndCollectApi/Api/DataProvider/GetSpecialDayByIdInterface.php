<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\DataProvider;

/**
 * Get special day by id data provider
 *
 * @api
 */
interface GetSpecialDayByIdInterface
{
    /**
     * Get special day data by given id.
     *
     * @param int $specialDayId
     * @return \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(int $specialDayId): \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
}
