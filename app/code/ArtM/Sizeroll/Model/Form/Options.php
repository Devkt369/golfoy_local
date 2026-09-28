<?php

namespace ArtM\Sizeroll\Model\Form;

use Magento\Framework\Option\ArrayInterface;

class Options implements ArrayInterface
{
    /**
     * @var array
     */
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => __('Disable')
            ],
            [
                'value' => '1',
                'label' => __('Enable')
            ]
        ];
    }
}