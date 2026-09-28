<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use ArtM\ProductSlider\Api\Data\SliderInterface;
use ArtM\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use Magento\Framework\Model\AbstractModel;

class Slider extends AbstractModel implements SliderInterface
{
    protected function _construct(): void
    {
        $this->_init(SliderResource::class);
    }

    public function getSliderId(): ?int
    {
        $value = $this->getData(self::SLIDER_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setSliderId(int $sliderId): SliderInterface
    {
        return $this->setData(self::SLIDER_ID, $sliderId);
    }

    public function getGroupId(): ?int
    {
        $value = $this->getData(self::GROUP_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setGroupId(?int $groupId): SliderInterface
    {
        return $this->setData(self::GROUP_ID, $groupId);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    public function setTitle(string $title): SliderInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getIdentifier(): string
    {
        return (string)$this->getData(self::IDENTIFIER);
    }

    public function setIdentifier(string $identifier): SliderInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus(int $status): SliderInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getSourceType(): string
    {
        return (string)$this->getData(self::SOURCE_TYPE);
    }

    public function setSourceType(string $sourceType): SliderInterface
    {
        return $this->setData(self::SOURCE_TYPE, $sourceType);
    }

    public function getSourceValue(): ?string
    {
        $value = $this->getData(self::SOURCE_VALUE);
        return $value !== null ? (string)$value : null;
    }

    public function setSourceValue(?string $sourceValue): SliderInterface
    {
        return $this->setData(self::SOURCE_VALUE, $sourceValue);
    }

    public function getSortBy(): string
    {
        return (string)$this->getData(self::SORT_BY);
    }

    public function setSortBy(string $sortBy): SliderInterface
    {
        return $this->setData(self::SORT_BY, $sortBy);
    }

    public function getSortDirection(): string
    {
        return (string)$this->getData(self::SORT_DIRECTION);
    }

    public function setSortDirection(string $sortDirection): SliderInterface
    {
        return $this->setData(self::SORT_DIRECTION, $sortDirection);
    }

    public function getProductLimit(): int
    {
        return (int)$this->getData(self::PRODUCT_LIMIT);
    }

    public function setProductLimit(int $productLimit): SliderInterface
    {
        return $this->setData(self::PRODUCT_LIMIT, $productLimit);
    }

    public function getSlidesToShow(): string
    {
        return (string)$this->getData(self::SLIDES_TO_SHOW);
    }

    public function setSlidesToShow(string $slidesToShow): SliderInterface
    {
        return $this->setData(self::SLIDES_TO_SHOW, $slidesToShow);
    }

    public function getAutoplay(): int
    {
        return (int)$this->getData(self::AUTOPLAY);
    }

    public function setAutoplay(int $autoplay): SliderInterface
    {
        return $this->setData(self::AUTOPLAY, $autoplay);
    }

    public function getAutoplaySpeed(): int
    {
        return (int)$this->getData(self::AUTOPLAY_SPEED);
    }

    public function setAutoplaySpeed(int $autoplaySpeed): SliderInterface
    {
        return $this->setData(self::AUTOPLAY_SPEED, $autoplaySpeed);
    }

    public function getShowName(): int
    {
        return (int)$this->getData(self::SHOW_NAME);
    }

    public function setShowName(int $showName): SliderInterface
    {
        return $this->setData(self::SHOW_NAME, $showName);
    }

    public function getShowPrice(): int
    {
        return (int)$this->getData(self::SHOW_PRICE);
    }

    public function setShowPrice(int $showPrice): SliderInterface
    {
        return $this->setData(self::SHOW_PRICE, $showPrice);
    }

    public function getShowAddToCart(): int
    {
        return (int)$this->getData(self::SHOW_ADD_TO_CART);
    }

    public function setShowAddToCart(int $showAddToCart): SliderInterface
    {
        return $this->setData(self::SHOW_ADD_TO_CART, $showAddToCart);
    }

    public function getPreviewNotes(): ?string
    {
        $value = $this->getData(self::PREVIEW_NOTES);
        return $value !== null ? (string)$value : null;
    }

    public function setPreviewNotes(?string $previewNotes): SliderInterface
    {
        return $this->setData(self::PREVIEW_NOTES, $previewNotes);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): SliderInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value !== null ? (string)$value : null;
    }

    public function setCreatedAt(string $createdAt): SliderInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value !== null ? (string)$value : null;
    }

    public function setUpdatedAt(string $updatedAt): SliderInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
