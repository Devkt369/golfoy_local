<?php
/**
 * ArtM ShortVideos Plugin for CMS Block
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Plugin;

use Magento\Cms\Block\Block;
use ArtM\ShortVideos\Helper\Data as ShortcodeHelper;

class CmsBlockPlugin
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
     * Process shortcodes in CMS block content
     *
     * @param Block $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(Block $subject, $result)
    {
        return $this->shortcodeHelper->processShortcode($result);
    }
}
