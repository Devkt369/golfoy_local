<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Queued;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlInterface;

class TabMatrix extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $url;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        UrlInterface $url
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->url = $url;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $cronLogTable = $connection->getTableName('artm_massproducts_cron_log');

            $lastCronRun = null;
            if ($connection->isTableExists($cronLogTable)) {
                $lastCronRun = $connection->fetchOne(
                    $connection->select()
                        ->from($cronLogTable, ['last_run' => new \Zend_Db_Expr('MAX(finished_at)')])
                        ->where('triggered_by = ?', 'cron')
                );
            }

            $drafts = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable)
                    ->where('status = ?', 'queued')
                    ->order('attribute_set ASC')
                    ->order('entity_id ASC')
            );

            foreach ($drafts as &$draft) {
                $draft['last_cron_run'] = $lastCronRun ?: '';
            }
            unset($draft);

            return $result->setData(['success' => true, 'rows' => $drafts]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
