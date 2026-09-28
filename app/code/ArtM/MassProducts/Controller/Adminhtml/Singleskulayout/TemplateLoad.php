<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use ArtM\MassProducts\Model\SingleSkuTemplate\TemplateService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TemplateLoad extends Action
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
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_single_sku_template');

            $row = $connection->fetchRow(
                $connection->select()
                    ->from($table, ['template_html', 'custom_css', 'fallback_template_html', 'is_valid'])
                    ->where('attribute_set_id = ?', $setId)
                    ->limit(1)
            );

            $groups = $this->templateService->getGroupsWithAttributes($setId, false);
            $fallback = $row && !empty($row['fallback_template_html'])
                ? (string)$row['fallback_template_html']
                : $this->templateService->buildFallbackTemplate($groups);

            $isValid = $row ? ((int)($row['is_valid'] ?? 1) === 1) : true;
            $templateHtml = $row ? (string)($row['template_html'] ?? '') : '';
            $customCss = $row ? (string)($row['custom_css'] ?? '') : '';
            if ($templateHtml === '') {
                $templateHtml = $fallback;
                $isValid = true;
            }

            $effective = $isValid ? $templateHtml : $fallback;
            $preview = $this->templateService->renderPreviewHtml($effective, $groups);

            return $result->setData([
                'success' => true,
                'template_html' => $templateHtml,
                'custom_css' => $customCss,
                'fallback_template_html' => $fallback,
                'is_valid' => $isValid,
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
