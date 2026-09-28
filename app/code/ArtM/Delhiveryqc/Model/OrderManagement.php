<?php
declare(strict_types=1);
namespace ArtM\Delhiveryqc\Model;

class OrderManagement implements \ArtM\Delhiveryqc\Api\OrderManagementInterface
{

    public function getorderdetailsforqc(array $packageIds)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create(\ArtM\Delhiveryqc\Helper\Data::class);
        $packageId = implode(', ', $packageIds);

        $packageid_details = $helper->GetProductsDataByPackageId($packageId);
        return $packageid_details;
    }
}