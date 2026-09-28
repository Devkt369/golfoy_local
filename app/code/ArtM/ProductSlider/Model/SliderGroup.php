<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use ArtM\ProductSlider\Api\Data\SliderGroupInterface;
use ArtM\ProductSlider\Model\ResourceModel\SliderGroup as SliderGroupResource;
use Magento\Framework\Model\AbstractModel;

class SliderGroup extends AbstractModel implements SliderGroupInterface
{
    protected function _construct(): void
    {
        $this->_init(SliderGroupResource::class);
    }

    public function getGroupId(): ?int
    {
        $value = $this->getData(self::GROUP_ID);
        return $value !== null ? (int)$value : null;
    }

    public function setGroupId(int $groupId): SliderGroupInterface
    {
        return $this->setData(self::GROUP_ID, $groupId);
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): SliderGroupInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getIdentifier(): string
    {
        return (string)$this->getData(self::IDENTIFIER);
    }

    public function setIdentifier(string $identifier): SliderGroupInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    public function getDescription(): ?string
    {
        $value = $this->getData(self::DESCRIPTION);
        return $value !== null ? (string)$value : null;
    }

    public function setDescription(?string $description): SliderGroupInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): SliderGroupInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getTabsJson(): ?string
    {
        $value = $this->getData(self::TABS_JSON);
        return $value !== null ? (string)$value : null;
    }

    public function setTabsJson(?string $tabsJson): SliderGroupInterface
    {
        return $this->setData(self::TABS_JSON, $tabsJson);
    }

    public function getStatus(): int
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus(int $status): SliderGroupInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value !== null ? (string)$value : null;
    }

    public function setCreatedAt(string $createdAt): SliderGroupInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);
        return $value !== null ? (string)$value : null;
    }

    public function setUpdatedAt(string $updatedAt): SliderGroupInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
