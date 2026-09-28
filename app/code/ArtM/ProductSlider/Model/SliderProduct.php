<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use ArtM\ProductSlider\Api\Data\SliderProductInterface;
use ArtM\ProductSlider\Model\ResourceModel\SliderProduct as SliderProductResource;
use Magento\Framework\Model\AbstractModel;

class SliderProduct extends AbstractModel implements SliderProductInterface
{
    protected function _construct(): void
    {
        $this->_init(SliderProductResource::class);
    }

    public function getItemId(): ?int
    {
        $value = $this->getData(self::ITEM_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setItemId(int $itemId): SliderProductInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    public function getSliderId(): int
    {
        return (int)$this->getData(self::SLIDER_ID);
    }

    public function setSliderId(int $sliderId): SliderProductInterface
    {
        return $this->setData(self::SLIDER_ID, $sliderId);
    }

    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId): SliderProductInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getPosition(): int
    {
        return (int)$this->getData(self::POSITION);
    }

    public function setPosition(int $position): SliderProductInterface
    {
        return $this->setData(self::POSITION, $position);
    }
}
