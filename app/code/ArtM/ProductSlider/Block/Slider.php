<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\Frontend\SliderDataProvider;
use ArtM\ProductSlider\Model\ProductEngine;
use ArtM\ProductSlider\ViewModel\ProductCard;
use ArtM\ProductSlider\ViewModel\SliderRenderer;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutFactory;

class Slider extends Template implements IdentityInterface
{
    private ?array $sliderData = null;

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderDataProvider $sliderDataProvider,
        private readonly SliderRenderer $sliderRenderer,
        private readonly ProductCard $productCard,
        private readonly LayoutFactory $layoutFactory,
        private readonly HttpContext $httpContext,
        private readonly ProductEngine $productEngine,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getIdentifier(): string
    {
        $identifier = trim((string)$this->getData('identifier'));
        if ($identifier !== '') {
            return $identifier;
        }

        $identifier = trim((string)$this->getRequest()->getParam('identifier', ''));
        if ($identifier !== '') {
            return $identifier;
        }

        $sliderId = (int)$this->getData('slider_id');
        if ($sliderId <= 0) {
            $sliderId = (int)$this->getData('id');
        }
        if ($sliderId <= 0) {
            $sliderId = (int)$this->getRequest()->getParam('slider_id', 0);
        }
        if ($sliderId <= 0) {
            $sliderId = (int)$this->getRequest()->getParam('id', 0);
        }
        if ($sliderId > 0) {
            try {
                $slider = $this->sliderRepository->getById($sliderId);
                $identifier = trim((string)$slider->getData('identifier'));
                if ($identifier !== '') {
                    return $identifier;
                }
            } catch (\Throwable) {
            }
        }

        return $this->extractIdentifierFromQueryString();
    }

    public function canRender(): bool
    {
        return $this->getSliderData() !== null;
    }

    public function getSliderData(): ?array
    {
        if ($this->sliderData !== null) {
            return $this->sliderData;
        }

        $data = $this->sliderDataProvider->getSliderByIdentifier($this->getIdentifier());
        $this->sliderData = $data;
        return $this->sliderData;
    }

    public function getInitialProductsPayload(): array
    {
        $sliderData = $this->getSliderData();
        if (!$sliderData) {
            return ['items' => [], 'page' => 1, 'total' => 0, 'has_more' => false, 'page_size' => 1];
        }

        $pageSize = $this->sliderRenderer->getInitialPageSize($sliderData);
        return $this->sliderDataProvider->getProductPage($sliderData, 1, $pageSize);
    }

    public function isDebugMode(): bool
    {
        return (int)$this->getRequest()->getParam('ps_debug', 0) === 1
            || (int)$this->getRequest()->getParam('debug', 0) === 1;
    }

    public function getDebugData(array $payload = []): array
    {
        $slider = $this->getSliderData();
        $queryString = (string)$this->getRequest()->getServer('QUERY_STRING');

        return [
            'request' => [
                'full_action' => (string)$this->getRequest()->getFullActionName(),
                'query_string' => $queryString,
                'identifier_param' => (string)$this->getRequest()->getParam('identifier', ''),
                'resolved_identifier' => $this->getIdentifier(),
                'store_id' => (int)$this->_storeManager->getStore()->getId(),
                'customer_group_id' => (int)$this->httpContext->getValue(CustomerContext::CONTEXT_GROUP),
                'is_logged_in' => (int)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH) === 1,
            ],
            'slider' => [
                'found' => $slider !== null,
                'slider_id' => (int)($slider['slider_id'] ?? 0),
                'title' => (string)($slider['title'] ?? ''),
                'status' => (int)($slider['status'] ?? 0),
                'source_type' => (string)($slider['source_type'] ?? ''),
                'display_type' => (string)($slider['display_type'] ?? ''),
                'store_view_ids' => (string)($slider['store_view_ids'] ?? ''),
                'customer_group_ids' => (string)($slider['customer_group_ids'] ?? ''),
            ],
            'products' => [
                'initial_total' => (int)($payload['total'] ?? 0),
                'initial_page_size' => (int)($payload['page_size'] ?? 0),
                'initial_items_count' => is_array($payload['items'] ?? null) ? count((array)$payload['items']) : 0,
                'has_more' => (bool)($payload['has_more'] ?? false),
            ],
            'stock_filter_trace' => $this->productEngine->getDebugTrace(),
        ];
    }

    /**
     * @param Product[] $items
     */
    public function getCardsHtml(array $items, array $sliderData): string
    {
        $layout = $this->layoutFactory->create();
        $block = $layout->createBlock(Template::class);
        $block->setTemplate('ArtM_ProductSlider::slider/cards.phtml');
        $block->setData('items', $items);
        $block->setData('slider', $sliderData);
        $block->setData('renderer', $this->sliderRenderer);
        $block->setData('card_view_model', $this->productCard);

        return $block->toHtml();
    }

    public function getSliderRenderer(): SliderRenderer
    {
        return $this->sliderRenderer;
    }

    public function getProductCardViewModel(): ProductCard
    {
        return $this->productCard;
    }

    public function getAjaxUrl(): string
    {
        return $this->getUrl('artm_productslider/ajax/products');
    }

    public function getCacheLifetime(): ?int
    {
        if ($this->isDebugMode()) {
            return null;
        }

        return 3600;
    }

    public function getCacheKeyInfo(): array
    {
        return [
            'ARTM_PRODUCT_SLIDER_BLOCK',
            $this->getIdentifier(),
            (int)$this->_storeManager->getStore()->getId(),
            (string)$this->httpContext->getValue(CustomerContext::CONTEXT_GROUP),
            (string)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH),
            $this->isDebugMode() ? 'debug_1' : 'debug_0',
        ];
    }

    public function getIdentities(): array
    {
        $sliderData = $this->getSliderData();
        if (!$sliderData || empty($sliderData['slider_id'])) {
            return [];
        }

        return ['ARTM_PRODUCTSLIDER_' . (int)$sliderData['slider_id']];
    }

    private function extractIdentifierFromQueryString(): string
    {
        $queryString = trim((string)$this->getRequest()->getServer('QUERY_STRING'));
        if ($queryString === '') {
            $requestUri = (string)$this->getRequest()->getServer('REQUEST_URI');
            $uriQuery = parse_url($requestUri, PHP_URL_QUERY);
            if (is_string($uriQuery) && $uriQuery !== '') {
                $queryString = trim($uriQuery);
            }
        }

        if ($queryString === '') {
            return '';
        }

        $parsed = [];
        parse_str($queryString, $parsed);
        if (is_array($parsed)) {
            $identifier = trim((string)($parsed['identifier'] ?? ''));
            if ($identifier !== '') {
                return $identifier;
            }
        }

        $pairs = explode('&', $queryString);
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }

            if (strpos($pair, '=') === false) {
                return trim((string)urldecode($pair));
            }
        }

        return '';
    }
}
