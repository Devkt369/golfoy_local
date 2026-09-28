<?php

namespace ArtM\EstimateDelivery\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ArtM\EstimateDelivery\Helper\Data as EstimateHelper;

class EstimateDelivery implements ResolverInterface
{
    protected $helper;

    public function __construct(EstimateHelper $helper)
    {
        $this->helper = $helper;
    }

    public function resolve(
        $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['sku'])) {
            throw new GraphQlInputException(__('SKU is required.'));
        }

        // Call your function
        $result = $this->helper->getEstimatedDeliveryDate($args['sku']);

        return [
            'dispatch_days'            => $result['dispatch_days'],
            'delivery_days'            => $result['delivery_days'],
            'total_days'               => $result['total_days'],
            'estimated_delivery_date'  => $result['estimated_delivery_date'],
            'source_code'              => $result['source_code'],
            'group'                    => $result['group'],
            'express_shipping'         => $result['express_shipping']
        ];
    }
}
