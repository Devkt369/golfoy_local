<?php
/**
 * ArtM ShortVideos Plugin for CMS Page
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Plugin;

use Magento\Cms\Block\Page;
use ArtM\ShortVideos\Helper\Data as ShortcodeHelper;

class CmsPagePlugin
{
    /**
     * @var ShortcodeHelper
     */
    protected $shortcodeHelper;

    /**
     * @param ShortcodeHelper $shortcodeHelper
     */
    public function __construct(ShortcodeHelper $shortcodeHelper)
    {
        $this->shortcodeHelper = $shortcodeHelper;
    }

    /**
     * Process shortcodes in CMS page content
     *
     * @param Page $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(Page $subject, $result)
    {
        return $this->shortcodeHelper->processShortcode($result);
    }
}
