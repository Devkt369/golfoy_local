<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block;

use ArtM\ProductSlider\Model\Frontend\GroupDataProvider;
use ArtM\ProductSlider\Model\Frontend\SliderDataProvider;
use ArtM\ProductSlider\ViewModel\SliderRenderer;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\LayoutFactory;

class Group extends Template
{
    private ?array $groupData = null;

    public function __construct(
        Context $context,
        private readonly GroupDataProvider $groupDataProvider,
        private readonly SliderDataProvider $sliderDataProvider,
        private readonly LayoutFactory $layoutFactory,
        private readonly SliderRenderer $sliderRenderer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getGroupId(): int
    {
        $dataId = (int)$this->getData('group_id');
        if ($dataId > 0) {
            return $dataId;
        }

        $dataLegacy = (int)$this->getData('id');
        if ($dataLegacy > 0) {
            return $dataLegacy;
        }

        $requestGroupId = (int)$this->getRequest()->getParam('group_id', 0);
        if ($requestGroupId > 0) {
            return $requestGroupId;
        }

        return (int)$this->getRequest()->getParam('id', 0);
    }

    /**
     * Resolve the group identifier from block data or request params.
     */
    public function getIdentifier(): string
    {
        $identifier = trim((string)$this->getData('identifier'));
        if ($identifier !== '') {
            return $identifier;
        }

        return trim((string)$this->getRequest()->getParam('identifier', ''));
    }

    /**
     * Return normalized group payload with settings and tabs.
     *
     * @return array<string, mixed>|null
     */
    public function getGroupData(): ?array
    {
        if ($this->groupData !== null) {
            return $this->groupData;
        }

        $this->groupData = $this->groupDataProvider->getGroup($this->getIdentifier(), $this->getGroupId());
        return $this->groupData;
    }

    public function getSettings(): array
    {
        $group = $this->getGroupData();
        if (!is_array($group)) {
            return [];
        }

        return is_array($group['settings'] ?? null) ? (array)$group['settings'] : [];
    }

    public function getTabs(): array
    {
        $group = $this->getGroupData();
        if (!is_array($group)) {
            return [];
        }

        return is_array($group['tabs'] ?? null) ? (array)$group['tabs'] : [];
    }

    public function getDefaultTabIndex(): int
    {
        $tabs = $this->getTabs();
        foreach ($tabs as $index => $tab) {
            if ((int)($tab['is_default'] ?? 0) === 1) {
                return (int)$index;
            }
        }

        return 0;
    }

    public function getDeviceVisibilityClass(): string
    {
        return $this->groupDataProvider->getDeviceVisibilityClass($this->getSettings());
    }

    /**
     * Resolve tab-level view-all link based on override mode.
     *
     * @return array{show: bool, url: string, text: string}
     */
    public function getTabViewAll(array $tab): array
    {
        $override = (string)($tab['view_all_override'] ?? 'use_slider_button');
        if ($override === 'hide') {
            return ['show' => false, 'url' => '', 'text' => ''];
        }

        if ($override === 'manual_url') {
            $manual = trim((string)($tab['manual_url'] ?? ''));
            return ['show' => $manual !== '', 'url' => $manual, 'text' => 'View All'];
        }

        $identifier = trim((string)($tab['slider_identifier'] ?? ''));
        if ($identifier === '') {
            return ['show' => false, 'url' => '', 'text' => ''];
        }

        $slider = $this->sliderDataProvider->getSliderByIdentifier($identifier);
        if (!$slider) {
            return ['show' => false, 'url' => '', 'text' => ''];
        }

        $url = trim($this->sliderRenderer->getButtonUrl($slider));
        if ($url === '') {
            return ['show' => false, 'url' => '', 'text' => ''];
        }

        $text = trim((string)($slider['button_text'] ?? 'View All'));
        return ['show' => true, 'url' => $url, 'text' => $text !== '' ? $text : 'View All'];
    }

    public function getTabHtml(array $tab): string
    {
        $identifier = trim((string)($tab['slider_identifier'] ?? ''));
        $identifier = trim($identifier);
        if ($identifier === '') {
            return '';
        }

        try {
            $sliderBlock = $this->layoutFactory->create()->createBlock(Slider::class);
            if (!$sliderBlock) {
                return '';
            }

            $sliderBlock->setData('identifier', $identifier);
            $sliderBlock->setTemplate('ArtM_ProductSlider::slider/view.phtml');
            return (string)$sliderBlock->toHtml();
        } catch (\Throwable) {
            return '';
        }
    }
}
