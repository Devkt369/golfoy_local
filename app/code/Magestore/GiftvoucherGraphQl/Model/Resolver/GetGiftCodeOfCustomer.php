<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Giftvoucher\Api\Data\GiftvoucherInterface;
use Magestore\Giftvoucher\Helper\Data as DataHelper;
use Magestore\Giftvoucher\Api\GiftvoucherRepositoryInterface;

/**
 * Fetch customer reward points
 */
class GetGiftCodeOfCustomer implements ResolverInterface
{
    const MAGESTORE_GIFTVOUCHER_GENERAL_ACTIVE = 'giftvoucher/general/active';

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var GiftvoucherRepositoryInterface
     */
    private $giftvoucherRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * CustomerRewardPoints constructor.
     *
     * @param DataHelper $dataHelper
     * @param CustomerRegistry $customerRegistry
     * @param GiftvoucherRepositoryInterface $giftvoucherRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        DataHelper $dataHelper,
        CustomerRegistry $customerRegistry,
        GiftvoucherRepositoryInterface $giftvoucherRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->dataHelper = $dataHelper;
        $this->customerRegistry = $customerRegistry;
        $this->giftvoucherRepository = $giftvoucherRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $currentStoreId = (int)$context->getExtensionAttributes()->getStore()->getId();

        if (!(int) $this->dataHelper->getStoreConfig(self::MAGESTORE_GIFTVOUCHER_GENERAL_ACTIVE, $currentStoreId)) {
            return 0;
        }

        /** @var Customer $customer */
        $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$currentCustomer && !$currentCustomer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }

        $this->searchCriteriaBuilder->addFilter(GiftvoucherInterface::CUSTOMER_ID, $currentCustomer->getId());
        $giftvoucherSearchResults = $this->giftvoucherRepository->getList($this->searchCriteriaBuilder->create());
        return $giftvoucherSearchResults->getItems();
    }
}
