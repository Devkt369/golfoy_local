<?php
/**
 * Class SaveHandler
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Model\ResourceModel;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\AttributeInterface;

/**
 * Class SaveHandler
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class SaveHandler implements AttributeInterface
{
    /**
     * @var \Sparsh\AutoRelatedProducts\Model\ResourceModel\AutoRelatedRule|Rule
     */
    protected $ruleResource;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * SaveHandler constructor.
     *
     * @param \Sparsh\AutoRelatedProducts\Model\ResourceModel\AutoRelatedRule $ruleResource
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        AutoRelatedRule $ruleResource,
        MetadataPool $metadataPool
    ) {
        $this->ruleResource = $ruleResource;
        $this->metadataPool = $metadataPool;
    }

    /**
     * SaveHandler execute
     *
     * @param string $entityType
     * @param array $entityData
     * @param array $arguments
     *
     * @return array
     *
     * @throws \Exception
     */
    public function execute($entityType, $entityData, $arguments = [])
    {
        $linkField = $this->metadataPool->getMetadata($entityType)->getLinkField();
        if (isset($entityData['store_ids'])) {
            $storeIds = $entityData['store_ids'];
            if (!is_array($storeIds)) {
                $storeIds = explode(',', (string)$storeIds);
            }
            $this->ruleResource->bindRuleToEntity($entityData[$linkField], $storeIds, 'store');
        }

        if (isset($entityData['customer_group_ids'])) {
            $customerGroupIds = $entityData['customer_group_ids'];
            if (!is_array($customerGroupIds)) {
                $customerGroupIds = explode(',', (string)$customerGroupIds);
            }
            $this->ruleResource->bindRuleToEntity($entityData[$linkField], $customerGroupIds, 'customer_group');
        }
        return $entityData;
    }
}
