<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Model\Customer;

use Amasty\Rewards\Api\RewardsRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterfaceFactory;
use Magento\Framework\App\ObjectManager;
use Magestore\Webpos\Api\Customer\CustomerRepositoryInterface;
use Magestore\Webpos\Api\Data\Customer\CustomerInterface;
use Magestore\Webpos\Api\Data\Customer\CustomerSearchResultsInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * WebposAmastyRewards - CustomerRepositoryPlugin
 */
class CustomerRepositoryPlugin
{
    /**
     * @var CustomerExtensionInterfaceFactory
     */
    protected $customerExtension;

    /**
     * @var RewardsRepositoryInterface
     */
    protected $rewardsRepository;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param CustomerExtensionInterfaceFactory $customerExtension
     * @param Data $helper
     */
    public function __construct(
        CustomerExtensionInterfaceFactory $customerExtension,
        Data $helper
    ) {
        $this->customerExtension = $customerExtension;
        $this->helper = $helper;
        if ($this->helper->isEnableAmastyRewards()) {
            $this->rewardsRepository =
                ObjectManager::getInstance()->get(RewardsRepositoryInterface::class); // phpstan:ignore
        }
    }

    /**
     * After Save
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CustomerRepositoryInterface $subject, $result)
    {
        return $this->processDataResult($result);
    }

    /**
     * After Get List
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerSearchResultsInterface $result
     * @return CustomerSearchResultsInterface|[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(CustomerRepositoryInterface $subject, $result)
    {
        return $this->processSearchResultsData($result);
    }

    /**
     * After Search
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerSearchResultsInterface $result
     * @return CustomerSearchResultsInterface|[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSearch(CustomerRepositoryInterface $subject, $result)
    {
        return $this->processSearchResultsData($result);
    }

    /**
     * After Get By Id
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $result
     * @return CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetById(CustomerRepositoryInterface $subject, $result)
    {
        return $this->processDataResult($result);
    }

    /**
     * Process Search Results Data
     *
     * @param CustomerSearchResultsInterface|array $result
     * @return CustomerSearchResultsInterface|array
     */
    public function processSearchResultsData($result)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $result;
        }
        if (is_array($result) && isset($result['cached_at'])) {
            $items = $result['items'];

            foreach ($items as &$item) {
                $extensionAttributes = isset($item['extension_attributes']) ? $item['extension_attributes'] : [];

                $extensionAttributes['amasty_reward_balance'] = $this->getCustomerPointBalance($item);
                $item['extension_attributes'] = $extensionAttributes;
            }

            $result['items'] = $items;
            return $result;
        } else {
            $items = $result->getItems();

            foreach ($items as &$item) {
                $extensionAttributes = $item->getExtensionAttributes();
                if (!$extensionAttributes) {
                    $extensionAttributes = $this->customerExtension->create();
                }

                $extensionAttributes->setAmastyRewardBalance($this->getCustomerPointBalance($item));
                $item->setExtensionAttributes($extensionAttributes);
            }

            return $result->setItems($items);
        }
    }

    /**
     * Process Data Result
     *
     * @param CustomerInterface $result
     * @return CustomerInterface
     */
    public function processDataResult($result)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $result;
        }
        $extensionAttributes = $result->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->customerExtension->create();
        }

        $extensionAttributes->setAmastyRewardBalance($this->getCustomerPointBalance($result));
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }

    /**
     * Get Customer Point Balance
     *
     * @param CustomerInterface $item
     * @return float
     */
    public function getCustomerPointBalance($item)
    {
        if (!$this->helper->isEnableAmastyRewards()) {
            return 0;
        }
        if (is_array($item)) {
            $id = $item['id'];
        } else {
            $id = $item->getId();
        }
        return $this->rewardsRepository->getCustomerRewardBalance((int)$id);
    }
}
