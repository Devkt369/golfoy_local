<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Column product sku
 */
class ProductSku extends Column
{
    /**
     * @var string
     */
    protected $_editUrl = 'catalog/product/edit';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item[InventoryMovementInterface::PRODUCT_ID])) {
                    $productSku = $item[$name];
                    $productId = $item[InventoryMovementInterface::PRODUCT_ID];
                    $url = $this->urlBuilder->getUrl($this->_editUrl, ['id' => $productId]);
                    $item[$name] = '<a href="' . $url . '">' . $productSku . '</a>';
                }
            }
        }

        return $dataSource;
    }
}
