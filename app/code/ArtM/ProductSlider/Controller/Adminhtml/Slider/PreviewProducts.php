<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use ArtM\ProductSlider\Model\ProductEngine;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class PreviewProducts extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_save';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly ProductEngine $productEngine
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        try {
            $sliderData = (array)$this->getRequest()->getParam('slider', []);
            if ($sliderData === []) {
                $sliderData = (array)$this->getRequest()->getPostValue('slider');
            }

            return $result->setData([
                'success' => true,
                'data' => $this->productEngine->resolveForPreview($sliderData),
            ]);
        } catch (\Throwable $exception) {
            return $result->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
