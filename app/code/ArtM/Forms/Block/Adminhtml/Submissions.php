<?php
namespace ArtM\Forms\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use ArtM\Forms\Model\ResourceModel\FormSubmission\CollectionFactory;
use ArtM\Forms\Model\FormsFactory;

class Submissions extends \Magento\Backend\Block\Template
{
    protected $collectionFactory;
    protected $formsFactory;

    // Get unread and total counts for all forms
    public function getFormSubmissionStats()
    {
        $forms = $this->formsFactory->create()->getCollection();
        $collection = $this->collectionFactory->create();
        $stats = [];
        foreach ($forms as $form) {
            $identifier = $form->getData('form_identifier');
            $total = $collection->getNewEmptyItem()->getResource()->getConnection()->fetchOne(
                "SELECT COUNT(*) FROM artm_form_submissions WHERE form_identifier = ?",
                [$identifier]
            );
            $unread = $collection->getNewEmptyItem()->getResource()->getConnection()->fetchOne(
                "SELECT COUNT(*) FROM artm_form_submissions WHERE form_identifier = ? AND is_read = 0",
                [$identifier]
            );
            $stats[$identifier] = [
                'total' => (int)$total,
                'unread' => (int)$unread,
            ];
        }
        return $stats;
    }

    // Mark all submissions for a form as read
    public function markAllRead($identifier)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('form_identifier', $identifier);
        foreach ($collection as $item) {
            if ($item->getData('is_read') == 0) {
                $item->setData('is_read', 1);
                $item->save();
            }
        }
    }

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        FormsFactory $formsFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->formsFactory = $formsFactory;
        parent::__construct($context, $data);
    }

    public function getIdentifiersWithNames()
    {
        $forms = $this->formsFactory->create()->getCollection();
        $result = [];
        foreach ($forms as $form) {
            $result[] = [
                'identifier' => $form->getData('form_identifier'),
                'name' => $form->getData('form_name')
            ];
        }
        return $result;
    }

    public function getSubmissions($identifier = null, $params = [])
    {
        $collection = $this->collectionFactory->create();
        if ($identifier) {
            $collection->addFieldToFilter('form_identifier', $identifier);
        }
        // Only filter real DB columns in SQL
        $realColumns = ['id', 'created_at', 'form_identifier'];
        $phpFilters = [];
        if (!empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                if ($value !== '' && $value !== null) {
                    if (in_array($field, $realColumns)) {
                        $collection->addFieldToFilter($field, ['like' => "%$value%"]);
                    } else {
                        $phpFilters[$field] = $value;
                    }
                }
            }
        }
        // Sorting
        $sortField = $params['sort_field'] ?? 'created_at';
        $sortDir = strtoupper($params['sort_dir'] ?? 'DESC');
        if (!in_array($sortDir, ['ASC', 'DESC'])) $sortDir = 'DESC';
        $collection->setOrder($sortField, $sortDir);
        // Pagination
        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = max(1, (int)($params['page_size'] ?? 20));
        $collection->setCurPage($page);
        $collection->setPageSize($pageSize);
        // Filter dynamic fields in PHP
        if (!empty($phpFilters)) {
            $filtered = [];
            foreach ($collection as $item) {
                $data = $item->getData('submitted_data');
                $fields = @json_decode($data, true);
                $match = true;
                foreach ($phpFilters as $f => $v) {
                    if (!isset($fields[$f]) || stripos((is_array($fields[$f]) ? implode(',', $fields[$f]) : $fields[$f]), $v) === false) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    $filtered[] = $item;
                }
            }
            // Return filtered as array (not collection)
            return $filtered;
        }
        return $collection;
    }

    public function getTotalSubmissionsCount($identifier = null, $params = [])
    {
        $collection = $this->collectionFactory->create();
        if ($identifier) {
            $collection->addFieldToFilter('form_identifier', $identifier);
        }
        $realColumns = ['id', 'created_at', 'form_identifier'];
        $phpFilters = [];
        if (!empty($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                if ($value !== '' && $value !== null) {
                    if (in_array($field, $realColumns)) {
                        $collection->addFieldToFilter($field, ['like' => "%$value%"]);
                    } else {
                        $phpFilters[$field] = $value;
                    }
                }
            }
        }
        if (!empty($phpFilters)) {
            $count = 0;
            foreach ($collection as $item) {
                $data = $item->getData('submitted_data');
                $fields = @json_decode($data, true);
                $match = true;
                foreach ($phpFilters as $f => $v) {
                    if (!isset($fields[$f]) || stripos((is_array($fields[$f]) ? implode(',', $fields[$f]) : $fields[$f]), $v) === false) {
                        $match = false;
                        break;
                    }
                }
                if ($match) {
                    $count++;
                }
            }
            return $count;
        }
        return $collection->getSize();
    }

    /**
     * Returns available values for a field (for filter dropdowns)
     */
    public function getDistinctFieldValues($identifier, $field)
    {
        $collection = $this->collectionFactory->create();
        if ($identifier) {
            $collection->addFieldToFilter('form_identifier', $identifier);
        }
        $values = [];
        foreach ($collection as $submission) {
            $data = $submission->getData('submitted_data');
            $fields = @json_decode($data, true);
            if (isset($fields[$field])) {
                $val = $fields[$field];
                if (is_array($val)) {
                    foreach ($val as $v) {
                        $values[] = $v;
                    }
                } else {
                    $values[] = $val;
                }
            }
        }
        return array_unique(array_filter($values, function($v) { return $v !== '' && $v !== null; }));
    }

    public function getFormFields($identifier)
    {
        $form = $this->formsFactory->create()->getCollection()
            ->addFieldToFilter('form_identifier', $identifier)
            ->getFirstItem();
        $fields = [];
        if ($form && $form->getId() && $form->getData('form_fields')) {
            $fieldsArr = @json_decode($form->getData('form_fields'), true);
            if (is_array($fieldsArr)) {
                foreach ($fieldsArr as $field) {
                    if (!empty($field['name'])) {
                        $fields[] = $field['name'];
                    }
                }
            }
        }
        return $fields;
    }
}
