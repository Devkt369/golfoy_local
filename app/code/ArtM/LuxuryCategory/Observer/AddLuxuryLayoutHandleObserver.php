<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Observer;

use ArtM\LuxuryCategory\Service\LuxuryModeService;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * Adds the `catalog_category_view_luxe` layout handle whenever luxury mode
 * is active, so themes/other modules can target luxury-mode rendering
 * purely through layout XML without any controller changes. Registered on
 * `layout_load_before`, which fires for every layout build including the
 * forwarded category view action.
 */
class AddLuxuryLayoutHandleObserver implements ObserverInterface
{
    public const LUXURY_LAYOUT_HANDLE = 'catalog_category_view_luxe';

    public function __construct(
        private readonly LuxuryModeService $luxuryModeService
    ) {
    }

    public function execute(EventObserver $observer): void
    {
        if (!$this->luxuryModeService->isLuxuryMode()) {
            return;
        }

        /** @var LayoutInterface|null $layout */
        $layout = $observer->getEvent()->getLayout();
        if ($layout === null) {
            return;
        }

        $layout->getUpdate()->addHandle(self::LUXURY_LAYOUT_HANDLE);
    }
}
