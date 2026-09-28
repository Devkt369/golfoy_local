<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\AdjustStock\Ui\Component\Listing\Columns\AdjustStock\Columns;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class Barcode
 *
 * Used to add scroll to barcode
 */
class Barcode extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Barcode constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_storeManager = $storeManager;
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add scroll to field barcode
     *
     * @param string $html
     * @return string
     */
    public function addScrollToField($html)
    {
        return '<div style="max-height: 85px;overflow-y: auto;">
            '.$html.'
        </div>';
    }

    /**
     * Prepare data source for barcode
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $moduleManager = ObjectManager::getInstance()->get(ModuleManager::class);
        if ($moduleManager->isEnabled('Magestore_BarcodeSuccess') && isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {

                $poArray = [];
                if ($item[$fieldName]) {
                    $item[$fieldName] = explode(',', $item[$fieldName]);
                    foreach ($item[$fieldName] as $barcode) {
                        $poArray[] = $barcode;
                    }
                    $item[$fieldName] = $this->addScrollToField(implode('</br>', $poArray));
                }
            }
        }
        return $dataSource;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws LocalizedException
     */
    public function prepare()
    {
        $moduleManager = ObjectManager::getInstance()->get(ModuleManager::class);
        if (!$moduleManager->isEnabled('Magestore_BarcodeSuccess')) {
            $this->_data['config']['componentDisabled'] = true;
        }
        parent::prepare();
    }
}
