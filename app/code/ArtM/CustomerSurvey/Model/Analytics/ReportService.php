<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Analytics;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

class ReportService
{
    private const XML_PATH_CACHE_ENABLE = 'customersurvey/analytics/enable_cache';
    private const XML_PATH_CACHE_TTL = 'customersurvey/analytics/cache_ttl';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ResourceConnection $resource,
        CacheInterface $cache,
        Json $json,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->cache = $cache;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSurveyListing(string $fromDate, string $toDate, int $page = 1, int $pageSize = 20): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(200, $pageSize));

        $cacheKey = $this->buildCacheKey('listing', [$fromDate, $toDate, $page, $pageSize]);
        $cached = $this->loadCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $connection = $this->resource->getConnection();
        $surveyTable = $this->resource->getTableName('artm_customersurvey_survey');
        $statsTable = $this->resource->getTableName('artm_customersurvey_stats_daily');
        $eventTable = $this->resource->getTableName('artm_customersurvey_event');
        $responseTable = $this->resource->getTableName('artm_customersurvey_response');

        $statsSelect = $connection->select()
            ->from(
                ['sd' => $statsTable],
                [
                    'survey_id',
                    'impressions' => new \Zend_Db_Expr('SUM(sd.impressions)'),
                    'opens' => new \Zend_Db_Expr('SUM(sd.opens)'),
                    'responses' => new \Zend_Db_Expr('SUM(sd.responses)')
                ]
            )
            ->where('sd.stat_date >= ?', $fromDate)
            ->where('sd.stat_date <= ?', $toDate)
            ->where('sd.device = ?', 'all')
            ->where('sd.customer_type = ?', 'all')
            ->where('sd.location_id = ?', 0)
            ->group('sd.survey_id');

        $total = (int)$connection->fetchOne(
            $connection->select()->from($surveyTable, [new \Zend_Db_Expr('COUNT(*)')])
        );

        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['s' => $surveyTable], ['survey_id', 'name', 'status'])
                ->joinLeft(['agg' => $statsSelect], 's.survey_id = agg.survey_id', [
                    'impressions' => new \Zend_Db_Expr('COALESCE(agg.impressions, 0)'),
                    'opens' => new \Zend_Db_Expr('COALESCE(agg.opens, 0)'),
                    'responses' => new \Zend_Db_Expr('COALESCE(agg.responses, 0)')
                ])
                ->order('s.priority DESC')
                ->order('s.sort_order ASC')
                ->limit($pageSize, ($page - 1) * $pageSize)
        );

        $eventAggRows = $connection->fetchAll(
            $connection->select()
                ->from(['e' => $eventTable], [
                    'survey_id',
                    'impressions' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "impression" THEN 1 ELSE 0 END)'),
                    'opens' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "popup_open" THEN 1 ELSE 0 END)'),
                    'responses' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "answered" THEN 1 ELSE 0 END)')
                ])
                ->where('e.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('e.created_at <= ?', $toDate . ' 23:59:59')
                ->group('e.survey_id')
        );

        $responseAggRows = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'survey_id',
                    'responses' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group('r.survey_id')
        );

        $eventAggBySurvey = [];
        foreach ($eventAggRows as $eventRow) {
            $eventAggBySurvey[(int)$eventRow['survey_id']] = [
                'impressions' => (int)$eventRow['impressions'],
                'opens' => (int)$eventRow['opens'],
                'responses' => (int)$eventRow['responses'],
            ];
        }

        $responseAggBySurvey = [];
        foreach ($responseAggRows as $responseRow) {
            $responseAggBySurvey[(int)$responseRow['survey_id']] = (int)$responseRow['responses'];
        }

        $items = [];
        foreach ($rows as $row) {
            $surveyId = (int)$row['survey_id'];
            $statImpressions = (int)$row['impressions'];
            $statOpens = (int)$row['opens'];
            $statResponses = (int)$row['responses'];

            $eventAgg = $eventAggBySurvey[$surveyId] ?? ['impressions' => 0, 'opens' => 0, 'responses' => 0];
            $realImpressions = (int)$eventAgg['impressions'];
            $realOpens = (int)$eventAgg['opens'];
            $realResponses = max((int)$eventAgg['responses'], (int)($responseAggBySurvey[$surveyId] ?? 0));

            $useRealtime = ($realImpressions + $realOpens + $realResponses) > 0;
            $impressions = $useRealtime ? $realImpressions : $statImpressions;
            $opens = $useRealtime ? $realOpens : $statOpens;
            $responses = $useRealtime ? $realResponses : $statResponses;
            $base = $impressions;
            $completionRate = $base > 0 ? round(($responses * 100) / $base, 2) : 0.0;

            $items[] = [
                'survey_id' => $surveyId,
                'name' => (string)$row['name'],
                'status' => (string)$row['status'],
                'impressions' => $impressions,
                'opens' => $opens,
                'responses' => $responses,
                'completion_rate' => $completionRate,
            ];
        }

        $totals = $connection->fetchRow(
            $connection->select()
                ->from(['sd' => $statsTable], [
                    'impressions' => new \Zend_Db_Expr('COALESCE(SUM(sd.impressions), 0)'),
                    'opens' => new \Zend_Db_Expr('COALESCE(SUM(sd.opens), 0)'),
                    'responses' => new \Zend_Db_Expr('COALESCE(SUM(sd.responses), 0)')
                ])
                ->where('sd.stat_date >= ?', $fromDate)
                ->where('sd.stat_date <= ?', $toDate)
                ->where('sd.device = ?', 'all')
                ->where('sd.customer_type = ?', 'all')
                ->where('sd.location_id = ?', 0)
        ) ?: ['impressions' => 0, 'opens' => 0, 'responses' => 0];

        $eventTotals = $connection->fetchRow(
            $connection->select()
                ->from(['e' => $eventTable], [
                    'impressions' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "impression" THEN 1 ELSE 0 END)'),
                    'opens' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "popup_open" THEN 1 ELSE 0 END)'),
                    'responses' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "answered" THEN 1 ELSE 0 END)')
                ])
                ->where('e.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('e.created_at <= ?', $toDate . ' 23:59:59')
        ) ?: ['impressions' => 0, 'opens' => 0, 'responses' => 0];

        $responseTotal = (int)$connection->fetchOne(
            $connection->select()
                ->from(['r' => $responseTable], [new \Zend_Db_Expr('COUNT(*)')])
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
        );

        $realTotals = [
            'impressions' => (int)($eventTotals['impressions'] ?? 0),
            'opens' => (int)($eventTotals['opens'] ?? 0),
            'responses' => max((int)($eventTotals['responses'] ?? 0), $responseTotal),
        ];

        if (($realTotals['impressions'] + $realTotals['opens'] + $realTotals['responses']) > 0) {
            $totals = $realTotals;
        }

        $trendRows = $connection->fetchAll(
            $connection->select()
                ->from(['sd' => $statsTable], [
                    'date' => 'sd.stat_date',
                    'impressions' => new \Zend_Db_Expr('COALESCE(SUM(sd.impressions), 0)'),
                    'opens' => new \Zend_Db_Expr('COALESCE(SUM(sd.opens), 0)'),
                    'responses' => new \Zend_Db_Expr('COALESCE(SUM(sd.responses), 0)')
                ])
                ->where('sd.stat_date >= ?', $fromDate)
                ->where('sd.stat_date <= ?', $toDate)
                ->where('sd.device = ?', 'all')
                ->where('sd.customer_type = ?', 'all')
                ->where('sd.location_id = ?', 0)
                ->group('sd.stat_date')
                ->order('sd.stat_date ASC')
        );

        $eventTrendRows = $connection->fetchAll(
            $connection->select()
                ->from(['e' => $eventTable], [
                    'date' => new \Zend_Db_Expr('DATE(e.created_at)'),
                    'impressions' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "impression" THEN 1 ELSE 0 END)'),
                    'opens' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "popup_open" THEN 1 ELSE 0 END)'),
                    'responses' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "answered" THEN 1 ELSE 0 END)')
                ])
                ->where('e.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('e.created_at <= ?', $toDate . ' 23:59:59')
                ->group(new \Zend_Db_Expr('DATE(e.created_at)'))
                ->order(new \Zend_Db_Expr('DATE(e.created_at) ASC'))
        );

        $responseTrendRows = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'date' => new \Zend_Db_Expr('DATE(r.created_at)'),
                    'responses' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group(new \Zend_Db_Expr('DATE(r.created_at)'))
                ->order(new \Zend_Db_Expr('DATE(r.created_at) ASC'))
        );

        $trendByDate = [];
        foreach ($eventTrendRows as $trendRow) {
            $date = (string)$trendRow['date'];
            $trendByDate[$date] = [
                'date' => $date,
                'impressions' => (int)$trendRow['impressions'],
                'opens' => (int)$trendRow['opens'],
                'responses' => (int)$trendRow['responses'],
            ];
        }
        foreach ($responseTrendRows as $trendRow) {
            $date = (string)$trendRow['date'];
            if (!isset($trendByDate[$date])) {
                $trendByDate[$date] = [
                    'date' => $date,
                    'impressions' => 0,
                    'opens' => 0,
                    'responses' => 0,
                ];
            }
            $trendByDate[$date]['responses'] = max((int)$trendByDate[$date]['responses'], (int)$trendRow['responses']);
        }

        if (!empty($trendByDate)) {
            ksort($trendByDate);
            $trendRows = array_values($trendByDate);
        }

        if (empty($trendRows) && ((int)$totals['impressions'] > 0 || (int)$totals['opens'] > 0 || (int)$totals['responses'] > 0)) {
            $trendRows = [[
                'date' => $toDate,
                'impressions' => (int)$totals['impressions'],
                'opens' => (int)$totals['opens'],
                'responses' => (int)$totals['responses'],
            ]];
        }

        $result = [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'totals' => [
                'impressions' => (int)$totals['impressions'],
                'opens' => (int)$totals['opens'],
                'responses' => (int)$totals['responses'],
            ],
            'trend' => $trendRows,
        ];

        $this->saveCache($cacheKey, $result);
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSurveyDetail(
        int $surveyId,
        string $fromDate,
        string $toDate,
        int $responsePage = 1,
        int $responsePageSize = 20
    ): array
    {
        if ($surveyId <= 0) {
            throw new LocalizedException(__('Survey ID is required.'));
        }

        $responsePage = max(1, $responsePage);
        $responsePageSize = max(1, min(100, $responsePageSize));

        $cacheKey = $this->buildCacheKey('detail', [$surveyId, $fromDate, $toDate, $responsePage, $responsePageSize]);
        $cached = $this->loadCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $connection = $this->resource->getConnection();
        $surveyTable = $this->resource->getTableName('artm_customersurvey_survey');
        $statsDailyTable = $this->resource->getTableName('artm_customersurvey_stats_daily');
        $statsAnswerTable = $this->resource->getTableName('artm_customersurvey_stats_answer_daily');
        $eventTable = $this->resource->getTableName('artm_customersurvey_event');
        $responseTable = $this->resource->getTableName('artm_customersurvey_response');
        $responseItemTable = $this->resource->getTableName('artm_customersurvey_response_item');
        $questionTable = $this->resource->getTableName('artm_customersurvey_question');
        $optionTable = $this->resource->getTableName('artm_customersurvey_question_option');
        $locationTable = $this->resource->getTableName('artm_customersurvey_location');
        $customerEntityTable = $this->resource->getTableName('customer_entity');
        $surveyQuestionTable = $this->resource->getTableName('artm_customersurvey_survey_question');

        $survey = $connection->fetchRow(
            $connection->select()
                ->from($surveyTable, ['survey_id', 'name', 'status'])
                ->where('survey_id = ?', $surveyId)
                ->limit(1)
        );
        if (!$survey) {
            throw new LocalizedException(__('Survey does not exist.'));
        }

        $totals = $connection->fetchRow(
            $connection->select()
                ->from(['sd' => $statsDailyTable], [
                    'impressions' => new \Zend_Db_Expr('COALESCE(SUM(sd.impressions), 0)'),
                    'opens' => new \Zend_Db_Expr('COALESCE(SUM(sd.opens), 0)'),
                    'responses' => new \Zend_Db_Expr('COALESCE(SUM(sd.responses), 0)'),
                ])
                ->where('sd.survey_id = ?', $surveyId)
                ->where('sd.stat_date >= ?', $fromDate)
                ->where('sd.stat_date <= ?', $toDate)
                ->where('sd.device = ?', 'all')
                ->where('sd.customer_type = ?', 'all')
                ->where('sd.location_id = ?', 0)
        ) ?: ['impressions' => 0, 'opens' => 0, 'responses' => 0];

        $eventTotals = $connection->fetchRow(
            $connection->select()
                ->from(['e' => $eventTable], [
                    'impressions' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "impression" THEN 1 ELSE 0 END)'),
                    'opens' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "popup_open" THEN 1 ELSE 0 END)'),
                    'responses' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "answered" THEN 1 ELSE 0 END)')
                ])
                ->where('e.survey_id = ?', $surveyId)
                ->where('e.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('e.created_at <= ?', $toDate . ' 23:59:59')
        ) ?: ['impressions' => 0, 'opens' => 0, 'responses' => 0];

        $responseTotal = (int)$connection->fetchOne(
            $connection->select()
                ->from(['r' => $responseTable], [new \Zend_Db_Expr('COUNT(*)')])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
        );

        $realTotals = [
            'impressions' => (int)($eventTotals['impressions'] ?? 0),
            'opens' => (int)($eventTotals['opens'] ?? 0),
            'responses' => max((int)($eventTotals['responses'] ?? 0), $responseTotal),
        ];

        if (($realTotals['impressions'] + $realTotals['opens'] + $realTotals['responses']) > 0) {
            $totals = $realTotals;
        }

        $surveyQuestions = $connection->fetchAll(
            $connection->select()
                ->from(['sq' => $surveyQuestionTable], ['question_id'])
                ->join(['q' => $questionTable], 'q.question_id = sq.question_id', ['question_title' => 'q.title'])
                ->where('sq.survey_id = ?', $surveyId)
                ->order('sq.question_order ASC')
        );

        $questionIds = array_values(array_filter(array_map(static function (array $row): int {
            return (int)($row['question_id'] ?? 0);
        }, $surveyQuestions)));

        $questionOptions = [];
        if (!empty($questionIds)) {
            $optionRows = $connection->fetchAll(
                $connection->select()
                    ->from($optionTable, ['question_id', 'option_id', 'title'])
                    ->where('question_id IN (?)', $questionIds)
                    ->order('sort_order ASC')
                    ->order('option_id ASC')
            );

            foreach ($optionRows as $optionRow) {
                $qId = (int)($optionRow['question_id'] ?? 0);
                if ($qId <= 0) {
                    continue;
                }
                $questionOptions[$qId][] = [
                    'option_id' => (int)($optionRow['option_id'] ?? 0),
                    'option_title' => (string)($optionRow['title'] ?? ''),
                ];
            }
        }

        $answerDistribution = $connection->fetchAll(
            $connection->select()
                ->from(['sad' => $statsAnswerTable], [
                    'question_id',
                    'option_id',
                    'answer_type',
                    'answer_count' => new \Zend_Db_Expr('SUM(sad.answer_count)')
                ])
                ->join(['q' => $questionTable], 'q.question_id = sad.question_id', ['question_title' => 'q.title'])
                ->joinLeft(['qo' => $optionTable], 'qo.option_id = sad.option_id', ['option_title' => 'qo.title'])
                ->where('sad.survey_id = ?', $surveyId)
                ->where('sad.stat_date >= ?', $fromDate)
                ->where('sad.stat_date <= ?', $toDate)
                ->group(['sad.question_id', 'sad.option_id', 'sad.answer_type'])
                ->order('sad.question_id ASC')
                ->order('answer_count DESC')
        );

        $liveAnswerDistribution = $connection->fetchAll(
            $connection->select()
                ->from(['ri' => $responseItemTable], [
                    'question_id' => 'ri.question_id',
                    'option_id' => new \Zend_Db_Expr('COALESCE(ri.option_id, 0)'),
                    'answer_type' => new \Zend_Db_Expr('CASE WHEN ri.option_id IS NULL OR ri.option_id = 0 THEN "text" ELSE "option" END'),
                    'answer_count' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->join(['r' => $responseTable], 'r.response_id = ri.response_id', [])
                ->join(['q' => $questionTable], 'q.question_id = ri.question_id', ['question_title' => 'q.title'])
                ->joinLeft(['qo' => $optionTable], 'qo.option_id = ri.option_id', ['option_title' => 'qo.title'])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group(['ri.question_id', new \Zend_Db_Expr('COALESCE(ri.option_id, 0)'), new \Zend_Db_Expr('CASE WHEN ri.option_id IS NULL OR ri.option_id = 0 THEN "text" ELSE "option" END')])
                ->order('ri.question_id ASC')
                ->order('answer_count DESC')
        );

        if (!empty($liveAnswerDistribution)) {
            $answerDistribution = $liveAnswerDistribution;
        }

        $answerCountsByQuestionOption = [];
        $textCountsByQuestion = [];
        foreach ($answerDistribution as $answerRow) {
            $qId = (int)($answerRow['question_id'] ?? 0);
            $oId = (int)($answerRow['option_id'] ?? 0);
            $count = (int)($answerRow['answer_count'] ?? 0);
            $type = (string)($answerRow['answer_type'] ?? '');

            if ($qId <= 0) {
                continue;
            }

            if ($type === 'text' || $oId === 0) {
                $textCountsByQuestion[$qId] = ($textCountsByQuestion[$qId] ?? 0) + $count;
                continue;
            }

            if (!isset($answerCountsByQuestionOption[$qId])) {
                $answerCountsByQuestionOption[$qId] = [];
            }
            $answerCountsByQuestionOption[$qId][$oId] = ($answerCountsByQuestionOption[$qId][$oId] ?? 0) + $count;
        }

        $normalizedAnswerDistribution = [];
        foreach ($surveyQuestions as $surveyQuestion) {
            $qId = (int)($surveyQuestion['question_id'] ?? 0);
            if ($qId <= 0) {
                continue;
            }

            $questionTitle = (string)($surveyQuestion['question_title'] ?? '');
            $optionsForQuestion = $questionOptions[$qId] ?? [];

            if (!empty($optionsForQuestion)) {
                foreach ($optionsForQuestion as $option) {
                    $oId = (int)($option['option_id'] ?? 0);
                    $normalizedAnswerDistribution[] = [
                        'question_id' => $qId,
                        'question_title' => $questionTitle,
                        'option_id' => $oId,
                        'option_title' => (string)($option['option_title'] ?? ''),
                        'answer_type' => 'option',
                        'answer_count' => (int)($answerCountsByQuestionOption[$qId][$oId] ?? 0),
                    ];
                }
                continue;
            }

            $normalizedAnswerDistribution[] = [
                'question_id' => $qId,
                'question_title' => $questionTitle,
                'option_id' => 0,
                'option_title' => 'Text',
                'answer_type' => 'text',
                'answer_count' => (int)($textCountsByQuestion[$qId] ?? 0),
            ];
        }

        if (!empty($normalizedAnswerDistribution)) {
            $answerDistribution = $normalizedAnswerDistribution;
        }

        $textResponses = $connection->fetchAll(
            $connection->select()
                ->from(['ri' => $responseItemTable], [
                    'question_id',
                    'answer_text',
                    'created_at' => 'r.created_at'
                ])
                ->join(['r' => $responseTable], 'r.response_id = ri.response_id', [])
                ->join(['q' => $questionTable], 'q.question_id = ri.question_id', ['question_title' => 'q.title'])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->where('ri.answer_text IS NOT NULL')
                ->where('TRIM(ri.answer_text) <> ?', '')
                ->order('r.created_at DESC')
                ->limit(300)
        );

        $locationPerformance = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'location_id' => new \Zend_Db_Expr('COALESCE(r.location_id, 0)'),
                    'responses' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->joinLeft(['l' => $locationTable], 'l.location_id = r.location_id', ['location_name' => 'l.name'])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group('location_id')
                ->order('responses DESC')
        );

        $deviceAnalytics = $connection->fetchAll(
            $connection->select()
                ->from(['sd' => $statsDailyTable], [
                    'device',
                    'impressions' => new \Zend_Db_Expr('SUM(sd.impressions)'),
                    'opens' => new \Zend_Db_Expr('SUM(sd.opens)'),
                    'responses' => new \Zend_Db_Expr('SUM(sd.responses)')
                ])
                ->where('sd.survey_id = ?', $surveyId)
                ->where('sd.stat_date >= ?', $fromDate)
                ->where('sd.stat_date <= ?', $toDate)
                ->where('sd.device <> ?', 'all')
                ->group('sd.device')
                ->order('responses DESC')
        );

        $liveDeviceAnalytics = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'device' => new \Zend_Db_Expr('COALESCE(NULLIF(TRIM(r.device), ""), "unknown")'),
                    'impressions' => new \Zend_Db_Expr('0'),
                    'opens' => new \Zend_Db_Expr('0'),
                    'responses' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group(new \Zend_Db_Expr('COALESCE(NULLIF(TRIM(r.device), ""), "unknown")'))
                ->order('responses DESC')
        );

        if (!empty($liveDeviceAnalytics)) {
            $deviceAnalytics = $liveDeviceAnalytics;
        }

        $customerTypeAnalytics = $connection->fetchAll(
            $connection->select()
                ->from(['sd' => $statsDailyTable], [
                    'customer_type',
                    'impressions' => new \Zend_Db_Expr('SUM(sd.impressions)'),
                    'opens' => new \Zend_Db_Expr('SUM(sd.opens)'),
                    'responses' => new \Zend_Db_Expr('SUM(sd.responses)')
                ])
                ->where('sd.survey_id = ?', $surveyId)
                ->where('sd.stat_date >= ?', $fromDate)
                ->where('sd.stat_date <= ?', $toDate)
                ->where('sd.customer_type <> ?', 'all')
                ->group('sd.customer_type')
                ->order('responses DESC')
        );

        $liveCustomerTypeAnalytics = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'customer_type' => new \Zend_Db_Expr('CASE WHEN r.customer_id IS NULL THEN "guest" ELSE "customer" END'),
                    'impressions' => new \Zend_Db_Expr('0'),
                    'opens' => new \Zend_Db_Expr('0'),
                    'responses' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group(new \Zend_Db_Expr('CASE WHEN r.customer_id IS NULL THEN "guest" ELSE "customer" END'))
                ->order('responses DESC')
        );

        if (!empty($liveCustomerTypeAnalytics)) {
            $customerTypeAnalytics = $liveCustomerTypeAnalytics;
        }

        $dateTrend = $connection->fetchAll(
            $connection->select()
                ->from(['sd' => $statsDailyTable], [
                    'date' => 'sd.stat_date',
                    'impressions' => new \Zend_Db_Expr('SUM(sd.impressions)'),
                    'opens' => new \Zend_Db_Expr('SUM(sd.opens)'),
                    'responses' => new \Zend_Db_Expr('SUM(sd.responses)')
                ])
                ->where('sd.survey_id = ?', $surveyId)
                ->where('sd.stat_date >= ?', $fromDate)
                ->where('sd.stat_date <= ?', $toDate)
                ->where('sd.device = ?', 'all')
                ->where('sd.customer_type = ?', 'all')
                ->where('sd.location_id = ?', 0)
                ->group('sd.stat_date')
                ->order('sd.stat_date ASC')
        );

        $eventTrendRows = $connection->fetchAll(
            $connection->select()
                ->from(['e' => $eventTable], [
                    'date' => new \Zend_Db_Expr('DATE(e.created_at)'),
                    'impressions' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "impression" THEN 1 ELSE 0 END)'),
                    'opens' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "popup_open" THEN 1 ELSE 0 END)'),
                    'responses' => new \Zend_Db_Expr('SUM(CASE WHEN e.event_type = "answered" THEN 1 ELSE 0 END)')
                ])
                ->where('e.survey_id = ?', $surveyId)
                ->where('e.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('e.created_at <= ?', $toDate . ' 23:59:59')
                ->group(new \Zend_Db_Expr('DATE(e.created_at)'))
                ->order(new \Zend_Db_Expr('DATE(e.created_at) ASC'))
        );

        $responseTrendRows = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'date' => new \Zend_Db_Expr('DATE(r.created_at)'),
                    'responses' => new \Zend_Db_Expr('COUNT(*)')
                ])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group(new \Zend_Db_Expr('DATE(r.created_at)'))
                ->order(new \Zend_Db_Expr('DATE(r.created_at) ASC'))
        );

        $dateTrendByDate = [];
        foreach ($eventTrendRows as $trendRow) {
            $date = (string)$trendRow['date'];
            $dateTrendByDate[$date] = [
                'date' => $date,
                'impressions' => (int)$trendRow['impressions'],
                'opens' => (int)$trendRow['opens'],
                'responses' => (int)$trendRow['responses'],
            ];
        }
        foreach ($responseTrendRows as $trendRow) {
            $date = (string)$trendRow['date'];
            if (!isset($dateTrendByDate[$date])) {
                $dateTrendByDate[$date] = [
                    'date' => $date,
                    'impressions' => 0,
                    'opens' => 0,
                    'responses' => 0,
                ];
            }
            $dateTrendByDate[$date]['responses'] = max((int)$dateTrendByDate[$date]['responses'], (int)$trendRow['responses']);
        }

        if (!empty($dateTrendByDate)) {
            ksort($dateTrendByDate);
            $dateTrend = array_values($dateTrendByDate);
        }

        if (empty($dateTrend) && ((int)$totals['impressions'] > 0 || (int)$totals['opens'] > 0 || (int)$totals['responses'] > 0)) {
            $dateTrend = [[
                'date' => $toDate,
                'impressions' => (int)$totals['impressions'],
                'opens' => (int)$totals['opens'],
                'responses' => (int)$totals['responses'],
            ]];
        }

        $responseTotal = (int)$connection->fetchOne(
            $connection->select()
                ->from(['r' => $responseTable], [new \Zend_Db_Expr('COUNT(*)')])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
        );

        $responseRows = $connection->fetchAll(
            $connection->select()
                ->from(['r' => $responseTable], [
                    'response_id',
                    'customer_id',
                    'guest_identifier',
                    'device',
                    'browser',
                    'ip_address',
                    'created_at',
                ])
                ->joinLeft(
                    ['ce' => $customerEntityTable],
                    'ce.entity_id = r.customer_id',
                    [
                        'customer_email' => 'email',
                        'customer_firstname' => 'firstname',
                        'customer_lastname' => 'lastname',
                    ]
                )
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->order('r.created_at DESC')
                ->limit($responsePageSize, ($responsePage - 1) * $responsePageSize)
        );

        $responseIds = array_values(array_filter(array_map(static function (array $row): int {
            return (int)($row['response_id'] ?? 0);
        }, $responseRows)));

        $answersByResponse = [];
        if (!empty($responseIds)) {
            $answerRows = $connection->fetchAll(
                $connection->select()
                    ->from(['ri' => $responseItemTable], [
                        'response_id',
                        'question_id',
                        'answer_text',
                        'option_id',
                    ])
                    ->join(['q' => $questionTable], 'q.question_id = ri.question_id', ['question_title' => 'q.title'])
                    ->joinLeft(['qo' => $optionTable], 'qo.option_id = ri.option_id', ['option_title' => 'qo.title'])
                    ->where('ri.response_id IN (?)', $responseIds)
                    ->order('ri.response_id ASC')
                    ->order('ri.item_id ASC')
            );

            foreach ($answerRows as $answerRow) {
                $responseId = (int)$answerRow['response_id'];
                $questionId = (int)$answerRow['question_id'];
                $optionTitle = trim((string)($answerRow['option_title'] ?? ''));
                $answerText = trim((string)($answerRow['answer_text'] ?? ''));
                $answerValue = $optionTitle !== '' ? $optionTitle : $answerText;

                if (!isset($answersByResponse[$responseId])) {
                    $answersByResponse[$responseId] = [];
                }
                if (!isset($answersByResponse[$responseId][$questionId])) {
                    $answersByResponse[$responseId][$questionId] = [
                        'question_id' => $questionId,
                        'question_title' => (string)($answerRow['question_title'] ?? ''),
                        'answers' => [],
                    ];
                }
                if ($answerValue !== '') {
                    $answersByResponse[$responseId][$questionId]['answers'][] = $answerValue;
                }
            }

            foreach ($answersByResponse as $responseId => $questionMap) {
                $normalizedQuestions = [];
                foreach ($questionMap as $questionEntry) {
                    $uniqueAnswers = array_values(array_unique(array_filter($questionEntry['answers'], static function (string $item): bool {
                        return trim($item) !== '';
                    })));
                    $questionEntry['answers'] = !empty($uniqueAnswers) ? $uniqueAnswers : ['-'];
                    $normalizedQuestions[] = $questionEntry;
                }
                $answersByResponse[$responseId] = $normalizedQuestions;
            }
        }

        $responseUsers = [];
        foreach ($responseRows as $responseRow) {
            $responseId = (int)$responseRow['response_id'];
            $answerRowsForResponse = $answersByResponse[$responseId] ?? [];
            $answeredQuestionIds = [];
            foreach ($answerRowsForResponse as $answerRow) {
                $answeredQuestionIds[(int)$answerRow['question_id']] = true;
            }

            $responseUsers[] = [
                'response_id' => $responseId,
                'customer_id' => (int)($responseRow['customer_id'] ?? 0),
                'guest_identifier' => (string)($responseRow['guest_identifier'] ?? ''),
                'customer_firstname' => (string)($responseRow['customer_firstname'] ?? ''),
                'customer_lastname' => (string)($responseRow['customer_lastname'] ?? ''),
                'customer_email' => (string)($responseRow['customer_email'] ?? ''),
                'device' => (string)($responseRow['device'] ?? ''),
                'browser' => (string)($responseRow['browser'] ?? ''),
                'ip_address' => (string)($responseRow['ip_address'] ?? ''),
                'created_at' => (string)($responseRow['created_at'] ?? ''),
                'answered_questions' => count($answeredQuestionIds),
                'answers' => $answerRowsForResponse,
            ];
        }

        $answeredByQuestionRows = $connection->fetchAll(
            $connection->select()
                ->from(['ri' => $responseItemTable], [
                    'question_id',
                    'answered_count' => new \Zend_Db_Expr('COUNT(DISTINCT ri.response_id)')
                ])
                ->join(['r' => $responseTable], 'r.response_id = ri.response_id', [])
                ->where('r.survey_id = ?', $surveyId)
                ->where('r.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('r.created_at <= ?', $toDate . ' 23:59:59')
                ->group('ri.question_id')
        );

        $answeredByQuestion = [];
        foreach ($answeredByQuestionRows as $answeredRow) {
            $answeredByQuestion[(int)$answeredRow['question_id']] = (int)$answeredRow['answered_count'];
        }

        $questionAnswerSplit = [];
        foreach ($surveyQuestions as $surveyQuestion) {
            $questionId = (int)($surveyQuestion['question_id'] ?? 0);
            if ($questionId <= 0) {
                continue;
            }

            $answeredCount = (int)($answeredByQuestion[$questionId] ?? 0);
            $notAnsweredCount = max(0, $responseTotal - $answeredCount);

            $questionAnswerSplit[] = [
                'question_id' => $questionId,
                'question_title' => (string)($surveyQuestion['question_title'] ?? ''),
                'answered' => $answeredCount,
                'not_answered' => $notAnsweredCount,
                'total_responses' => $responseTotal,
            ];
        }

        $result = [
            'survey' => [
                'survey_id' => (int)$survey['survey_id'],
                'name' => (string)$survey['name'],
                'status' => (string)$survey['status'],
            ],
            'totals' => [
                'impressions' => (int)$totals['impressions'],
                'opens' => (int)$totals['opens'],
                'responses' => (int)$totals['responses'],
            ],
            'answer_distribution' => $answerDistribution,
            'text_responses' => $textResponses,
            'location_performance' => $locationPerformance,
            'device_analytics' => $deviceAnalytics,
            'customer_type_analytics' => $customerTypeAnalytics,
            'date_trend' => $dateTrend,
            'question_answer_split' => $questionAnswerSplit,
            'response_users' => [
                'items' => $responseUsers,
                'total' => $responseTotal,
                'page' => $responsePage,
                'page_size' => $responsePageSize,
            ],
        ];

        $this->saveCache($cacheKey, $result);
        return $result;
    }

    /**
     * @param array<int|string, mixed> $parts
     */
    private function buildCacheKey(string $prefix, array $parts): string
    {
        return 'artm_cs_report_' . $prefix . '_' . sha1($this->json->serialize($parts));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadCache(string $cacheKey): ?array
    {
        if (!(bool)$this->scopeConfig->getValue(self::XML_PATH_CACHE_ENABLE)) {
            return null;
        }

        $cached = $this->cache->load($cacheKey);
        if (!$cached) {
            return null;
        }

        try {
            $decoded = $this->json->unserialize($cached);
            return is_array($decoded) ? $decoded : null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function saveCache(string $cacheKey, array $payload): void
    {
        if (!(bool)$this->scopeConfig->getValue(self::XML_PATH_CACHE_ENABLE)) {
            return;
        }

        $ttl = (int)$this->scopeConfig->getValue(self::XML_PATH_CACHE_TTL);
        if ($ttl <= 0) {
            $ttl = 900;
        }

        $this->cache->save($this->json->serialize($payload), $cacheKey, ['ARTM_CS_REPORT'], $ttl);
    }
}
