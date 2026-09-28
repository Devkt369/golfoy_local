<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Rest;

use Magestore\WebposIntegration\Controller\Rest\RequestProcessor as RequestProcessorCore;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Class RequestProcessor
 *
 * Plugin to change resource
 */
class RequestProcessor extends RequestProcessorCore
{
    const CLICK_AND_COLLECT_ACCESS_ACL = 'Magestore_ClickAndCollectAdminUi::click_and_collect';

    const CLICK_AND_COLLECT_PREFIX_URL = 'click-and-collect';

    /**
     * Around can process to change resource
     *
     * @param RequestProcessorCore $requestProcessorCore
     * @param \Closure $proceed
     * @param Request $request
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCanProcess(
        RequestProcessorCore $requestProcessorCore,
        \Closure $proceed,
        Request $request
    ) : bool {
        try {
            $route = $this->inputParamsResolver->getRoute();
            $routeUrl = $route->getRoutePath();
            $aclResource = $route->getAclResources();
            if (strpos($routeUrl, self::CLICK_AND_COLLECT_PREFIX_URL) !== false
                && in_array(self::CLICK_AND_COLLECT_ACCESS_ACL, $aclResource)) {
                return true;
            } else {
                return $proceed($request);
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
