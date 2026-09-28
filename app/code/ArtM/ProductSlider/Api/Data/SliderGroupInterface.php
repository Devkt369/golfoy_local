<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api\Data;

interface SliderGroupInterface
{
    public const GROUP_ID = 'group_id';
    public const NAME = 'name';
    public const IDENTIFIER = 'identifier';
    public const DESCRIPTION = 'description';
    public const SORT_ORDER = 'sort_order';
    public const TABS_JSON = 'tabs_json';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getGroupId(): ?int;

    public function setGroupId(int $groupId): self;

    public function getName(): string;

    public function setName(string $name): self;

    public function getIdentifier(): string;

    public function setIdentifier(string $identifier): self;

    public function getDescription(): ?string;

    public function setDescription(?string $description): self;

    public function getSortOrder(): int;

    public function setSortOrder(int $sortOrder): self;

    public function getTabsJson(): ?string;

    public function setTabsJson(?string $tabsJson): self;

    public function getStatus(): int;

    public function setStatus(int $status): self;

    public function getCreatedAt(): ?string;

    public function setCreatedAt(string $createdAt): self;

    public function getUpdatedAt(): ?string;

    public function setUpdatedAt(string $updatedAt): self;
}
