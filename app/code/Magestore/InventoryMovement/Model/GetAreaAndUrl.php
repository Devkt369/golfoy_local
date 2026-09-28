<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magestore\InventoryMovementApi\Api\GetAreaAndUrlInterface;

/**
 * Get area and url for request
 *
 * Class GetAreaAndUrl
 */
class GetAreaAndUrl implements GetAreaAndUrlInterface
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * GetAreaAndUrl constructor.
     *
     * @param State $appState
     * @param RequestInterface $request
     */
    public function __construct(
        State $appState,
        RequestInterface $request
    ) {
        $this->appState = $appState;
        $this->request = $request;
    }

    /**
     * Execute service
     *
     * @return array
     */
    public function execute(): array
    {
        $url = '';
        try {
            $areaCode = $this->appState->getAreaCode();
            if ($areaCode == 'adminhtml') {
                $url = $this->request->getFullActionName('/');
            } else {
                $url = preg_replace('/^\//', '', $this->request->getPathInfo());
            }
        } catch (\Exception $exception) {
            return [
                'area' => 'undefined',
                'url' => $url
            ];
        }

        return [
            'area' => $areaCode,
            'url' => $url
        ];
    }
}
