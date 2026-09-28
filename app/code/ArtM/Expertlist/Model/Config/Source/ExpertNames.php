<?php
namespace ArtM\Expertlist\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\AttributeFactory;

class ExpertNames extends AbstractSource
{
    protected $attributeFactory;

    public function __construct(AttributeFactory $attributeFactory)
    {
        $this->attributeFactory = $attributeFactory;
    }

    public function getAllOptions()
    {
        $options = [];

        $attributeCode = 'expert_name'; // Replace with your actual attribute code

        $attributeModel = $this->attributeFactory->create()->loadByCode('catalog_product', $attributeCode);

        if ($attributeModel && $attributeModel->getName()) {
            foreach ($attributeModel->getSource()->getAllOptions() as $option) {
                $options[] = [
                    'value' => $option['label'],
                    'label' => $option['label'],
                ];
            }
        }

        return $options;
    }
}
