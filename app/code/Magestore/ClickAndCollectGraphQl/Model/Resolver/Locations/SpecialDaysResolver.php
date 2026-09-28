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
use Magestore\ClickAndCollectApi\Api\GetLocationSpecialDaysInterface;
use Psr\Log\LoggerInterface;

/**
 * Location's special days field resolver, used for GraphQL request processing.
 */
class SpecialDaysResolver implements ResolverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GetLocationSpecialDaysInterface
     */
    protected $getLocationSpecialDays;

    /**
     * SpecialDaysResolver constructor.
     * @param LoggerInterface $logger
     * @param GetLocationSpecialDaysInterface $getLocationSpecialDays
     */
    public function __construct(
        LoggerInterface $logger,
        GetLocationSpecialDaysInterface $getLocationSpecialDays
    ) {
        $this->logger = $logger;
        $this->getLocationSpecialDays = $getLocationSpecialDays;
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
        return $this->getLocationSpecialDays->execute($location);
    }
}
