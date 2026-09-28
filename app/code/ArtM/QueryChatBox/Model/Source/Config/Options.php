<?php

declare(strict_types=1);

namespace ArtM\QueryChatBox\Model\Source\Config;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Options extends AbstractSource
{
    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $this->_options = [
            ['label' => 'New', 'value' => '1'],
            ['label' => 'Awaiting', 'value' => '2'],
            ['label' => 'Responded', 'value' => '3'],
            ['label' => 'Discard', 'value' => '4'],
            ['label' => 'Assigned', 'value' => '5']
        ];
        return $this->_options;
    }
}