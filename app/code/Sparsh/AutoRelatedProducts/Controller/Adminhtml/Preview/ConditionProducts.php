<?php
/**
 * Class ConditionProducts
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Controller\Adminhtml\Preview;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class ConditionProducts
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class ConditionProducts extends \Magento\Backend\App\Action
{
    /**
     * @var \Sparsh\AutoRelatedProducts\Model\AutoRelatedRuleFactory
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * ConditionProducts constructor.
     *
     * @param Context $context
     * @param \Sparsh\AutoRelatedProducts\Model\AutoRelatedRuleFactory $ruleFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        Context $context,
        \Sparsh\AutoRelatedProducts\Model\AutoRelatedRuleFactory $ruleFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context);
    }

    /**
     * Preview products for conditions action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $postParams = $this->getRequest()->getParams();
        $data = $this->prepareData($postParams);
        $model = $this->ruleFactory->create();
        $model->loadPost($data);

        $matchingProductIds = [];
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        foreach ($productCollection as $product) {
            if ($model->getConditions()->validate($product)) {
                $matchingProductIds[] = $product->getId();
            }
        }

        if(empty($matchingProductIds)){
            $matchingProductIds[] = 0;
        }

        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $resultLayout->getLayout()
            ->getBlock('product.conditions.list')
            ->setData('product_ids', $matchingProductIds);
        return $resultLayout;
    }

    /**
     * Prepares specific data
     *
     * @param $data
     *
     * @return mixed
     */
    protected function prepareData($data)
    {
        if (isset($data['rule']['conditions'])) {
            $data['conditions'] = $data['rule']['conditions'];
        }

        unset($data['rule']);

        return $data;
    }

    /**
     * Grid action
     *
     * @return void;
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
