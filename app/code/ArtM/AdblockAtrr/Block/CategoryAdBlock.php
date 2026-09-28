<?php
declare(strict_types=1);

namespace ArtM\AdblockAtrr\Block;

use Magento\Cms\Block\Block as CmsBlock;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CategoryAdBlock extends Template
{
    private Registry $registry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    public function getAdBlockHtml(): string
    {
        $identifier = $this->getAdBlockIdentifier();

        if ($identifier === '' || $this->getPosition() <= 0 || $this->getCurrentPage() !== 1) {
            return '';
        }

        $cmsBlock = $this->getLayout()->createBlock(CmsBlock::class);

        if (!$cmsBlock) {
            return '';
        }

        return (string) $cmsBlock->setBlockId($identifier)->toHtml();
    }

    public function getPosition(): int
    {
        $parts = $this->getAdBlockParts();

        return isset($parts[1]) ? max(0, (int) $parts[1]) : 0;
    }

    public function getCurrentPage(): int
    {
        return max(1, (int) $this->getRequest()->getParam('p', 1));
    }

    private function getAdBlockIdentifier(): string
    {
        $parts = $this->getAdBlockParts();

        return $parts[0] ?? '';
    }

    private function getAdBlockParts(): array
    {
        $category = $this->registry->registry('current_category');

        if (!$category) {
            return [];
        }

        $adBlock = trim((string) $category->getData('adblock'));

        if ($adBlock === '') {
            return [];
        }

        return array_map('trim', explode('|', $adBlock, 2));
    }
}
