<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Block;

use ArtM\LuxuryCategory\Model\ResourceModel\LuxuryProductChecker;
use ArtM\LuxuryCategory\Service\LuxuryModeService;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * TEMPORARY diagnostic panel for the /luxe product-filtering issue.
 *
 * Only renders when the request carries ?luxe_debug=1 — remove this block,
 * its template, and the layout reference in catalog_category_view_luxe.xml
 * once debugging is complete; it is not meant to ship long-term.
 */
class Debug extends Template
{
    public function __construct(
        Context $context,
        private readonly LuxuryModeService $luxuryModeService,
        private readonly LuxuryProductChecker $luxuryProductChecker,
        private readonly LayerResolver $layerResolver,
        private readonly RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isDebugEnabled(): bool
    {
        return (string)$this->request->getParam('luxe_debug', '') === '1';
    }

    public function isLuxuryMode(): bool
    {
        return $this->luxuryModeService->isLuxuryMode();
    }

    public function getCategoryId(): int
    {
        $category = $this->getCurrentCategory();

        return $category ? (int)$category->getId() : 0;
    }

    public function getCategoryName(): string
    {
        $category = $this->getCurrentCategory();

        return $category ? (string)$category->getName() : '';
    }

    /**
     * @return int[]
     */
    public function getLuxuryProductIds(): array
    {
        $categoryId = $this->getCategoryId();
        if ($categoryId <= 0) {
            return [];
        }

        return $this->luxuryProductChecker->getLuxuryProductIdsForCategory($categoryId);
    }

    public function getCollectionSize(): ?int
    {
        try {
            return $this->getLayer()?->getProductCollection()->getSize();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return int[]
     */
    public function getCollectionProductIds(): array
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();
            if ($collection === null) {
                return [];
            }

            return array_map('intval', $collection->getAllIds());
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getCollectionClass(): string
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();

            return $collection ? get_class($collection) : '';
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    public function getLayerClass(): string
    {
        $layer = $this->getLayer();

        return $layer ? get_class($layer) : '';
    }

    public function getRequestParams(): array
    {
        return (array)$this->request->getParams();
    }

    public function getPathInfo(): string
    {
        return (string)$this->request->getPathInfo();
    }

    public function isOurFilterFlagSet(): bool
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();

            return $collection ? (bool)$collection->getFlag(
                \ArtM\LuxuryCategory\Plugin\Catalog\Model\LuxuryLayerProductCollectionPlugin::APPLIED_FLAG
            ) : false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getPaginationTrace(): array
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();

            return $collection ? (array)$collection->getFlag(
                \ArtM\LuxuryCategory\Plugin\Catalog\Model\LuxuryCollectionPaginationPlugin::TRACE_FLAG
            ) : [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getCollectionObjectHash(): string
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();

            return $collection ? spl_object_hash($collection) : '';
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    public function getCollectionItemCount(): int
    {
        try {
            return count((array)$this->getLayer()?->getProductCollection()->getItems());
        } catch (\Throwable $e) {
            return -1;
        }
    }

    public function getCollectionPageSize(): ?int
    {
        try {
            $pageSize = $this->getLayer()?->getProductCollection()->getPageSize();

            return $pageSize !== null ? (int)$pageSize : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getCollectionCurPage(): ?int
    {
        try {
            $curPage = $this->getLayer()?->getProductCollection()->getCurPage();

            return $curPage !== null ? (int)$curPage : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getSelectSql(): string
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();
            if ($collection === null) {
                return '';
            }

            // Force filter/select rendering (safe — idempotent if already
            // rendered earlier in the request) so the SQL reflects every
            // condition actually applied, not just what was added so far.
            $collection->getSelectCountSql();

            return (string)$collection->getSelect();
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    public function getCountSelectSql(): string
    {
        try {
            $collection = $this->getLayer()?->getProductCollection();

            return $collection ? (string)$collection->getSelectCountSql() : '';
        } catch (\Throwable $e) {
            return 'ERROR: ' . $e->getMessage();
        }
    }

    /**
     * Reads the toolbar block's own view of pagination — rendered after
     * the toolbar has already run (this block is positioned with after="-"
     * on the content container), so this reflects reality rather than a
     * state our own debug calls forced.
     */
    public function getToolbarInfo(): array
    {
        $toolbar = $this->getLayout()->getBlock('product_list_toolbar');
        if (!$toolbar) {
            return ['found' => false];
        }

        $info = ['found' => true, 'class' => get_class($toolbar)];

        try {
            $info['getCurrentPage'] = method_exists($toolbar, 'getCurrentPage') ? $toolbar->getCurrentPage() : null;
        } catch (\Throwable $e) {
            $info['getCurrentPage'] = 'ERROR: ' . $e->getMessage();
        }
        try {
            $info['getLimit'] = method_exists($toolbar, 'getLimit') ? $toolbar->getLimit() : null;
        } catch (\Throwable $e) {
            $info['getLimit'] = 'ERROR: ' . $e->getMessage();
        }
        try {
            $collection = method_exists($toolbar, 'getCollection') ? $toolbar->getCollection() : null;
            if ($collection) {
                $info['collection_getSize'] = $collection->getSize();
                $info['collection_getPageSize'] = $collection->getPageSize();
                $info['collection_getCurPage'] = $collection->getCurPage();
                $info['collection_getLastPageNumber'] = method_exists($collection, 'getLastPageNumber')
                    ? $collection->getLastPageNumber()
                    : null;
                $info['collection_object_hash'] = spl_object_hash($collection);
            } else {
                $info['collection'] = 'null';
            }
        } catch (\Throwable $e) {
            $info['collection_error'] = $e->getMessage();
        }
        try {
            $pagerHtml = method_exists($toolbar, 'getPagerHtml') ? $toolbar->getPagerHtml() : '';
            $info['pagerHtml_length'] = strlen((string)$pagerHtml);
        } catch (\Throwable $e) {
            $info['pagerHtml_error'] = $e->getMessage();
        }

        return $info;
    }

    private function getLayer(): ?\Magento\Catalog\Model\Layer
    {
        return $this->layerResolver->get();
    }

    private function getCurrentCategory(): ?\Magento\Catalog\Model\Category
    {
        $layer = $this->getLayer();

        return $layer ? $layer->getCurrentCategory() : null;
    }
}
