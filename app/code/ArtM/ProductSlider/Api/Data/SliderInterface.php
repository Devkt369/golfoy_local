<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api\Data;

interface SliderInterface
{
    public const SLIDER_ID = 'slider_id';
    public const GROUP_ID = 'group_id';
    public const TITLE = 'title';
    public const IDENTIFIER = 'identifier';
    public const STATUS = 'status';
    public const SOURCE_TYPE = 'source_type';
    public const SOURCE_VALUE = 'source_value';
    public const SORT_BY = 'sort_by';
    public const SORT_DIRECTION = 'sort_direction';
    public const PRODUCT_LIMIT = 'product_limit';
    public const SLIDES_TO_SHOW = 'slides_to_show';
    public const AUTOPLAY = 'autoplay';
    public const AUTOPLAY_SPEED = 'autoplay_speed';
    public const SHOW_NAME = 'show_name';
    public const SHOW_PRICE = 'show_price';
    public const SHOW_ADD_TO_CART = 'show_add_to_cart';
    public const PREVIEW_NOTES = 'preview_notes';
    public const SORT_ORDER = 'sort_order';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getSliderId(): ?int;

    public function setSliderId(int $sliderId): self;

    public function getGroupId(): ?int;

    public function setGroupId(?int $groupId): self;

    public function getTitle(): string;

    public function setTitle(string $title): self;

    public function getIdentifier(): string;

    public function setIdentifier(string $identifier): self;

    public function getStatus(): int;

    public function setStatus(int $status): self;

    public function getSourceType(): string;

    public function setSourceType(string $sourceType): self;

    public function getSourceValue(): ?string;

    public function setSourceValue(?string $sourceValue): self;

    public function getSortBy(): string;

    public function setSortBy(string $sortBy): self;

    public function getSortDirection(): string;

    public function setSortDirection(string $sortDirection): self;

    public function getProductLimit(): int;

    public function setProductLimit(int $productLimit): self;

    public function getSlidesToShow(): string;

    public function setSlidesToShow(string $slidesToShow): self;

    public function getAutoplay(): int;

    public function setAutoplay(int $autoplay): self;

    public function getAutoplaySpeed(): int;

    public function setAutoplaySpeed(int $autoplaySpeed): self;

    public function getShowName(): int;

    public function setShowName(int $showName): self;

    public function getShowPrice(): int;

    public function setShowPrice(int $showPrice): self;

    public function getShowAddToCart(): int;

    public function setShowAddToCart(int $showAddToCart): self;

    public function getPreviewNotes(): ?string;

    public function setPreviewNotes(?string $previewNotes): self;

    public function getSortOrder(): int;

    public function setSortOrder(int $sortOrder): self;

    public function getCreatedAt(): ?string;

    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): ?string;

    public function setUpdatedAt(string $updatedAt): self;
}
