<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api\Data;

interface SliderProductInterface
{
    public const ITEM_ID = 'item_id';
    public const SLIDER_ID = 'slider_id';
    public const PRODUCT_ID = 'product_id';
    public const POSITION = 'position';

    public function getItemId(): ?int;

    public function setItemId(int $itemId): self;

    public function getSliderId(): int;

    public function setSliderId(int $sliderId): self;

    public function getProductId(): int;

    public function setProductId(int $productId): self;

    public function getPosition(): int;

    public function setPosition(int $position): self;
}
