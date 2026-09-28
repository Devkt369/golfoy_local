<?php
/**
 * Class ReadHandler
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
 * Class ReadHandler
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class ReadHandler implements AttributeInterface
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
     * ReadHandler constructor.
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
     * ReadHandler execute
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
        $entityId = $entityData[$linkField];

        $entityData['store_ids'] = $this->ruleResource->getStoreIds($entityId);
        $entityData['customer_group_ids'] = $this->ruleResource->getCustomerGroupIds($entityId);

        return $entityData;
    }
}
