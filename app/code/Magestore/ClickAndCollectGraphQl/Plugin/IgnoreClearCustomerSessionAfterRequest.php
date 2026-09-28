<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\GraphQl\Controller\GraphQl as GraphQlController;
use Magento\CustomerGraphQl\Plugin\ClearCustomerSessionAfterRequest;

/**
 * Clear the user data out of the session object before returning the GraphQL response
 */
class IgnoreClearCustomerSessionAfterRequest
{
    /**
     * @var array
     */
    protected $whiteList;

    /**
     * IgnoreClearCustomerSessionAfterRequest constructor.
     *
     * @param array $whiteList
     */
    public function __construct(
        array $whiteList = []
    ) {
        $this->whiteList = $whiteList;
    }

    /**
     * Plugin to ignore in white list
     *
     * @param ClearCustomerSessionAfterRequest $subject
     * @param callable $proceed
     * @param GraphQlController $controller
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @return ResponseInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAfterDispatch(
        ClearCustomerSessionAfterRequest $subject, // phpstan:ignore
        callable $proceed,
        GraphQlController $controller,
        ResponseInterface $response,
        RequestInterface $request
    ): ResponseInterface {
        $query = $request->getContent();
        $isIgnore = false;
        if ($query) {
            foreach ($this->whiteList as $queryWhiteList) {
                if (strpos($query, $queryWhiteList) !== false) {
                    $isIgnore = true;
                }
            }
        }
        if ($isIgnore) {
            return $response;
        } else {
            return $proceed($controller, $response);
        }
    }
}
