<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class Tab extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    public function execute(): Raw
    {
        $tab = (string)$this->getRequest()->getParam('tab', 'upload');

        $templates = [
            'products_upload' => 'ArtM_MassProducts::tabs/products_upload.phtml',
            'configuration' => 'ArtM_MassProducts::tabs/configuration.phtml',
            'upload' => 'ArtM_MassProducts::tabs/upload.phtml',
            'inventory_update' => 'ArtM_MassProducts::tabs/inventory_update.phtml',
            'reorder_products' => 'ArtM_MassProducts::tabs/reorder_products.phtml',
            'column_mapping' => 'ArtM_MassProducts::tabs/column_mapping.phtml',
            'draft' => 'ArtM_MassProducts::tabs/draft.phtml',
            'attributes_assignment' => 'ArtM_MassProducts::tabs/attributes_assignment.phtml',
            'attribute_sorting' => 'ArtM_MassProducts::tabs/attribute_sorting.phtml',
            'single_sku_config' => 'ArtM_MassProducts::tabs/single_sku_config.phtml',
            'single_sku_template' => 'ArtM_MassProducts::tabs/single_sku_template.phtml',
            'logs' => 'ArtM_MassProducts::tabs/logs.phtml',
            'cron_settings' => 'ArtM_MassProducts::tabs/cron_settings.phtml',
            'ready' => 'ArtM_MassProducts::tabs/ready.phtml',
            'queued' => 'ArtM_MassProducts::tabs/queued.phtml',
            'processing' => 'ArtM_MassProducts::tabs/processing.phtml',
            'failed' => 'ArtM_MassProducts::tabs/failed.phtml',
            'success' => 'ArtM_MassProducts::tabs/success.phtml',
            'single_sku' => 'ArtM_MassProducts::tabs/single_sku.phtml',
        ];

        $template = $templates[$tab] ?? $templates['upload'];

        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock(\Magento\Backend\Block\Template::class)
            ->setTemplate($template);

        return $this->resultRawFactory->create()->setContents($block->toHtml());
    }
}
