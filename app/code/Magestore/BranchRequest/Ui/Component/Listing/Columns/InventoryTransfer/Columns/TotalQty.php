<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\Component\Listing\Columns\InventoryTransfer\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 * Class BranchRequest TotalQty
 */
class TotalQty extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var WarehouseFactory
     */
    protected $_warehouseFactory;
    /**
     * @var \Magestore\BranchRequest\Model\Source\Adminhtml\Source
     */
    protected $source;

    /**
     * TotalQty construct
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magestore\BranchRequest\Model\Source\Adminhtml\Source $source
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                                       $context,
        UiComponentFactory                                     $uiComponentFactory,
        UrlInterface                                           $urlBuilder,
        \Magento\Framework\App\RequestInterface                $request,
        \Magestore\BranchRequest\Model\Source\Adminhtml\Source $source,
        array                                                  $components = [],
        array                                                  $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->source = $source;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
}
