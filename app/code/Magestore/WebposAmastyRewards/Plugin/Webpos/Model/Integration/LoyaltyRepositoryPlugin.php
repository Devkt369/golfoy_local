<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Integration;

use Amasty\Rewards\Api\RewardsRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterfaceFactory;
use Magento\Framework\App\ObjectManager;
use Magestore\Webpos\Api\Data\Customer\CustomerInterface;
use Magestore\Webpos\Api\Data\Customer\CustomerSearchResultsInterface;
use Magestore\Webpos\Api\Integration\LoyaltyRepositoryInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - LoyaltyRepositoryPlugin
 */
class LoyaltyRepositoryPlugin
{
    /**
     * @var CustomerExtensionInterfaceFactory
     */
    protected $customerExtensionFactory;

    /**
     * @var RewardsRepositoryInterface
     */
    protected $rewardsRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param CustomerExtensionInterfaceFactory $customerExtensionFactory
     * @param Data $helper
     */
    public function __construct(
        CustomerExtensionInterfaceFactory $customerExtensionFactory,
        Data $helper
    ) {
        $this->customerExtensionFactory = $customerExtensionFactory;
        $this->helper = $helper;
        if ($this->helper->isEnableAmastyRewards()) {
            $this->rewardsRepository =
                ObjectManager::getInstance()->get(RewardsRepositoryInterface::class); // phpstan:ignore
        }
    }

    /**
     * After Get List
     *
     * @param LoyaltyRepositoryInterface $subject
     * @param CustomerSearchResultsInterface $result
     * @return CustomerSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(LoyaltyRepositoryInterface $subject, $result)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $result;
        }
        $items = $result->getItems();

        foreach ($items as $item) {
            $extensionAttributes = $item->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = $this->customerExtensionFactory->create();
            }
            $extensionAttributes->setAmastyRewardBalance($this->getCustomerPointBalance($item));
            $item->setExtensionAttributes($extensionAttributes);
        }

        return $result->setItems($items);
    }

    /**
     * Get Customer Point Balance
     *
     * @param CustomerInterface $item
     * @return float
     */
    public function getCustomerPointBalance($item)
    {
        return $this->rewardsRepository->getCustomerRewardBalance($item->getId());
    }
}
