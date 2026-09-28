<?php
namespace ArtM\MassProducts\Block\Adminhtml\Draft;

use Magento\Catalog\Model\Product;
use ArtM\MassProducts\Model\Import\DraftCompletenessService;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Backend\Block\Template;
use Magento\Framework\App\ResourceConnection;

class Edit extends Template
{
    protected $_template = 'ArtM_MassProducts::draft/edit.phtml';

    private $resource;
    private $attributeRepository;
    private $searchCriteriaBuilder;
    private $completenessService;

    public function __construct(
        Template\Context $context,
        ResourceConnection $resource,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DraftCompletenessService $completenessService,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resource = $resource;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->completenessService = $completenessService;
    }

    public function getDraftId(): int
    {
        return (int)$this->getRequest()->getParam('id');
    }

    public function getDraft(): array
    {
        $draftId = $this->getDraftId();
        if ($draftId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_massproducts_draft');
        $row = $connection->fetchRow($connection->select()->from($table)->where('entity_id = ?', $draftId));
        return is_array($row) ? $row : [];
    }

    public function getDraftValues(): array
    {
        $draftId = $this->getDraftId();
        if ($draftId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_massproducts_draft_values');
        $rows = $connection->fetchAll(
            $connection->select()->from($table, ['attribute_code', 'value'])->where('draft_id = ?', $draftId)->where('store_id = ?', 0)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(string)$row['attribute_code']] = $row['value'];
        }
        return $result;
    }

    public function getAttributeGroupsWithAttributes(): array
    {
        $draft = $this->getDraft();
        $attributeSetId = (int)($draft['attribute_set_id'] ?? 0);
        if ($attributeSetId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $groupTable = $connection->getTableName('eav_attribute_group');
        $entityAttributeTable = $connection->getTableName('eav_entity_attribute');

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $repositoryItems = $this->attributeRepository->getList(Product::ENTITY, $searchCriteria)->getItems();
        $attributesById = [];
        foreach ($repositoryItems as $attribute) {
            $attributesById[(int)$attribute->getAttributeId()] = $attribute;
        }

        $groupRows = $connection->fetchAll(
            $connection->select()
                ->from($groupTable, ['attribute_group_id', 'attribute_group_name'])
                ->where('attribute_set_id = ?', $attributeSetId)
                ->order('sort_order ASC')
        );

        $groups = [];
        foreach ($groupRows as $groupRow) {
            $groupId = (int)$groupRow['attribute_group_id'];
            $groupAttrRows = $connection->fetchAll(
                $connection->select()
                    ->from(['ea' => $entityAttributeTable], ['attribute_id', 'sort_order'])
                    ->where('ea.attribute_set_id = ?', $attributeSetId)
                    ->where('ea.attribute_group_id = ?', $groupId)
                    ->order('ea.sort_order ASC')
            );

            $preparedAttrs = [];
            foreach ($groupAttrRows as $row) {
                $attributeId = (int)$row['attribute_id'];
                if (!isset($attributesById[$attributeId])) {
                    continue;
                }

                $attribute = $attributesById[$attributeId];
                if (!(bool)$attribute->getIsVisible() || !(bool)$attribute->getIsUserDefined()) {
                    continue;
                }

                $frontendInput = (string)$attribute->getFrontendInput();
                $frontendLabel = trim((string)$attribute->getDefaultFrontendLabel());

                $options = [];
                if (in_array($frontendInput, ['select', 'multiselect', 'boolean'], true)) {
                    if ($frontendInput === 'boolean') {
                        $options = [
                            ['value' => '1', 'label' => 'Yes'],
                            ['value' => '0', 'label' => 'No'],
                        ];
                    } else {
                        foreach ($attribute->getOptions() as $opt) {
                            $val = (string)$opt->getValue();
                            if ($val === '') {
                                continue;
                            }
                            $options[] = [
                                'value' => $val,
                                'label' => (string)$opt->getLabel(),
                            ];
                        }
                    }
                }

                $preparedAttrs[] = [
                    'attribute_code' => (string)$attribute->getAttributeCode(),
                    'frontend_label' => $frontendLabel !== '' ? $frontendLabel : (string)$attribute->getAttributeCode(),
                    'frontend_input' => $frontendInput,
                    'is_required' => (bool)$attribute->getIsRequired(),
                    'options' => $options,
                ];
            }

            $groups[] = [
                'group_name' => (string)$groupRow['attribute_group_name'],
                'attributes' => $preparedAttrs,
            ];
        }

        return $groups;
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('massproducts/draft/save');
    }

    public function getCompletionSummary(): array
    {
        $draft = $this->getDraft();
        if (empty($draft)) {
            return [
                'status' => 'incomplete',
                'missing_count' => 0,
                'filled_required' => 0,
                'total_required' => 0,
                'progress_percent' => 0,
            ];
        }

        return $this->completenessService->computeDraftCompleteness($draft);
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('massproducts/draft/index');
    }
}
