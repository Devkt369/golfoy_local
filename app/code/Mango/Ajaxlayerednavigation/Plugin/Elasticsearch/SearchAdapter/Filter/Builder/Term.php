<?php
namespace Mango\Ajaxlayerednavigation\PLugin\Elasticsearch\SearchAdapter\Filter\Builder;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\Request\Filter\Term as TermFilterRequest;
use Magento\Framework\Search\Request\FilterInterface as RequestFilterInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ConverterInterface
as FieldTypeConverterInterface;

/**
 * Term filter builder
 */
class Term #implements FilterInterface
{
    /**
     * @var FieldMapperInterface
     */
    protected $fieldMapper;
    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;
    /**
     * @var array
     * @see \Magento\Elasticsearch\Elasticsearch5\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\Resolver\IntegerType::$integerTypeAttributes
     */
    private $integerTypeAttributes = ['category_ids'];
    protected $helper;
    /**
     * @param FieldMapperInterface $fieldMapper
     * @param AttributeProvider $attributeAdapterProvider
     * @param array $integerTypeAttributes
     */
    public function __construct(
        FieldMapperInterface $fieldMapper,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        AttributeProvider $attributeAdapterProvider = null,
        array $integerTypeAttributes = []
    ) {
        $this->fieldMapper = $fieldMapper;
        $this->helper = $helper;
        $this->attributeAdapterProvider = $attributeAdapterProvider ?? ObjectManager::getInstance()->get(AttributeProvider::class);
        $this->integerTypeAttributes = array_merge($this->integerTypeAttributes, $integerTypeAttributes);
    }
    /**
     * Build term filter request
     *
     * @param RequestFilterInterface|TermFilterRequest $filter
     * @return array
     */
    public function aroundBuildFilter(
        \Magento\Elasticsearch\SearchAdapter\Filter\Builder\Term $subject,
        \Closure $proceed,
        RequestFilterInterface $filter
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($filter);
        }
        /* if regular filter, not AND condition */
        if (!$this->helper->isAndCondition($filter->getField())) {
            return $proceed($filter);
        }

        #echo "<div style='background:#ccc5c7;'>";
        #echo "will build filter for . " . $filter->getField() . "..<br/>";
        #echo "</div>";
        #if ($filter->getField() == "climate") {
        #   echo get_class($filter) . " is the class of the filter<br/>";
        #$filter->setData( 'value', [154,143] );
        #}

        $filterQuery = [];
        $attribute = $this->attributeAdapterProvider->getByAttributeCode($filter->getField());
        $fieldName = $this->fieldMapper->getFieldName($filter->getField());
        if ($attribute->isTextType() && !in_array($attribute->getAttributeCode(), $this->integerTypeAttributes)) {
            $suffix = FieldTypeConverterInterface::INTERNAL_DATA_TYPE_KEYWORD;
            $fieldName .= '.' . $suffix;
            # echo "field is text...<br/>";
        }

        if ($filter->getValue()) {
            #print_r($filter->getValue());
            #echo "-----<br/>";

            $value = $filter->getValue();
            #    if($filter->getField()== "climate"){
            #echo get_class($filter) . " is the class of the filter<br/>";
            # $value = [154,143];
            #$filter->setData( 'value', [154,143] );
            foreach ($value as $x) {
                $operator = is_array($x) ? 'terms' : 'term';
                $filterQuery [] = [
                    $operator => [
                        $fieldName => $x, #$filter->getValue(),
                    ],
                ];
            }

            # }else{
            #  $operator = is_array($value) ? 'terms' : 'term';
            # $filterQuery []= [
            #    $operator => [
            #        $fieldName => $value , #$filter->getValue(),
            #    ],
            #];
            #}
            #$operator = is_array($value) ? 'terms' : 'term';
            #$filterQuery []= [
            #      $operator => [
            #            $fieldName => $value , #$filter->getValue(),
            #          ],
            #        ];
        }
        return $filterQuery;
    }
}
