<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\PurchaseOrderSuccess\Ui\Component\Listing\Columns;

use IntlDateFormatter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class to remove time
 *
 * Class Date.
 */
class Date extends Column
{
    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Date constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $localeDate
     * @param ResolverInterface|null $localeResolver
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $localeDate,
        ResolverInterface $localeResolver,
        array $components = [],
        array $data = []
    ) {
        $this->_localeDate = $localeDate;
        $this->localeResolver = $localeResolver;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Datasource
     *
     * @param array $dataSource
     * @return array
     * @throws LocalizedException
     */
    public function prepareDataSource(array $dataSource): array
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
     * @throws \Exception
     */
    protected function prepareItem(array $item): string
    {
        $date = $item[$this->getData('name')];

        if (empty($date)) {
            return '';
        }
        $locale = $this->localeResolver->getLocale();
        $formatter = new IntlDateFormatter($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        return $formatter->format(new \DateTime($date));
    }
}
