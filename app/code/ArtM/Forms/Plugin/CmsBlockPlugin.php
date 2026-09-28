<?php
namespace ArtM\Forms\Plugin;

use Magento\Cms\Block\Block as CmsBlock;
use Magento\Framework\View\LayoutInterface;

class CmsBlockPlugin
{
    protected $layout;
    public function __construct(LayoutInterface $layout)
    {
        $this->layout = $layout;
    }
    public function afterToHtml(CmsBlock $subject, $result)
    {
        // Replace {{artm_form identifier="form_identifier"}} with rendered form
        return preg_replace_callback('/\{\{artm_form identifier=\"([a-zA-Z0-9_\-]+)\"\}\}/', function($matches) {
            $block = $this->layout->createBlock('ArtM\\Forms\\Block\\Form');
            $block->setData('identifier', $matches[1]);
            return $block->setTemplate('ArtM_Forms::form.phtml')->toHtml();
        }, $result);
    }
}
