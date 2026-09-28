<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use ArtM\MassProducts\Model\SingleSkuTemplate\TemplateService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TemplateSave extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /** @var ResourceConnection */
    private $resource;

    /** @var TemplateService */
    private $templateService;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        TemplateService $templateService
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->templateService = $templateService;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $templateRaw = (string)$this->getRequest()->getParam('template_html', '');
            $cssRaw = (string)$this->getRequest()->getParam('custom_css', '');

            $groups = $this->templateService->getGroupsWithAttributes($setId, false);
            $fallback = $this->templateService->buildFallbackTemplate($groups);
            $templateHtml = $this->templateService->sanitizeHtml($templateRaw);
            $customCss = $this->templateService->sanitizeCss($cssRaw);

            $isValid = true;
            if ($templateHtml === '') {
                $isValid = false;
            }
            if ($this->templateService->hasInvalidTokenSyntax($templateRaw)) {
                $isValid = false;
            }

            $effective = $isValid ? $templateHtml : $fallback;
            $preview = $this->templateService->renderPreviewHtml($effective, $groups);

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_single_sku_template');
            $connection->insertOnDuplicate(
                $table,
                [
                    'attribute_set_id' => $setId,
                    'template_html' => $templateHtml,
                    'custom_css' => $customCss,
                    'fallback_template_html' => $fallback,
                    'is_valid' => $isValid ? 1 : 0,
                ],
                ['template_html', 'custom_css', 'fallback_template_html', 'is_valid', 'updated_at']
            );

            return $result->setData([
                'success' => true,
                'message' => $isValid ? 'Template saved.' : 'Template saved with fallback because input was invalid.',
                'is_valid' => $isValid,
                'template_html' => $templateHtml,
                'custom_css' => $customCss,
                'fallback_template_html' => $fallback,
                'preview_html' => $preview,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
