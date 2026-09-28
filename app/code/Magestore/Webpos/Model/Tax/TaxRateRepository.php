<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Tax;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\TaxRateTitleInterface;
use Magento\Tax\Model\Calculation\RateRepository;
use Magento\Tax\Model\ResourceModel\Calculation\Rate\Collection;
use Magestore\Webpos\Api\Tax\TaxRateRepositoryInterface;

/**
 * Class TaxRateRepository
 *
 * Get tax rates
 */
class TaxRateRepository extends RateRepository implements TaxRateRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getAllTaxRates()
    {
        $objectManager = ObjectManager::getInstance();

        /* @var Collection $collection */
        $collection = $objectManager->create(Collection::class);
        $collection->setModel(TaxRate::class);
        /* @var StoreManagerInterface $StoreManager */
        $StoreManager = $objectManager->create(StoreManagerInterface::class);
        $collection->joinRegionTable();
        $collection->joinTitle();

        $taxRates = [];

        /* @var TaxRate $taxRateModel */
        foreach ($collection as $taxRateModel) {
            if ($taxRateModel->getTitle()) {
                /* @var TaxRateTitleInterface $taxTitle */
                $taxTitle = $objectManager->create(TaxRateTitleInterface::class);
                $taxTitle->setValue($taxRateModel->getTitle());
                $taxTitle->setStoreId($StoreManager->getStore()->getId());
                $taxRateModel->setTitles([$taxTitle]);
            } else {
                $taxRateModel->setTitles([]);
            }
            if (!$taxRateModel->getTaxPostcode()) {
                $taxRateModel->setTaxPostcode('*');
            }
            $taxRates[] = $taxRateModel;
        }

        return $taxRates;
    }
}
