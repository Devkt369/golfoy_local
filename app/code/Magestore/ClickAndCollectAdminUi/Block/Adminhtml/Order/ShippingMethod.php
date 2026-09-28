<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Block\Adminhtml\Order;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Helper\Admin;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Sales\Model\Order;
use Magestore\ClickAndCollect\Model\Carrier\Method;
use Magestore\ClickAndCollectApi\Api\GetPickUpInformationInterface;
use Magestore\ClickAndCollectApi\Api\Order\IsEnoughQtyInStockInterface;

/**
 * Class ShippingMethod
 *
 * Render picking information
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingMethod extends AbstractOrder
{
    const MAGENTO_SHIPPING_INFO_TEMPLATE = 'Magento_Shipping::order/view/info.phtml';

    const MAGESTORE_STORE_PICKUP_INFO_TEMPLATE = 'Magestore_ClickAndCollectAdminUi::order/view/info.phtml';

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var IsEnoughQtyInStockInterface
     */
    protected $isEnoughQtyInStock;

    /**
     * @var GetPickUpInformationInterface
     */
    protected $getPickUpInformation;

    /**
     * ShippingMethod constructor.
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param LocationRepositoryInterface $locationRepository
     * @param IsEnoughQtyInStockInterface $isEnoughQtyInStock
     * @param GetPickUpInformationInterface $getPickUpInformation
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        LocationRepositoryInterface $locationRepository,
        IsEnoughQtyInStockInterface $isEnoughQtyInStock,
        GetPickUpInformationInterface $getPickUpInformation,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        $this->locationRepository = $locationRepository;
        $this->getPickUpInformation = $getPickUpInformation;
        $this->isEnoughQtyInStock = $isEnoughQtyInStock;
        $data['shippingHelper'] = $shippingHelper ?? ObjectManager::getInstance()->get(ShippingHelper::class);
        $data['taxHelper'] = $taxHelper ?? ObjectManager::getInstance()->get(TaxHelper::class);
        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * Set template to render html (when shipping method is store pickup)
     *
     * @return ShippingMethod
     * @throws LocalizedException
     */
    public function _prepareLayout()
    {
        $order = $this->getOrder();
        /* Change shipping method template for only Click and Collect shipping method */
        if ($order->getShippingMethod() == Method::CLICK_AND_COLLECT_SHIPPING_METHOD) {
            $this->setTemplate(self::MAGESTORE_STORE_PICKUP_INFO_TEMPLATE);
        } else {
            $this->setTemplate(self::MAGENTO_SHIPPING_INFO_TEMPLATE);
        }
        return parent::_prepareLayout();
    }

    /**
     * Get pick up store in the order
     *
     * @return LocationInterface|string
     */
    public function getPickUpStore()
    {
        try {
            $order = $this->getOrder();
            $locationPickupId = $order->getLocationPickupId();
            $location = $this->locationRepository->getById($locationPickupId);
            if ($location->getLocationId()) {
                return $location;
            }
        } catch (LocalizedException $exception) {
            return '';
        }
        return '';
    }

    /**
     * Get store address of location
     *
     * @param LocationInterface $store
     * @return string
     */
    public function getStoreAddress(LocationInterface $store)
    {
        return implode(
            ', ',
            array_filter(
                [
                    $store->getStreet(),
                    $store->getCity(),
                    $store->getRegion(),
                    $store->getCountry(),
                    $store->getPostcode(),
                ]
            )
        );
    }

    /**
     * Get pickup information of location
     *
     * @param Order $order
     * @return string
     */
    public function getPickUpInformation(Order $order)
    {
        $timeToPick = $this->getPickUpInformation->execute($order, true);
        if ($timeToPick) {
            return __('Customer want to collect at ') . '<strong>' . $timeToPick . '</strong>';
        } else {
            return '';
        }
    }

    /**
     * Get pickup status to display
     *
     * @param LocationInterface $store
     * @return Phrase|string
     */
    public function getPickUpStatus(LocationInterface $store)
    {
        try {
            $order = $this->getOrder();
            if ($order->getPickupStatus() === OrderPickupStatusResolverInterface::STATUS_PENDING
                && !$this->isEnoughQtyInStock->execute($order, $store)
            ) {
                return __('There are some out of stock items in that order, please check it.');
            } else {
                return $this->getPickUpInformation($order);
            }
        } catch (LocalizedException $exception) {
            return '';
        }
    }
}
