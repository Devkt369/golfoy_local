<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use ArtM\ProductSlider\Api\Data\SliderGroupItemInterface;
use ArtM\ProductSlider\Model\ResourceModel\SliderGroupItem as SliderGroupItemResource;
use Magento\Framework\Model\AbstractModel;

class SliderGroupItem extends AbstractModel implements SliderGroupItemInterface
{
    protected function _construct(): void
    {
        $this->_init(SliderGroupItemResource::class);
    }

    public function getItemId(): ?int
    {
        $value = $this->getData(self::ITEM_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setItemId(int $itemId): SliderGroupItemInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    public function getGroupId(): int
    {
        return (int)$this->getData(self::GROUP_ID);
    }

    public function setGroupId(int $groupId): SliderGroupItemInterface
    {
        return $this->setData(self::GROUP_ID, $groupId);
    }

    public function getSliderId(): int
    {
        return (int)$this->getData(self::SLIDER_ID);
    }

    public function setSliderId(int $sliderId): SliderGroupItemInterface
    {
        return $this->setData(self::SLIDER_ID, $sliderId);
    }

    public function getTabTitle(): string
    {
        return (string)$this->getData(self::TAB_TITLE);
    }

    public function setTabTitle(string $tabTitle): SliderGroupItemInterface
    {
        return $this->setData(self::TAB_TITLE, $tabTitle);
    }

    public function getPosition(): int
    {
        return (int)$this->getData(self::POSITION);
    }

    public function setPosition(int $position): SliderGroupItemInterface
    {
        return $this->setData(self::POSITION, $position);
    }

    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus(int $status): SliderGroupItemInterface
    {
        return $this->setData(self::STATUS, $status);
    }
}
