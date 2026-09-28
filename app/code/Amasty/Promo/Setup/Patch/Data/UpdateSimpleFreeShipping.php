<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Setup\Patch\Data;

use Amasty\Promo\Observer\Salesrule\Discount;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SalesRule\Model\Data\Rule;

class UpdateSimpleFreeShipping implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function apply(): UpdateSimpleFreeShipping
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->update(
            $this->resourceConnection->getTableName('salesrule'),
            [Rule::KEY_SIMPLE_FREE_SHIPPING => 0],
            [Rule::KEY_SIMPLE_ACTION . ' IN(?) ' => Discount::PROMO_RULES]
        );

        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [];
    }
}
