<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Controller;

use ArtM\LuxuryCategory\Service\LuxuryModeService;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * Matches any request path ending in `/luxe` (e.g. `men/luxe`,
 * `men/perfumes/luxe`), resolves the underlying category through the
 * standard URL rewrite table (no hardcoded path mapping, no duplicate
 * categories), and forwards internally to the native category view
 * controller. The browser URL is never changed — this is a forward, not a
 * redirect.
 *
 * Only category entities are handled today; the entity-type check below is
 * the deliberate extension point for future `/product-name/luxe` support
 * without touching this router's matching/forwarding flow.
 */
class Router implements RouterInterface
{
    private const CATEGORY_ENTITY_TYPE = 'category';

    public function __construct(
        private readonly LuxuryModeService $luxuryModeService,
        private readonly UrlFinderInterface $urlFinder,
        private readonly StoreManagerInterface $storeManager,
        private readonly ActionFactory $actionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        if (!$request instanceof HttpRequest) {
            return null;
        }

        $pathInfo = (string)$request->getPathInfo();
        if (!$this->luxuryModeService->isLuxuryPath($pathInfo)) {
            return null;
        }

        $categoryRequestPath = $this->stripLuxurySuffix($pathInfo);
        if ($categoryRequestPath === '') {
            return null;
        }

        $entityId = $this->resolveCategoryId($categoryRequestPath);
        if ($entityId === null) {
            return null;
        }

        $this->luxuryModeService->markLuxuryMode(true);

        // Register the pretty "/shoes.html/luxe"-style path as the rewrite
        // alias for this request — the same thing
        // Magento\UrlRewrite\Controller\Router does for ordinary category
        // URLs. Without this, Magento\Framework\Url::getUrl('*/*/*', ...)
        // (used internally by the toolbar/pager for "page N" links, and by
        // anything else building a "current page" URL) has no way to know
        // the pretty URL exists and falls back to the raw internal route
        // (/catalog/category/view/id/31/...) — which is exactly what
        // broke pager links to page 2+.
        $request->setAlias(
            \Magento\Framework\UrlInterface::REWRITE_REQUEST_PATH_ALIAS,
            trim($pathInfo, '/')
        );

        $request->setModuleName('catalog');
        $request->setControllerName('category');
        $request->setActionName('view');
        $request->setParam('id', $entityId);

        // A Forward action sets dispatched=false, so the front controller
        // re-runs every router from the top on the next loop iteration
        // (see Magento\Framework\App\FrontController::dispatch()). If we
        // left pathInfo untouched, our own isLuxuryPath() check would match
        // this same "/.../luxe" path again on every pass and forward
        // forever, until Magento's 100-iteration guard throws a
        // LogicException. Rewriting pathInfo to the resolved target — the
        // same technique Magento\UrlRewrite\Controller\Router uses — makes
        // this router return null on re-entry so the standard router can
        // take over and actually dispatch the category controller.
        // getOriginalPathInfo() (used everywhere else via
        // LuxuryModeService::isLuxuryMode()) is computed once from the raw
        // request and is unaffected by this, so luxury mode detection
        // elsewhere stays correct regardless.
        $request->setPathInfo('/catalog/category/view');

        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }

    /**
     * Resolves a category entity id from its request path via the standard
     * url_rewrite table. Returns null when no matching category rewrite
     * exists (lets the request fall through to the next router / 404).
     */
    private function resolveCategoryId(string $requestPath): ?int
    {
        $storeId = (int)$this->storeManager->getStore()->getId();

        $rewrite = $this->urlFinder->findOneByData([
            UrlRewrite::REQUEST_PATH => $requestPath,
            UrlRewrite::STORE_ID => $storeId,
        ]);

        if ($rewrite === null || $rewrite->getEntityType() !== self::CATEGORY_ENTITY_TYPE) {
            return null;
        }

        $entityId = (int)$rewrite->getEntityId();

        return $entityId > 0 ? $entityId : null;
    }

    /**
     * Strips the trailing `/luxe` segment and leading slash, returning a
     * bare request path suitable for a url_rewrite lookup (e.g. `men`).
     */
    private function stripLuxurySuffix(string $pathInfo): string
    {
        $normalized = trim($pathInfo, '/');
        $suffixLength = strlen(LuxuryModeService::LUXE_SEGMENT);

        if (strlen($normalized) <= $suffixLength) {
            return '';
        }

        $withoutSuffix = substr($normalized, 0, -$suffixLength);

        return trim($withoutSuffix, '/');
    }
}
