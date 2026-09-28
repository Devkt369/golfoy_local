<?php
namespace ArtM\RecommendedFilter\Model\Source\Config;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class SyncStatus extends AbstractSource
{
    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $this->_options = [
            ['label' => 'Synced', 'value' => '1'],
            ['label' => 'Not Synced', 'value' => '0']
        ];
        return $this->_options;
    }
}