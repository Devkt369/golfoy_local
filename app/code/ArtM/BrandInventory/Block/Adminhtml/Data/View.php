<?php
namespace ArtM\BrandInventory\Block\Adminhtml\Data;

class View extends \Magento\Backend\Block\Template
{
    protected $resource;
    protected $request;
    protected $unfilteredRows;
    protected $unfilteredTotal;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resource = $resource;
        $this->request = $context->getRequest();
    }

    public function getBatchId()
    {
        return (int)$this->request->getParam('id');
    }

    public function getFilterLinkedSku()
    {
        return trim($this->request->getParam('filter_linked_sku', ''));
    }

    public function getFilterStatus()
    {
        return $this->request->getParam('filter_status', '');
    }

    public function getFilterSource()
    {
        return $this->request->getParam('filter_source', '');
    }

    public function getCurrentPage()
    {
        return (int)$this->request->getParam('page', 1);
    }

    public function getPageSize()
    {
        return (int)$this->request->getParam('pageSize', 50);
    }

    public function getTotalCount()
    {
        return (int)$this->getData('total_count');
    }

    public function getTotalPages()
    {
        $total = $this->getTotalCount();
        $size = $this->getPageSize();
        return (int)ceil($total / $size) ?: 1;
    }

    public function getBatchRows()
    {
        $batchId = $this->getBatchId();
        // REMOVE THIS LINE: if (!$batchId) return [];

        $filterLinkedSku = $this->getFilterLinkedSku();
        $filterStatus = $this->getFilterStatus();
        $filterSource = $this->getFilterSource();
        $page = $this->getCurrentPage();
        $pageSize = $this->getPageSize();
        $offset = ($page - 1) * $pageSize;

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_manual_match');

        // Build WHERE — batch_id is now optional
        $whereConditions = [];
        $whereParams = [];

        if ($batchId > 0) {
            $whereConditions[] = 'batch_id = ?';
            $whereParams[] = $batchId;
        }

        if ($filterLinkedSku !== '') {
            $whereConditions[] = 'linked_sku LIKE ?';
            $whereParams[] = '%' . $filterLinkedSku . '%';
        }
        if ($filterStatus !== '') {
            $whereConditions[] = 'status = ?';
            $whereParams[] = $filterStatus;
        }
        if ($filterSource !== '') {
            $whereConditions[] = 'source_code = ?';
            $whereParams[] = $filterSource;
        }

        $whereSql = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Total
        $totalRecords = (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} {$whereSql}",
            $whereParams
        );
        $this->setData('total_count', $totalRecords);

        // Fetch page
        $rows = $connection->fetchAll(
            "SELECT * FROM {$table} {$whereSql} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}",
            $whereParams
        );

        // Decode JSON
        foreach ($rows as &$row) {
            if (!empty($row['brand_created_sku'])) {
                $row['brand_created_sku_decoded'] = json_decode($row['brand_created_sku'], true) ?: [];
            } else {
                $row['brand_created_sku_decoded'] = [];
            }
        }

        return $rows;
    }

    public function getAllStatuses()
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_manual_match');

        return $connection->fetchCol(
            "SELECT DISTINCT status FROM {$table} ORDER BY status"
        );
    }

    public function getAllSources()
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_manual_match');

        return $connection->fetchCol(
            "SELECT DISTINCT source_code FROM {$table} ORDER BY source_code"
        );
    }

    public function getAllRows()
    {
        if ($this->unfilteredRows !== null) return $this->unfilteredRows;

        $page = $this->getCurrentPage();
        $pageSize = $this->getPageSize();
        $offset = ($page - 1) * $pageSize;

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_manual_match');

        $this->unfilteredTotal = (int)$connection->fetchOne(
            "SELECT COUNT(*) FROM {$table}"
        );

        $rows = $connection->fetchAll(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset}"
        );

        foreach ($rows as &$row) {
            if (!empty($row['brand_created_sku'])) {
                $row['brand_created_sku_decoded'] = json_decode($row['brand_created_sku'], true) ?: [];
            } else {
                $row['brand_created_sku_decoded'] = [];
            }
        }

        $this->unfilteredRows = $rows;
        return $rows;
    }

    public function getTotalCountUnfiltered()
    {
        if ($this->unfilteredTotal === null) {
            $this->getAllRows();
        }
        return $this->unfilteredTotal;
    }

    public function getTotalPagesUnfiltered()
    {
        $total = $this->getTotalCountUnfiltered();
        $size = $this->getPageSize();
        return (int)ceil($total / $size) ?: 1;
    }
}