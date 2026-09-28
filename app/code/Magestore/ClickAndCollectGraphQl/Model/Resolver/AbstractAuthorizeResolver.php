<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\AuthorizationInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;
use Magento\Framework\Webapi\Request;

/**
 * Class AbstractAuthorizeResolver
 *
 * Authorize class to manage role and permission
 */
abstract class AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = '';

    const MAGESTORE_ROLE_RESOURCE = '';

    const POS_SESSION_HEADER = 'pos-session';

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var StaffManagementInterface
     */
    private $staffManagement;

    /**
     * @var Request
     */
    private $request;

    /**
     * AbstractAuthorizeResolver constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request
    ) {
        $this->authorization = $authorization;
        $this->staffManagement = $staffManagement;
        $this->request = $request;
    }

    /**
     * Fetches the data from persistence models and format it according to the GraphQL schema.
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $sessionId = $this->request->getHeader(self::POS_SESSION_HEADER);
        if (!$this->authorization->isAllowed(static::ALLOWED_RESOURCE)
            || !$this->staffManagement->isAllowResourceBySessionId(
                static::MAGESTORE_ROLE_RESOURCE,
                $sessionId
            )
        ) {
            throw new GraphQlAuthorizationException(__('You don\'t have permission !'));
        }
    }
}
