<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Cms\Api\Data\PageInterface;
use Magestore\Rewardpoints\Helper\Config as RewardPointConfig;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Class GetRewardPointWelcomePage
 *
 * Get reward point welcome in reward
 */
class GetRewardPointWelcomePage implements ResolverInterface
{
    const XML_ENABLE = 'enable';
    const XML_ALLOW_WELCOME = 'show_welcome_page';
    const XML_WELCOME_CMS = 'welcome_page';

    /**
     * @var RewardPointConfig
     */
    private $config;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * GetRewardPointPolicy constructor.
     *
     * @param RewardPointConfig $config
     * @param PageRepositoryInterface $pageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        RewardPointConfig $config,
        PageRepositoryInterface $pageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->config = $config;
        $this->pageRepository = $pageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentStore = $context->getExtensionAttributes()->getStore();

        if (!(int) $this->config->getGeneralConfig(self::XML_ENABLE, $currentStore->getId())
            || !(int) $this->config->getGeneralConfig(self::XML_ALLOW_WELCOME, $currentStore->getId())) {
            throw new LocalizedException(
                __("Welcome page is disabled.")
            );
        }

        $welcomePage = $this->config->getGeneralConfig(self::XML_WELCOME_CMS);
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            PageInterface::IDENTIFIER,
            $welcomePage,
            'eq'
        )->setPageSize(1)->setCurrentPage(1)->create();
        $cmsPageList = $this->pageRepository->getList($searchCriteria)->getItems();
        if (count($cmsPageList)) {
            return current($cmsPageList);
        } else {
            throw new LocalizedException(
                __("Can not find welcome page.")
            );
        }
    }
}
