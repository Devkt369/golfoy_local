<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Plugin\Theme\Block\Html;

use ArtM\LuxuryCategory\Service\LuxuryModeService;
use Magento\Theme\Block\Html\Breadcrumbs;

/**
 * Appends a trailing "Luxe" breadcrumb when luxury mode is active, without
 * altering any of the existing category breadcrumb trail built by
 * Magento\Catalog\Block\Category\View::_prepareLayout().
 */
class AddLuxuryBreadcrumbPlugin
{
    private const CRUMB_NAME = 'artm_luxury';

    public function __construct(
        private readonly LuxuryModeService $luxuryModeService
    ) {
    }

    /**
     * @param Breadcrumbs $subject
     * @return void
     */
    public function beforeToHtml(Breadcrumbs $subject): void
    {
        if (!$this->luxuryModeService->isLuxuryMode()) {
            return;
        }

        // addCrumb() is keyed by crumb name and overwrites in place, so
        // calling this repeatedly for the same request is naturally safe
        // — no separate "already added" check is needed.
        $subject->addCrumb(self::CRUMB_NAME, [
            'label' => __('Luxe'),
            'title' => __('Luxe'),
        ]);
    }
}
