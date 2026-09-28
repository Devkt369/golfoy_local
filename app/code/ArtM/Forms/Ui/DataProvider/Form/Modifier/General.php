<?php
namespace ArtM\Forms\Ui\DataProvider\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class General implements ModifierInterface
{
    public function modifyData(array $data)
    {
        // You can add custom logic here if needed
        return $data;
    }

    public function modifyMeta(array $meta)
    {
        // You can add custom logic here if needed
        return $meta;
    }
}
