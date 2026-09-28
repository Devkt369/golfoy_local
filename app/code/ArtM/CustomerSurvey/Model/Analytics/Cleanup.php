<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Analytics;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class Cleanup
{
    private const XML_PATH_RETENTION_DAYS = 'customersurvey/analytics/retention_days';
    private const XML_PATH_SUMMARY_RETENTION_DAYS = 'customersurvey/analytics/summary_retention_days';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->scopeConfig = $scopeConfig;
    }

    public function clean(): void
    {
        $retentionDays = (int)$this->scopeConfig->getValue(self::XML_PATH_RETENTION_DAYS);
        if ($retentionDays <= 0) {
            $retentionDays = 365;
        }

        $summaryRetentionDays = (int)$this->scopeConfig->getValue(self::XML_PATH_SUMMARY_RETENTION_DAYS);
        if ($summaryRetentionDays <= 0) {
            $summaryRetentionDays = 730;
        }

        $rawCutoff = gmdate('Y-m-d H:i:s', strtotime('-' . $retentionDays . ' days'));
        $summaryCutoffDate = gmdate('Y-m-d', strtotime('-' . $summaryRetentionDays . ' days'));

        $connection = $this->resource->getConnection();

        $eventTable = $this->resource->getTableName('artm_customersurvey_event');
        $responseTable = $this->resource->getTableName('artm_customersurvey_response');
        $rewardQueueTable = $this->resource->getTableName('artm_customersurvey_reward_queue');
        $aiQueueTable = $this->resource->getTableName('artm_customersurvey_ai_sentiment_queue');
        $statsTable = $this->resource->getTableName('artm_customersurvey_stats_daily');
        $answerStatsTable = $this->resource->getTableName('artm_customersurvey_stats_answer_daily');

        $connection->beginTransaction();
        try {
            $connection->delete($eventTable, ['created_at < ?' => $rawCutoff]);
            $connection->delete($responseTable, ['created_at < ?' => $rawCutoff]);
            $connection->delete($rewardQueueTable, ['created_at < ?' => $rawCutoff]);
            $connection->delete($aiQueueTable, ['created_at < ?' => $rawCutoff]);

            $connection->delete($statsTable, ['stat_date < ?' => $summaryCutoffDate]);
            $connection->delete($answerStatsTable, ['stat_date < ?' => $summaryCutoffDate]);

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
