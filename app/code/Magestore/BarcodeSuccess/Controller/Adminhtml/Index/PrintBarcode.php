<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BarcodeSuccess\Controller\Adminhtml\Index;

use Datetime;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magestore\BarcodeSuccess\Block\Barcode\Container\Template;
use Magestore\BarcodeSuccess\Controller\Adminhtml\AbstractIndex;
use Magestore\BarcodeSuccess\Helper\Data;
use Magestore\BarcodeSuccess\Model\Locator\LocatorInterface;
use Magestore\BarcodeSuccess\Model\ResourceModel\Barcode\Collection;
use Magestore\BarcodeSuccess\Api\Data\BarcodeTemplateInterface;

/**
 * Class PrintBarcode
 *
 * Used for print barcode
 * @SuppressWarnings(PHPMD)
 */
class PrintBarcode extends AbstractIndex implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magestore_BarcodeSuccess::print_barcode';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var TemplateResource
     */
    protected $templateResource;
    /**
     * @var
     */
    protected $filter;

    protected $likeCondition = ['barcode', 'product_sku', 'supplier_code'];
    protected $dateCondition = ['purchased_time'];
    protected $rangeCondition = ['id'];

    /**
     * PrintBarcode constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $data
     * @param LocatorInterface $locator
     * @param JsonFactory $resultJsonFactory
     * @param \Magestore\BarcodeSuccess\Model\ResourceModel\Template $templateResource
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $data,
        LocatorInterface $locator,
        JsonFactory $resultJsonFactory,
        \Magestore\BarcodeSuccess\Model\ResourceModel\Template $templateResource,
        Filter $filter
    ) {
        parent::__construct($context, $resultPageFactory, $data, $locator);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->templateResource = $templateResource;
        $this->filter = $filter;
    }

    /**
     * Function execute
     *
     * @return Page
     */
    public function execute()
    {
        $defaultTemplateId = $this->helper->getStoreConfig('barcodesuccess/general/default_barcode_template');
        $resultJson = $this->resultJsonFactory->create();
        if (!$this->helper->isLaminasBarcodeInstalled()) {
            $resultJson->setData([
                'messages' =>
                    __('Unable to Print Barcode. Require ')
                    . '<a href="' . $this->helper->getBarcodeSetupGuideUrl() .'">Laminas_Barcode.</a>',
                'error' => true
            ]);

            return $resultJson;
        }

        $templateId = $this->getRequest()->getParam('type', $defaultTemplateId);
        $printQty = $this->getRequest()->getParam('qty');
        $excluded = $this->getRequest()->getParam(Filter::EXCLUDED_PARAM);
        $selectedBarcodeIds = $this->getRequest()->getParam(Filter::SELECTED_PARAM);
        $barcodeCollection = $this->helper->getModel(Collection::class);
        if ($this->getRequest()->getParam('filters')) {
            $filters = $this->getRequest()->getParam('filters');
            foreach ($filters as $key => $filter) {
                if (in_array($key, $this->likeCondition)) {
                    $barcodeCollection->addFieldToFilter($key, ['like' => '%' . $filter . '%']);
                } elseif (in_array($key, $this->dateCondition)) {
                    if (isset($filter['from'])) {
                        $barcodeCollection->addFieldToFilter(
                            $key,
                            [
                                'gteq' => (new Datetime($filter['from']))->format('Y-m-d H:i:s')
                            ]
                        );
                    }
                    if (isset($filter['to'])) {
                        $barcodeCollection->addFieldToFilter(
                            $key,
                            [
                                'lteq' => (new Datetime($filter['to']))->format('Y-m-d H:i:s')
                            ]
                        );
                    }
                } elseif (in_array($key, $this->rangeCondition)) {
                    if (isset($filter['from'])) {
                        $barcodeCollection->addFieldToFilter(
                            $key,
                            [
                                'gteq' => $filter['from']
                            ]
                        );
                    }
                    if (isset($filter['to'])) {
                        $barcodeCollection->addFieldToFilter(
                            $key,
                            [
                                'lteq' => $filter['to']
                            ]
                        );
                    }
                } else {
                    $barcodeCollection->addFieldToFilter($key, ['eq' => $filter]);
                }
            }
        }

        if ($excluded == 'false' ||
            empty($selectedBarcodeIds) ||
            (empty($excluded) && empty($selectedBarcodeIds))
        ) {
            $selectedBarcodeIds = $this->locator->get('current_barcode_ids_to_print');
        }
        if (is_array($excluded)) {
            $barcodeCollection->addFieldToFilter('id', ['nin' => $excluded]);
        }
        $barcodeCollection->addFieldToFilter('id', ['in' => $selectedBarcodeIds]);

        $savedItems = $this->locator->get('print_inline_edit_qty');
        if (!empty($savedItems)) {
            foreach ($barcodeCollection as $barcode) {
                foreach ($savedItems as $savedItem) {
                    if ($savedItem['id'] == $barcode->getId()) {
                        $barcode->setData('qty', $savedItem['qty']);
                        break;
                    }
                }
            }
        }
        if ($printQty) {
            foreach ($barcodeCollection as $barcode) {
                $barcode->setData('qty', $printQty);
            }
        }
        $html = "";
        $barcodes = [];
        foreach ($barcodeCollection as $barcode) {
            $barcodes[$barcode->getId()] = $barcode->getData();
        }
        $block = $this->createBlock(
            Template::class,
            '',
            'Magestore_BarcodeSuccess::barcode/print/template.phtml'
        );
        $block->setData('barcodes', $barcodes);
        if (isset($templateId)) {
            $template = $this->helper->getModel(BarcodeTemplateInterface::class);
            $this->templateResource->load($template, $templateId);
            $data = $template->getData();
            $block->setData('template_data', $data);
        }
        $html .= $block->toHtml();
        $resultJson->setData([
            'html' => $html,
            'success' => true
        ]);
        return $resultJson;
    }
}
