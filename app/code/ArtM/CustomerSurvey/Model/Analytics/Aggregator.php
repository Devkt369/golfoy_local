<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Analytics;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Aggregator
{
    private const XML_PATH_WINDOW_DAYS = 'customersurvey/analytics/aggregation_days';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ResourceConnection $resource,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
    }

    public function aggregate(): void
    {
        $windowDays = (int)$this->scopeConfig->getValue(self::XML_PATH_WINDOW_DAYS);
        if ($windowDays <= 0) {
            $windowDays = 30;
        }

        $connection = $this->resource->getConnection();
        $statsTable = $this->resource->getTableName('artm_customersurvey_stats_daily');
        $answerStatsTable = $this->resource->getTableName('artm_customersurvey_stats_answer_daily');
        $eventTable = $this->resource->getTableName('artm_customersurvey_event');
        $responseTable = $this->resource->getTableName('artm_customersurvey_response');
        $responseItemTable = $this->resource->getTableName('artm_customersurvey_response_item');
        $stateTable = $this->resource->getTableName('artm_customersurvey_audience_state');

        $fromDate = gmdate('Y-m-d', strtotime('-' . $windowDays . ' days'));

        $connection->beginTransaction();
        try {
            $connection->delete($statsTable, ['stat_date >= ?' => $fromDate]);
            $connection->delete($answerStatsTable, ['stat_date >= ?' => $fromDate]);

            $statsRows = $connection->fetchAll(
                $connection->select()
                    ->from(['e' => $eventTable], [
                        'stat_date' => new \Zend_Db_Expr('DATE(e.created_at)'),
                        'survey_id' => 'e.survey_id',
                        'impressions' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "impression" THEN 1 ELSE 0 END)'),
                        'opens' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "popup_open" THEN 1 ELSE 0 END)'),
                        'responses' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "answered" THEN 1 ELSE 0 END)')
                    ])
                    ->where('e.created_at >= ?', $fromDate . ' 00:00:00')
                    ->group(['DATE(e.created_at)', 'e.survey_id'])
            );

            foreach ($statsRows as $row) {
                $this->insertOrUpdateStat($connection, $statsTable, [
                    'stat_date' => (string)$row['stat_date'],
                    'survey_id' => (int)$row['survey_id'],
                    'location_id' => 0,
                    'device' => 'all',
                    'customer_type' => 'all',
                    'impressions' => (int)$row['impressions'],
                    'opens' => (int)$row['opens'],
                    'responses' => (int)$row['responses'],
                ]);
            }

            $responseRows = $connection->fetchAll(
                $connection->select()
                    ->from(['r' => $responseTable], [
                        'stat_date' => new \Zend_Db_Expr('DATE(r.created_at)'),
                        'survey_id' => 'r.survey_id',
                        'location_id' => new \Zend_Db_Expr('COALESCE(r.location_id, 0)'),
                        'device' => new \Zend_Db_Expr('COALESCE(NULLIF(TRIM(r.device), ""), "unknown")'),
                        'customer_type' => new \Zend_Db_Expr('CASE WHEN r.customer_id IS NULL THEN "guest" ELSE "customer" END'),
                        'responses' => new \Zend_Db_Expr('COUNT(*)')
                    ])
                    ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                    ->group([
                        'DATE(r.created_at)',
                        'r.survey_id',
                        'COALESCE(r.location_id, 0)',
                        'COALESCE(NULLIF(TRIM(r.device), ""), "unknown")',
                        'CASE WHEN r.customer_id IS NULL THEN "guest" ELSE "customer" END'
                    ])
            );

            foreach ($responseRows as $row) {
                $this->insertOrUpdateStat($connection, $statsTable, [
                    'stat_date' => (string)$row['stat_date'],
                    'survey_id' => (int)$row['survey_id'],
                    'location_id' => (int)$row['location_id'],
                    'device' => substr((string)$row['device'], 0, 64),
                    'customer_type' => (string)$row['customer_type'],
                    'impressions' => 0,
                    'opens' => 0,
                    'responses' => (int)$row['responses'],
                ]);
            }

            $answerRows = $connection->fetchAll(
                $connection->select()
                    ->from(['ri' => $responseItemTable], [
                        'stat_date' => new \Zend_Db_Expr('DATE(r.created_at)'),
                        'survey_id' => 'r.survey_id',
                        'question_id' => 'ri.question_id',
                        'option_id' => new \Zend_Db_Expr('COALESCE(ri.option_id, 0)'),
                        'answer_type' => new \Zend_Db_Expr('CASE WHEN ri.option_id IS NULL OR ri.option_id = 0 THEN "text" ELSE "option" END'),
                        'answer_count' => new \Zend_Db_Expr('COUNT(*)')
                    ])
                    ->join(['r' => $responseTable], 'r.response_id = ri.response_id', [])
                    ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                    ->group([
                        'DATE(r.created_at)',
                        'r.survey_id',
                        'ri.question_id',
                        'COALESCE(ri.option_id, 0)',
                        'CASE WHEN ri.option_id IS NULL OR ri.option_id = 0 THEN "text" ELSE "option" END'
                    ])
            );

            foreach ($answerRows as $row) {
                $this->insertOrUpdateAnswerStat($connection, $answerStatsTable, [
                    'stat_date' => (string)$row['stat_date'],
                    'survey_id' => (int)$row['survey_id'],
                    'question_id' => (int)$row['question_id'],
                    'option_id' => (int)$row['option_id'],
                    'answer_type' => (string)$row['answer_type'],
                    'answer_count' => (int)$row['answer_count'],
                ]);
            }

            $this->syncAudienceCounters($connection, $eventTable, $stateTable);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
         }
     }

    /**
     * @param array<string, int|string> $row
     */
    private function insertOrUpdateStat(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $statsTable,
        array $row
    ): void {
        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');
        $row['created_at'] = $row['created_at'] ?? $now;
        $row['updated_at'] = $now;

        $connection->insertOnDuplicate(
            $statsTable,
            $row,
            ['impressions', 'opens', 'responses', 'updated_at']
        );
    }

    /**
     * @param array<string, int|string> $row
     */
    private function insertOrUpdateAnswerStat(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $answerStatsTable,
        array $row
    ): void {
        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');
        $row['created_at'] = $row['created_at'] ?? $now;
        $row['updated_at'] = $now;

        $connection->insertOnDuplicate(
            $answerStatsTable,
            $row,
            ['answer_count', 'updated_at']
        );
    }

    private function syncAudienceCounters(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $eventTable,
        string $stateTable
    ): void {
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['e' => $eventTable], [
                    'survey_id',
                    'visitor_key',
                    'impression_count' => new \Zend_Db_Expr('SUM(CASE WHEN event_type = "impression" THEN 1 ELSE 0 END)'),
                    'popup_open_count' => new \Zend_Db_Expr('SUM(CASE WHEN event_type = "popup_open" THEN 1 ELSE 0 END)'),
                    'close_count' => new \Zend_Db_Expr('SUM(CASE WHEN event_type = "closed" THEN 1 ELSE 0 END)'),
                    'answer_count' => new \Zend_Db_Expr('SUM(CASE WHEN event_type = "answered" THEN 1 ELSE 0 END)')
                ])
                ->group(['survey_id', 'visitor_key'])
        );

        foreach ($rows as $row) {
            $connection->update(
                $stateTable,
                [
                    'impression_count' => (int)$row['impression_count'],
                    'popup_open_count' => (int)$row['popup_open_count'],
                    'close_count' => (int)$row['close_count'],
                    'answer_count' => (int)$row['answer_count'],
                    'updated_at' => $this->dateTime->gmtDate('Y-m-d H:i:s'),
                ],
                [
                    'survey_id = ?' => (int)$row['survey_id'],
                    'visitor_key = ?' => (string)$row['visitor_key'],
                ]
            );
        }
    }
}
