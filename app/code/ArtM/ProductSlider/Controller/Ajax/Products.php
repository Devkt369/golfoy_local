<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Ajax;

use ArtM\ProductSlider\Model\Frontend\SliderDataProvider;
use ArtM\ProductSlider\ViewModel\ProductCard;
use ArtM\ProductSlider\ViewModel\SliderRenderer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;

class Products extends Action
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly SliderDataProvider $sliderDataProvider,
        private readonly SliderRenderer $sliderRenderer,
        private readonly ProductCard $productCard,
        private readonly LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $identifier = trim((string)$this->getRequest()->getParam('identifier', ''));
            $page = max(1, (int)$this->getRequest()->getParam('page', 1));
            $pageSize = max(1, min(48, (int)$this->getRequest()->getParam('page_size', 4)));

            $sliderData = $this->sliderDataProvider->getSliderByIdentifier($identifier);
            if (!$sliderData) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Slider not found or not visible for this context.',
                ]);
            }

            $payload = $this->sliderDataProvider->getProductPage($sliderData, $page, $pageSize);
            $layout = $this->layoutFactory->create();
            $cards = $layout->createBlock(Template::class)
                ->setTemplate('ArtM_ProductSlider::slider/cards.phtml')
                ->setData('items', $payload['items'])
                ->setData('slider', $sliderData)
                ->setData('renderer', $this->sliderRenderer)
                ->setData('card_view_model', $this->productCard)
                ->toHtml();

            $this->getResponse()->setHeader('Cache-Control', 'public, max-age=300, s-maxage=900', true);

            return $result->setData([
                'success' => true,
                'data' => [
                    'html' => $cards,
                    'page' => (int)$payload['page'],
                    'page_size' => (int)$payload['page_size'],
                    'total' => (int)$payload['total'],
                    'has_more' => (bool)$payload['has_more'],
                ],
            ]);
        } catch (\Throwable $exception) {
            return $result->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
