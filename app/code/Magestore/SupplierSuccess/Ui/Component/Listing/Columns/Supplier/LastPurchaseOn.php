<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\SupplierSuccess\Ui\Component\Listing\Columns\Supplier;

use DateTime;
use Exception;
use IntlDateFormatter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class LastPurchaseOn
 *
 * To convert date for purchase on
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LastPurchaseOn extends Column
{
    /**
     * @var Manager
     */
    protected $moduleManager;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * LastPurchaseOn constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param Manager $moduleManager
     * @param ProductMetadataInterface $productMetadata
     * @param array $components
     * @param array $data
     * @param ResolverInterface|null $localeResolver
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        Manager $moduleManager,
        ProductMetadataInterface $productMetadata,
        array $components = [],
        array $data = [],
        ResolverInterface $localeResolver = null
    ) {
        $this->timezone = $timezone;
        $this->moduleManager = $moduleManager;
        $this->productMetadata = $productMetadata;
        $this->localeResolver = $localeResolver ?: ObjectManager::getInstance()
            ->get(ResolverInterface::class);
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Datasource
     *
     * @param array $dataSource
     * @return array
     * @throws LocalizedException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * Prepare Item
     *
     * @param array $item
     * @return string
     * @throws LocalizedException
     * @throws Exception
     */
    protected function prepareItem(array $item): string
    {
        $date = $item[$this->getData('name')];

        if (empty($date)) {
            return '';
        }
        $locale = $this->localeResolver->getLocale();
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        return $formatter->format(new DateTime($date));
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws LocalizedException
     * @throws LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        if (!$this->moduleManager->isOutputEnabled('Magestore_PurchaseOrderSuccess')) {
            $this->_data['config']['component'] = false;
        }
    }
}
