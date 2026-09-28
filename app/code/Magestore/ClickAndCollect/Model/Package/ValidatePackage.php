<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollect\Model\Carrier\Method;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\ValidatePackageInterface;

/**
 * Class Validate Package
 *
 * Service to validate package
 */
class ValidatePackage implements ValidatePackageInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * ValidatePackage constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(PackageInterface $package)
    {
        $result = [
            'error' => false,
            'error_message' => ''
        ];
        try {
            $order = $this->orderRepository->get($package->getOrderId());
            if ($order->getShippingMethod() != Method::CLICK_AND_COLLECT_SHIPPING_METHOD) {
                $result['error'] = true;
                $result['error_message'] = 'You only can create package for click & collect order!';
            }
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['error_message'] = 'The order does not exist!';
        }
        return $result;
    }
}
