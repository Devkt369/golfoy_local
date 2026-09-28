<?php

declare(strict_types=1);

namespace ArtM\QueryChatBox\Model\Source\Config;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class BlkUnblk extends AbstractSource
{
    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $this->_options = [
            ['label' => 'Blocked', 'value' => '1'],
            ['label' => 'Unblocked', 'value' => '0']
        ];
        return $this->_options;
    }
}