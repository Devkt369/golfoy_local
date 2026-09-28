<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Class LocationTypeResolver
 *
 * Resolve location type for schema
 */
class LocationTypeResolver implements TypeResolverInterface
{
    /**
     * Resolve type
     *
     * @param array $data
     * @return string
     * @throws GraphQlInputException
     */
    public function resolveType(array $data) : string
    {
        return isset($data['distance']) ? 'LocationWithDistance' : 'Location';
    }
}
