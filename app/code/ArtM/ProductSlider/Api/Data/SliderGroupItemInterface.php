<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api\Data;

interface SliderGroupItemInterface
{
    public const ITEM_ID = 'item_id';
    public const GROUP_ID = 'group_id';
    public const SLIDER_ID = 'slider_id';
    public const TAB_TITLE = 'tab_title';
    public const POSITION = 'position';
    public const STATUS = 'status';

    public function getItemId(): ?int;

    public function setItemId(int $itemId): self;

    public function getGroupId(): int;

    public function setGroupId(int $groupId): self;

    public function getSliderId(): int;

    public function setSliderId(int $sliderId): self;

    public function getTabTitle(): string;

    public function setTabTitle(string $tabTitle): self;

    public function getPosition(): int;

    public function setPosition(int $position): self;

    public function getStatus(): int;

    public function setStatus(int $status): self;
}
