<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab;

/**
 * Cms page edit form main tab
 */
class Filters extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    public $_objectManager;
    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory|null
     */
    protected $_attributeFactory = null;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory|null
     */
    protected $_attributeOption = null;
    public $_framework = null;
    public $_attributeSetRepository = null;
    public $_attributeRepository = null;
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|null
     */
    protected $objectManager = null;
    /*
     * @var null|Array
     */
    protected $productTypes = null;
    public $productTypeConfig = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attributeOption,
        /** @delegation off */
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_attributeFactory = $attributeFactory;
        $this->_attributeOption = $attributeOption;
    }
    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('');
        $form->setValues($model->getData());
        $this->setForm($form);
        $this->setTemplate('edit/filters.phtml');
        return parent::_prepareForm();
    }
    /**
     * @return array
     */
    public function getProductVisibility()
    {
        return \Magento\Catalog\Model\Product\Visibility::getOptionArray();
    }
    /**
     * @return string
     */
    public function getJsData()
    {
        $attributeCodes = [];
        $attributeList = $this->getAttributesList();
        foreach ($attributeList as $attribute) {
            if (preg_match("/^[a-zA-Z0-9_]+\$/", $attribute['attribute_code'])) {
                if (isset($attribute['attribute_id'])) {
                    $attributeOptions = $this->getAttributeOptions($attribute['attribute_id']);
                    $options = [];
                    if (is_array($attributeOptions)) {
                        foreach ($attributeOptions as $attributeOption) {
                            if (!empty($attributeOption['value'])) {
                                $options[] = ['value' => isset($attributeOption['option_id']) ? $attributeOption['option_id'] : $attributeOption['value'], 'label' => isset($attributeOption['label']) ? $attributeOption['label'] : $attributeOption['value']];
                            }
                        }
                    }
                    if ($attribute['attribute_code'] != 'location') {
                        $attributeCodes[$attribute['attribute_code']] = $options;
                    }
                }
            }
        }
        return json_encode($attributeCodes);
    }
    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    public function cmp($a, $b)
    {
        return $a['frontend_label'] < $b['frontend_label'] ? -1 : 1;
    }
    /**
     * @return string
     */
    public function getSelectHtml()
    {
        $selectOutput = "";
        $attributeList = $this->getAttributesList();
        foreach ($attributeList as $attribute) {
            if (!empty($attribute['frontend_label'])) {
                $selectOutput .= "<option value='" . $attribute['attribute_code'] . "'>" . $attribute['frontend_label'] . "</option>";
            }
        }
        return $selectOutput;
    }
    /**
     * @return \Wyomind\Framework\Helper\type
     */
    public function getNbFilters()
    {
        return $this->_framework->getStoreConfig("datafeedmanager/system/filters");
    }
    /**
     * @return \Wyomind\Framework\Helper\type
     */
    public function getFiltersSql()
    {
        return $this->_framework->getStoreConfig("datafeedmanager/system/filters_sql");
    }
    /**
     * @return mixed
     */
    public function getDFMTypeIds()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getTypeIds();
    }
    /**
     * @return mixed
     */
    public function getDFMAttributeSets()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getAttributeSets();
    }
    /**
     * @return mixed
     */
    public function getDFMVisibilities()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getVisibilities();
    }
    /**
     * @return mixed
     */
    public function getDFMAttributes()
    {
        $model = $this->_coreRegistry->registry('data_feed');
        return $model->getAttributes();
    }
    /**
     * @return \Magento\Eav\Api\Data\AttributeSetInterface[]
     */
    public function getAttributeSets()
    {
        $typeCode = \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE;
        $filterGroup = $this->_objectManager->create('\\Magento\\Framework\\Api\\Search\\FilterGroup');
        $filter = $this->_objectManager->create('\\Magento\\Framework\\Api\\Filter');
        $filter->setField('entity_type_code');
        $filter->setConditionType('eq');
        $filter->setValue($typeCode);
        $filterGroup->setFilters([$filter]);
        $searchCriteria = $this->_objectManager->create('\\Magento\\Framework\\Api\\SearchCriteria');
        $searchCriteria->setFilterGroups([$filterGroup]);
        return $this->_attributeSetRepository->getList($searchCriteria)->getItems();
    }
    /**
     * @param $attId
     * @return array
     */
    public function getAttributeOptions($attId)
    {
        $att = $this->_attributeFactory->create()->load($attId);
        if ($att->getSourceModel() != "") {
            try {
                return $att->getSource()->getAllOptions();
            } catch (\Exception $e) {
                return [];
            }
        } else {
            $coll = $this->_attributeOption->create();
            return $coll->setAttributeFilter($attId)->setStoreFilter($this->getStoreId())->getData();
        }
    }
    /**
     * @return array|\Magento\Eav\Api\Data\AttributeInterface[]
     */
    public function getAttributesList()
    {
        $typeCode = \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE;
        $searchCriteria = $this->_objectManager->create('\\Magento\\Framework\\Api\\SearchCriteria');
        $attributeList = $this->_attributeRepository->getList($typeCode, $searchCriteria)->getItems();
        $tmp = [];
        foreach ($attributeList as $attribute) {
            $tmp[] = ['attribute_id' => $attribute->getAttributeId(), 'attribute_code' => $attribute->getAttributeCode(), 'frontend_label' => $attribute->getDefaultFrontendLabel()];
        }
        $attributeList[] = ['attribute_code' => 'entity_id', 'frontend_label' => 'Product Id'];
        $attributeList[] = ['attribute_code' => 'qty', 'frontend_label' => 'Quantity'];
        $attributeList[] = ['attribute_code' => 'is_in_stock', 'frontend_label' => 'Is in stock'];
        $attributeList[] = ['attribute_code' => 'status', 'frontend_label' => 'Status'];
        usort($attributeList, ['\\Wyomind\\DataFeedManager\\Block\\Adminhtml\\Feeds\\Edit\\Tab\\Filters', 'cmp']);
        return $attributeList;
    }
    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Filters');
    }
    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('Filters');
    }
    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }
    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
    /**
     * @return array
     */
    public function getProductTypes()
    {
        if ($this->productTypes === null) {
            $productTypes = [];
            foreach ($this->productTypeConfig->getAll() as $productTypeData) {
                $productTypes[$productTypeData['name']] = $productTypeData['label'];
            }
            $this->productTypes = $productTypes;
        }
        return $this->productTypes;
    }
}