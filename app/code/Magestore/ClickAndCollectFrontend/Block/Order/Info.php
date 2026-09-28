<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Block\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Block\Order\Info as CoreInfo;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magestore\ClickAndCollect\Model\Carrier\Method;
use Magestore\ClickAndCollectApi\Api\DataProvider\CheckIsClickAndCollectOrderInterface;
use Magestore\ClickAndCollectApi\Api\GetPickUpInformationInterface;
use Magestore\ClickAndCollectApi\Api\GoogleMap\GetMapImageFromPositionInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;

/**
 * Class Info
 *
 * Use for add information about click and collect
 */
class Info extends CoreInfo
{
    const MAGENTO_ORDER_INFO_TEMPLATE = 'Magento_Sales::order/info.phtml';

    const MAGESTORE_ORDER_INFO_TEMPLATE = 'Magestore_ClickAndCollectFrontend::order/info.phtml';

    /**
     * @var CheckIsClickAndCollectOrderInterface
     */
    protected $checkIsClickAndCollectOrder;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var GetPickUpInformationInterface
     */
    protected $getPickUpInformation;

    /**
     * @var GetMapImageFromPositionInterface
     */
    protected $getMapImageFromPosition;

    /**
     * Info constructor.
     *
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param CheckIsClickAndCollectOrderInterface $checkIsClickAndCollectOrder
     * @param LocationRepositoryInterface $locationRepository
     * @param GetPickUpInformationInterface $getPickUpInformation
     * @param GetMapImageFromPositionInterface $getMapImageFromPosition
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        CheckIsClickAndCollectOrderInterface $checkIsClickAndCollectOrder,
        LocationRepositoryInterface $locationRepository,
        GetPickUpInformationInterface $getPickUpInformation,
        GetMapImageFromPositionInterface $getMapImageFromPosition,
        array $data = []
    ) {
        $this->checkIsClickAndCollectOrder = $checkIsClickAndCollectOrder;
        $this->locationRepository = $locationRepository;
        $this->getPickUpInformation = $getPickUpInformation;
        $this->getMapImageFromPosition = $getMapImageFromPosition;
        parent::__construct($context, $registry, $paymentHelper, $addressRenderer, $data);
    }

    /**
     * Set template to render html (when shipping method is store pickup)
     *
     * @return void
     */
    public function _prepareLayout()
    {
        $order = $this->getOrder();
        /* Change shipping method template for only Click and Collect shipping method */
        if ($order->getShippingMethod() == Method::CLICK_AND_COLLECT_SHIPPING_METHOD) {
            $this->setTemplate(self::MAGESTORE_ORDER_INFO_TEMPLATE);
        } else {
            $this->setTemplate(self::MAGENTO_ORDER_INFO_TEMPLATE);
        }
        parent::_prepareLayout();
    }

    /**
     * Get store pickup information
     *
     * @return array
     */
    public function getStorePickupInformation()
    {
        $order = $this->getOrder();
        if ($order->getLocationPickupId()) {
            $location = $this->locationRepository->getById($order->getLocationPickupId());
            if ($location->getLocationId()) {
                $locationAddress = [
                    $location->getStreet(),
                    $location->getCity(),
                    $location->getRegion(),
                    $location->getCountry(),
                    $location->getPostcode()
                ];
                $storeInformation = [
                    'store_name' => $location->getName(),
                    'phone' => $location->getTelephone(),
                    'lat' => $location->getLatitude(),
                    'lng' => $location->getLongitude(),
                    'address' => implode(', ', array_filter($locationAddress))
                ];
                $storeInformation['pick_time'] = $this->getPickUpInformation->execute($order, true);
                $mapInformation =   $this->getMapImageFromPosition->execute(
                    (string) $storeInformation['lat'],
                    (string) $storeInformation['lng']
                );
                $storeInformation['image'] = $mapInformation['image'];
                $storeInformation['url'] = $mapInformation['url'];
                return $storeInformation;
            }
        }
        return [];
    }
}
