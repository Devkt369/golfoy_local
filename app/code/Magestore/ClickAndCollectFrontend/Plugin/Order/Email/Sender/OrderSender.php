<?php
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Plugin\Order\Email\Sender;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magestore\ClickAndCollectApi\Api\DataProvider\CheckIsClickAndCollectOrderInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\GetPickUpInformationInterface;
use Magestore\ClickAndCollectApi\Api\GoogleMap\GetMapImageFromPositionInterface;

/**
 * Class OrderSender used for plugin before send email
 */
class OrderSender
{
    /**
     * @var CheckIsClickAndCollectOrderInterface
     */
    private $checkIsClickAndCollectOrder;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var GetPickUpInformationInterface
     */
    private $getPickUpInformation;

    /**
     * @var GetMapImageFromPositionInterface
     */
    private $getMapImageFromPosition;

    /**
     * OrderSender constructor.
     *
     * @param CheckIsClickAndCollectOrderInterface $checkIsClickAndCollectOrder
     * @param LocationRepositoryInterface $locationRepository
     * @param GetPickUpInformationInterface $getPickUpInformation
     * @param GetMapImageFromPositionInterface $getMapImageFromPosition
     */
    public function __construct(
        CheckIsClickAndCollectOrderInterface $checkIsClickAndCollectOrder,
        LocationRepositoryInterface $locationRepository,
        GetPickUpInformationInterface $getPickUpInformation,
        GetMapImageFromPositionInterface $getMapImageFromPosition
    ) {
        $this->checkIsClickAndCollectOrder = $checkIsClickAndCollectOrder;
        $this->locationRepository = $locationRepository;
        $this->getPickUpInformation = $getPickUpInformation;
        $this->getMapImageFromPosition = $getMapImageFromPosition;
    }
    /**
     * Function beforeSend
     *
     * @param Order\Email\Sender\OrderSender $orderSender
     * @param Order $order
     * @param bool $forceSyncMode
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSend(
        Order\Email\Sender\OrderSender $orderSender,
        Order $order,
        $forceSyncMode = false
    ) {
        if ($order->getLocationPickupId()) {
            $this->checkIsClickAndCollectOrder->setIsClickAndCollectOrder(true);
            $information = $this->getStoreInformation((int) $order->getLocationPickupId());
            $order->setPickTime($this->getPickUpInformation->execute($order, true));
            $mapInformation =   $this->getMapImageFromPosition->execute(
                (string) $information['lat'],
                (string) $information['lng']
            );
            $order->setMapImage(
                $mapInformation['image']
            );
            $order->setMapUrl(
                $mapInformation['url']
            );
            if (count($information)) {
                $order->setClickAndCollectInfo(
                    new DataObject($information)
                );
            } else {
                $order->setClickAndCollectInfo(null);
            }
        }
        return [$order, $forceSyncMode];
    }

    /**
     * Get store information
     *
     * @param int $storeId
     * @return array
     */
    public function getStoreInformation(int $storeId)
    {
        $location = $this->locationRepository->getById($storeId);
        if ($location->getLocationId()) {
            $locationAddress = [
                $location->getStreet(),
                $location->getCity(),
                $location->getRegion(),
                $location->getCountry(),
                $location->getPostcode()
            ];
            return [
                'store_name' => $location->getName(),
                'email' => $location->getEmail(),
                'phone' => $location->getTelephone(),
                'lat' => $location->getLatitude(),
                'lng' => $location->getLongitude(),
                'address' => implode(', ', array_filter($locationAddress))
            ];
        } else {
            return [];
        }
    }
}
