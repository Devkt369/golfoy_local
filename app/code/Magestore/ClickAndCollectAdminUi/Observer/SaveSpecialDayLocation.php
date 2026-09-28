<?php

/**
 * Magestore.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_ClickAndCollectAdminHtml
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\ClickAndCollectAdminUi\Observer;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magestore\ClickAndCollect\Model\LocationSpecialDayLinkFactory;
use Magestore\ClickAndCollect\Model\LocationSpecialDayLink;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink\DeleteMultiple;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink\SaveMultiple;
use Magestore\ClickAndCollectApi\Api\GetLocationSpecialDayLinksInterface;

/**
 * Model SaveSpecialDayLocation
 */
class SaveSpecialDayLocation implements ObserverInterface
{
    /**
     * Used for create object special day link
     *
     * @var LocationSpecialDayLinkFactory $locationSpecialDayLinkFactory
     */
    protected $locationSpecialDayLinkFactory;

    /**
     * Used for create object special day link
     *
     * @var DeleteMultiple $deleteMultiple
     */
    protected $deleteMultiple;

    /**
     * Used for create object special day link
     *
     * @var SaveMultiple $saveMultiple
     */
    protected $saveMultiple;

    /**
     * @var GetLocationSpecialDayLinksInterface
     */
    protected $getLocationSpecialDayLinks;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * SaveSpecialDayLocation constructor.
     *
     * @param GetLocationSpecialDayLinksInterface $getLocationSpecialDayLinks
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param LocationSpecialDayLinkFactory $locationSpecialDayLinkFactory
     * @param DeleteMultiple $deleteMultiple
     * @param SaveMultiple $saveMultiple
     */
    public function __construct(
        GetLocationSpecialDayLinksInterface $getLocationSpecialDayLinks,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        LocationSpecialDayLinkFactory $locationSpecialDayLinkFactory,
        DeleteMultiple $deleteMultiple,
        SaveMultiple $saveMultiple
    ) {
        $this->getLocationSpecialDayLinks = $getLocationSpecialDayLinks;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->locationSpecialDayLinkFactory = $locationSpecialDayLinkFactory;
        $this->deleteMultiple = $deleteMultiple;
        $this->saveMultiple = $saveMultiple;
    }

    /**
     * Function execute event
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $data = $observer->getData('data');
        $locationId = $observer->getData('locationId');
        $specialDayIds = [];

        if (isset($data['click_and_collect']) && isset($data['click_and_collect']['session_special_days'])) {
            $dataSpecialDaysLinks = $data['click_and_collect']['session_special_days']
            ['special_days_dynamic_grid']['links']['special_days_list'];

            foreach ($dataSpecialDaysLinks as $dataSpecialDaysLink) {
                $specialDayIds[] = $dataSpecialDaysLink['id'];
            }
        }

        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter(LocationSpecialDayLink::LOCATION_ID, $locationId)
            ->create();
        $links = $this->getLocationSpecialDayLinks->execute($searchCriteria);
        $this->deleteMultiple->execute($links->getItems());
        $locationSpecialDayLinks = [];
        foreach ($specialDayIds as $specialDayId) {
            $locationSpecialDayLink = $this->locationSpecialDayLinkFactory->create();
            $locationSpecialDayLink->setLocationId($locationId);
            $locationSpecialDayLink->setSpecialDayId($specialDayId);
            $locationSpecialDayLinks[] = $locationSpecialDayLink;
        }
        $this->saveMultiple->execute($locationSpecialDayLinks);
    }
}
