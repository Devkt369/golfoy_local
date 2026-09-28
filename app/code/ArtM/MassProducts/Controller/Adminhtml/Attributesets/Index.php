<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attributesets;

use ArtM\MassProducts\Model\AttributeSetResolver;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $attributeSetResolver;

    public function __construct(
        Context $context,
        AttributeSetResolver $attributeSetResolver
    ) {
        parent::__construct($context);
        $this->attributeSetResolver = $attributeSetResolver;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $result->setData([
            'success' => true,
            'attribute_sets' => array_values($this->attributeSetResolver->getProductAttributeSets()),
        ]);
    }
}
