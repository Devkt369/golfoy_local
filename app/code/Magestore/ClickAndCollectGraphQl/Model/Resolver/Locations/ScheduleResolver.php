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
use Magestore\ClickAndCollectApi\Api\DataProvider\GetScheduleByIdInterface;
use Psr\Log\LoggerInterface;

/**
 * Location's schedule field resolver, used for GraphQL request processing.
 */
class ScheduleResolver implements ResolverInterface
{
    /**
     * @var GetScheduleByIdInterface
     */
    protected $getScheduleById;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ScheduleResolver constructor.
     * @param GetScheduleByIdInterface $getScheduleById
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetScheduleByIdInterface $getScheduleById,
        LoggerInterface $logger
    ) {
        $this->getScheduleById = $getScheduleById;
        $this->logger = $logger;
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
            return null;
        }
        $location = $value['model'];
        $scheduleId = $location->getScheduleId();
        if (!$scheduleId) {
            return null;
        }
        try {
            return $this->getScheduleById->execute((int)$scheduleId);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }
}
