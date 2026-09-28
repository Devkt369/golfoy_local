<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Locations;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Location's special days field resolver, used for GraphQL request processing.
 */
class TimezoneResolver implements ResolverInterface
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * TimezoneResolver constructor.
     *
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$value || !$value['model']) {
            return [];
        }
        $location = $value['model'];
        $storeId = $location->getStoreId();
        return $this->timezone->getConfigTimezone(
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
