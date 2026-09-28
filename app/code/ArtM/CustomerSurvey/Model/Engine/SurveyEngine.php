<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine;

use ArtM\CustomerSurvey\Api\SurveyEngineInterface;
use ArtM\CustomerSurvey\Model\Engine\Context\ContextProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class SurveyEngine implements SurveyEngineInterface
{
    private const XML_PATH_STORE_IP = 'customersurvey/privacy/store_ip_address';
    private const FUTURE_DATE = '2099-12-31 23:59:59';

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

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ContextProviderInterface[]
     */
    private $contextProviders;

    /**
     * @param ContextProviderInterface[] $contextProviders
     */
    public function __construct(
        ResourceConnection $resource,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress,
        CustomerSession $customerSession,
        array $contextProviders = []
    ) {
        $this->resource = $resource;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->remoteAddress = $remoteAddress;
        $this->customerSession = $customerSession;
        $this->contextProviders = $contextProviders;
    }

    public function getEligibleSurveys(array $context = []): array
    {
        $context = $this->buildContext($context);
        $connection = $this->resource->getConnection();

        $locationIds = $this->resolveLocationIds($context);
        if (empty($locationIds)) {
            return [];
        }

        $surveys = $this->loadCandidateSurveys(
            $locationIds,
            (int)$context['store_id'],
            (int)$context['customer_group_id']
        );
        if (empty($surveys)) {
            return [];
        }

        $surveyIds = array_map(static function (array $row): int {
            return (int)$row['survey_id'];
        }, $surveys);

        $visitorKey = $this->resolveVisitorKey($context);
        $audienceState = $this->loadAudienceState($surveyIds, $visitorKey);
        $limitCounters = $this->loadLimitCounters($surveyIds);

        $eligible = [];
        foreach ($surveys as $survey) {
            $surveyId = (int)$survey['survey_id'];
            $state = $audienceState[$surveyId] ?? [];
            if (!$this->passesFrequency($survey, $state, $limitCounters[$surveyId] ?? [])) {
                continue;
            }

            // Conditions are intentionally bypassed for selector-driven strategy.
            $eligible[$surveyId] = $survey;
        }

        if (empty($eligible)) {
            return [];
        }

        $eligibleIds = array_keys($eligible);
        $questionsBySurvey = $this->loadQuestionsBySurvey($eligibleIds);
        $locationsBySurvey = $this->loadLocationsBySurvey($eligibleIds);

        $result = [];
        foreach ($eligible as $surveyId => $survey) {
            $result[] = [
                'survey_id' => (int)$survey['survey_id'],
                'name' => (string)$survey['name'],
                'description' => (string)($survey['description'] ?? ''),
                'status' => (string)$survey['status'],
                'priority' => (int)$survey['priority'],
                'sort_order' => (int)$survey['sort_order'],
                'display' => [
                    'display_type' => (string)$survey['display_type'],
                    'inline_position' => (string)($survey['inline_position'] ?? ''),
                    'inline_selector' => (string)($survey['inline_selector'] ?? ''),
                    'popup_triggers' => $this->explodeCsv((string)($survey['popup_triggers'] ?? '')),
                    'popup_delay_seconds' => $this->nullableInt($survey['popup_delay_seconds'] ?? null),
                    'popup_page_views' => $this->nullableInt($survey['popup_page_views'] ?? null),
                ],
                'frequency' => [
                    'if_not_answered' => (string)$survey['frequency_if_not_answered'],
                    'if_not_answered_days' => $this->nullableInt($survey['frequency_if_not_answered_days'] ?? null),
                    'if_answered' => (string)$survey['frequency_if_answered'],
                    'if_answered_days' => $this->nullableInt($survey['frequency_if_answered_days'] ?? null),
                    'max_impressions' => $this->nullableInt($survey['max_impressions'] ?? null),
                    'limit_daily' => $this->nullableInt($survey['limit_daily'] ?? null),
                    'limit_weekly' => $this->nullableInt($survey['limit_weekly'] ?? null),
                    'limit_monthly' => $this->nullableInt($survey['limit_monthly'] ?? null),
                    'max_response_count' => $this->nullableInt($survey['max_response_count'] ?? null),
                ],
                'questions' => $questionsBySurvey[$surveyId] ?? [],
                'locations' => $locationsBySurvey[$surveyId] ?? [],
            ];
        }

        return $result;
    }

    public function trackImpression(array $payload = []): bool
    {
        return $this->trackEvent('impression', $payload);
    }

    public function trackClosed(array $payload = []): bool
    {
        return $this->trackEvent('closed', $payload);
    }

    public function trackPopupOpen(array $payload = []): bool
    {
        return $this->trackEvent('popup_open', $payload);
    }

    public function trackAnswered(array $payload = []): bool
    {
        return $this->trackEvent('answered', $payload);
    }

    public function submitAnswers(array $payload = []): array
    {
        $surveyId = isset($payload['survey_id']) ? (int)$payload['survey_id'] : 0;
        if ($surveyId <= 0) {
            throw new LocalizedException(__('Survey id is required.'));
        }

        $responseId = isset($payload['response_id']) ? (int)$payload['response_id'] : 0;
        $isCompleted = !empty($payload['is_completed']);

        $customerId = $this->resolveCustomerId(isset($payload['customer_id']) ? (int)$payload['customer_id'] : 0);
        $visitorKey = $this->resolveVisitorKey([
            'visitor_key' => (string)($payload['visitor_key'] ?? ''),
            'customer_id' => $customerId,
        ]);
        if ($visitorKey === '') {
            throw new LocalizedException(__('visitor_key is required for guest tracking.'));
        }

        $answers = isset($payload['answers']) && is_array($payload['answers']) ? $payload['answers'] : [];
        if (empty($answers)) {
            throw new LocalizedException(__('Answers payload is required.'));
        }

        $connection = $this->resource->getConnection();
        $responseTable = $this->resource->getTableName('artm_customersurvey_response');
        $responseItemTable = $this->resource->getTableName('artm_customersurvey_response_item');

        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');
        $browser = mb_substr(trim((string)($payload['browser'] ?? '')), 0, 128);
        $device = mb_substr(trim((string)($payload['device'] ?? '')), 0, 128);
        $pageUrl = (string)($payload['page_url'] ?? '');
        $locationId = isset($payload['location_id']) ? (int)$payload['location_id'] : null;
        if ($locationId !== null && $locationId <= 0) {
            $locationId = null;
        }

        $connection->beginTransaction();
        try {
            if ($responseId > 0) {
                $existingResponse = $connection->fetchRow(
                    $connection->select()
                        ->from($responseTable, ['response_id'])
                        ->where('response_id = ?', $responseId)
                        ->where('survey_id = ?', $surveyId)
                        ->limit(1)
                );
                if (!$existingResponse) {
                    $responseId = 0;
                }
            }

            if ($responseId <= 0) {
                $connection->insert($responseTable, [
                    'survey_id' => $surveyId,
                    'customer_id' => $customerId ?: null,
                    'guest_identifier' => $customerId > 0 ? null : $visitorKey,
                    'location_id' => $locationId,
                    'page_url' => $pageUrl,
                    'device' => $device,
                    'browser' => $browser,
                    'ip_address' => $this->resolveIpAddress(),
                    'created_at' => $now,
                ]);

                $responseId = (int)$connection->lastInsertId($responseTable);
            } else {
                $responseUpdate = [
                    'page_url' => $pageUrl,
                    'device' => $device,
                    'browser' => $browser,
                ];

                if ($locationId !== null) {
                    $responseUpdate['location_id'] = $locationId;
                }

                if ($customerId > 0) {
                    $responseUpdate['customer_id'] = $customerId;
                    $responseUpdate['guest_identifier'] = null;
                }

                $connection->update(
                    $responseTable,
                    $responseUpdate,
                    ['response_id = ?' => $responseId]
                );
            }

            foreach ($answers as $answer) {
                if (!is_array($answer)) {
                    continue;
                }

                $questionId = isset($answer['question_id']) ? (int)$answer['question_id'] : 0;
                if ($questionId <= 0) {
                    continue;
                }

                $answerText = trim((string)($answer['answer_text'] ?? ''));
                $optionIds = [];
                if (isset($answer['option_ids']) && is_array($answer['option_ids'])) {
                    foreach ($answer['option_ids'] as $optId) {
                        $optId = (int)$optId;
                        if ($optId > 0) {
                            $optionIds[] = $optId;
                        }
                    }
                } elseif (isset($answer['option_id'])) {
                    $optId = (int)$answer['option_id'];
                    if ($optId > 0) {
                        $optionIds[] = $optId;
                    }
                }

                // Keep only the latest answer for each question in this response.
                $connection->delete(
                    $responseItemTable,
                    [
                        'response_id = ?' => $responseId,
                        'question_id = ?' => $questionId,
                    ]
                );

                if (empty($optionIds) && $answerText === '') {
                    continue;
                }

                if (empty($optionIds)) {
                    $connection->insert($responseItemTable, [
                        'response_id' => $responseId,
                        'question_id' => $questionId,
                        'option_id' => null,
                        'answer_text' => $answerText,
                        'created_at' => $now,
                    ]);
                    continue;
                }

                foreach ($optionIds as $optionId) {
                    $connection->insert($responseItemTable, [
                        'response_id' => $responseId,
                        'question_id' => $questionId,
                        'option_id' => $optionId,
                        'answer_text' => $answerText !== '' ? $answerText : null,
                        'created_at' => $now,
                    ]);
                }
            }

            if ($isCompleted) {
                $this->trackEventInternal('answered', [
                    'survey_id' => $surveyId,
                    'visitor_key' => $visitorKey,
                    'customer_id' => $customerId,
                ], $connection, $now);
            }

            $connection->commit();
            return [
                'success' => true,
                'response_id' => $responseId,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildContext(array $context): array
    {
        $normalized = [
            'page_type' => (string)($context['page_type'] ?? ''),
            'product_id' => isset($context['product_id']) ? (int)$context['product_id'] : 0,
            'category_id' => isset($context['category_id']) ? (int)$context['category_id'] : 0,
            'url' => (string)($context['url'] ?? ''),
            'store_id' => isset($context['store_id']) ? (int)$context['store_id'] : 0,
            'visitor_key' => (string)($context['visitor_key'] ?? ''),
            'customer_id' => isset($context['customer_id']) ? (int)$context['customer_id'] : 0,
            'customer_group_id' => isset($context['customer_group_id']) ? (int)$context['customer_group_id'] : 0,
            'is_logged_in' => !empty($context['is_logged_in']),
        ];

        foreach ($this->contextProviders as $provider) {
            if ($provider instanceof ContextProviderInterface) {
                $normalized = array_merge($normalized, $provider->provide($normalized));
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     * @return int[]
     */
    private function resolveLocationIds(array $context): array
    {
        $connection = $this->resource->getConnection();
        $locationTable = $this->resource->getTableName('artm_customersurvey_location');

        $pageType = (string)$context['page_type'];
        if ($pageType === '') {
            return [];
        }

        $select = $connection->select()
            ->from($locationTable, ['location_id', 'page_type', 'url_pattern'])
            ->where('status = ?', 1)
            ->where('page_type IN (?)', [$pageType, 'custom_url']);

        $rows = $connection->fetchAll($select);
        if (!$rows) {
            return [];
        }

        $url = (string)$context['url'];
        $locationIds = [];
        foreach ($rows as $row) {
            $rowPageType = (string)$row['page_type'];
            if ($rowPageType === 'custom_url') {
                if ($url === '' || !$this->matchUrlPattern((string)$row['url_pattern'], $url)) {
                    continue;
                }
            }
            $locationIds[] = (int)$row['location_id'];
        }

        return array_values(array_unique(array_filter($locationIds)));
    }

    private function matchUrlPattern(string $pattern, string $url): bool
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return false;
        }

        return fnmatch($pattern, $url);
    }

    /**
     * @param int[] $locationIds
     * @return array<int, array<string, mixed>>
     */
    private function loadCandidateSurveys(array $locationIds, int $storeId, int $customerGroupId): array
    {
        $connection = $this->resource->getConnection();
        $surveyTable = $this->resource->getTableName('artm_customersurvey_survey');
        $surveyLocationTable = $this->resource->getTableName('artm_customersurvey_survey_location');

        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');

        $select = $connection->select()
            ->from(['s' => $surveyTable])
            ->join(['sl' => $surveyLocationTable], 's.survey_id = sl.survey_id', [])
            ->where('sl.location_id IN (?)', $locationIds)
            ->where('s.status = ?', 'active')
            ->where('(s.start_date IS NULL OR s.start_date <= ?)', $now)
            ->where('(s.end_date IS NULL OR s.end_date >= ?)', $now)
            ->order('s.priority DESC')
            ->order('s.sort_order ASC')
            ->group('s.survey_id');

        if ($storeId > 0) {
            $select->where('(s.store_ids IS NULL OR s.store_ids = "" OR FIND_IN_SET(0, s.store_ids) OR FIND_IN_SET(?, s.store_ids))', $storeId);
        }

        $select->where(
            '(s.customer_group_ids IS NULL OR s.customer_group_ids = "" OR FIND_IN_SET(?, s.customer_group_ids))',
            $customerGroupId
        );

        return $connection->fetchAll($select);
    }

    /**
     * @param int[] $surveyIds
     * @return array<int, array<string, mixed>>
     */
    private function loadAudienceState(array $surveyIds, string $visitorKey): array
    {
        if (empty($surveyIds) || $visitorKey === '') {
            return [];
        }

        $connection = $this->resource->getConnection();
        $stateTable = $this->resource->getTableName('artm_customersurvey_audience_state');

        $select = $connection->select()
            ->from($stateTable)
            ->where('survey_id IN (?)', $surveyIds)
            ->where('visitor_key = ?', $visitorKey);

        $rows = $connection->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['survey_id']] = $row;
        }

        return $result;
    }

    /**
     * @param int[] $surveyIds
     * @return array<int, array<string, int>>
     */
    private function loadLimitCounters(array $surveyIds): array
    {
        $result = [];
        foreach ($surveyIds as $surveyId) {
            $result[$surveyId] = [
                'impressions_daily' => 0,
                'impressions_weekly' => 0,
                'impressions_monthly' => 0,
                'responses_total' => 0,
            ];
        }

        if (empty($surveyIds)) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $eventTable = $this->resource->getTableName('artm_customersurvey_event');

        $nowTs = strtotime($this->dateTime->gmtDate('Y-m-d H:i:s')) ?: time();
        $dayStart = gmdate('Y-m-d 00:00:00', $nowTs);
        $weekStart = gmdate('Y-m-d 00:00:00', strtotime('monday this week', $nowTs));
        $monthStart = gmdate('Y-m-01 00:00:00', $nowTs);

        $daily = $connection->fetchPairs(
            $connection->select()
                ->from($eventTable, ['survey_id', new \Zend_Db_Expr('COUNT(*)')])
                ->where('survey_id IN (?)', $surveyIds)
                ->where('event_type = ?', 'impression')
                ->where('created_at >= ?', $dayStart)
                ->group('survey_id')
        );

        $weekly = $connection->fetchPairs(
            $connection->select()
                ->from($eventTable, ['survey_id', new \Zend_Db_Expr('COUNT(*)')])
                ->where('survey_id IN (?)', $surveyIds)
                ->where('event_type = ?', 'impression')
                ->where('created_at >= ?', $weekStart)
                ->group('survey_id')
        );

        $monthly = $connection->fetchPairs(
            $connection->select()
                ->from($eventTable, ['survey_id', new \Zend_Db_Expr('COUNT(*)')])
                ->where('survey_id IN (?)', $surveyIds)
                ->where('event_type = ?', 'impression')
                ->where('created_at >= ?', $monthStart)
                ->group('survey_id')
        );

        $responses = $connection->fetchPairs(
            $connection->select()
                ->from($eventTable, ['survey_id', new \Zend_Db_Expr('COUNT(*)')])
                ->where('survey_id IN (?)', $surveyIds)
                ->where('event_type = ?', 'answered')
                ->group('survey_id')
        );

        foreach ($result as $surveyId => $row) {
            $result[$surveyId]['impressions_daily'] = (int)($daily[$surveyId] ?? 0);
            $result[$surveyId]['impressions_weekly'] = (int)($weekly[$surveyId] ?? 0);
            $result[$surveyId]['impressions_monthly'] = (int)($monthly[$surveyId] ?? 0);
            $result[$surveyId]['responses_total'] = (int)($responses[$surveyId] ?? 0);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $survey
     * @param array<string, mixed> $state
     * @param array<string, int> $counters
     */
    private function passesFrequency(array $survey, array $state, array $counters): bool
    {
        $nextAllowedAt = isset($state['next_allowed_display_at']) ? (string)$state['next_allowed_display_at'] : '';
        if ($nextAllowedAt !== '' && $nextAllowedAt !== '0000-00-00 00:00:00') {
            $nextTs = strtotime($nextAllowedAt);
            if ($nextTs && time() < $nextTs) {
                return false;
            }
        }

        $impressionCount = isset($state['impression_count']) ? (int)$state['impression_count'] : 0;
        $maxImpressions = $this->nullableInt($survey['max_impressions'] ?? null);
        if ($maxImpressions !== null && $impressionCount >= $maxImpressions) {
            return false;
        }

        $maxResponses = $this->nullableInt($survey['max_response_count'] ?? null);
        if ($maxResponses !== null && (int)($counters['responses_total'] ?? 0) >= $maxResponses) {
            return false;
        }

        $limitDaily = $this->nullableInt($survey['limit_daily'] ?? null);
        if ($limitDaily !== null && (int)($counters['impressions_daily'] ?? 0) >= $limitDaily) {
            return false;
        }

        $limitWeekly = $this->nullableInt($survey['limit_weekly'] ?? null);
        if ($limitWeekly !== null && (int)($counters['impressions_weekly'] ?? 0) >= $limitWeekly) {
            return false;
        }

        $limitMonthly = $this->nullableInt($survey['limit_monthly'] ?? null);
        if ($limitMonthly !== null && (int)($counters['impressions_monthly'] ?? 0) >= $limitMonthly) {
            return false;
        }

        $answeredAt = isset($state['answered_at']) ? (string)$state['answered_at'] : '';
        if ($answeredAt !== '' && $answeredAt !== '0000-00-00 00:00:00') {
            $mode = (string)($survey['frequency_if_answered'] ?? 'never');
            if ($mode === 'never') {
                return false;
            }

            if ($mode === 'after_days') {
                $days = max(0, (int)($survey['frequency_if_answered_days'] ?? 0));
                if (!$this->hasDaysElapsed($answeredAt, $days)) {
                    return false;
                }
            }
        }

        $closedAt = isset($state['closed_at']) ? (string)$state['closed_at'] : '';
        if ($closedAt !== '' && $closedAt !== '0000-00-00 00:00:00') {
            $mode = (string)($survey['frequency_if_not_answered'] ?? 'every_visit');
            if ($mode === 'once_never') {
                return false;
            }

            if ($mode === 'after_days') {
                $days = max(0, (int)($survey['frequency_if_not_answered_days'] ?? 0));
                if (!$this->hasDaysElapsed($closedAt, $days)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function hasDaysElapsed(string $dateTime, int $days): bool
    {
        if ($days <= 0) {
            return true;
        }

        $ts = strtotime($dateTime);
        if (!$ts) {
            return true;
        }

        $nextAllowed = strtotime('+' . $days . ' days', $ts);
        return $nextAllowed !== false && time() >= $nextAllowed;
    }

    /**
     * @param int[] $surveyIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadQuestionsBySurvey(array $surveyIds): array
    {
        $result = [];
        if (empty($surveyIds)) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $mapTable = $this->resource->getTableName('artm_customersurvey_survey_question');
        $questionTable = $this->resource->getTableName('artm_customersurvey_question');
        $optionTable = $this->resource->getTableName('artm_customersurvey_question_option');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['sq' => $mapTable], ['survey_id', 'question_id', 'question_order'])
                ->join(['q' => $questionTable], 'q.question_id = sq.question_id', [
                    'title', 'description', 'type', 'is_required', 'min_selection', 'max_selection', 'sort_order'
                ])
                ->where('sq.survey_id IN (?)', $surveyIds)
                ->where('q.status = ?', 1)
                ->order('sq.question_order ASC')
        );

        if (empty($rows)) {
            return $result;
        }

        $questionIds = [];
        foreach ($rows as $row) {
            $questionIds[] = (int)$row['question_id'];
        }
        $questionIds = array_values(array_unique($questionIds));

        $optionsByQuestion = [];
        if (!empty($questionIds)) {
            $optionRows = $connection->fetchAll(
                $connection->select()
                    ->from($optionTable, ['option_id', 'question_id', 'title', 'value', 'image', 'sort_order'])
                    ->where('question_id IN (?)', $questionIds)
                    ->order('sort_order ASC')
                    ->order('option_id ASC')
            );

            foreach ($optionRows as $optionRow) {
                $qId = (int)$optionRow['question_id'];
                $optionsByQuestion[$qId][] = [
                    'option_id' => (int)$optionRow['option_id'],
                    'title' => (string)$optionRow['title'],
                    'value' => (string)$optionRow['value'],
                    'image' => (string)($optionRow['image'] ?? ''),
                    'sort_order' => (int)$optionRow['sort_order'],
                ];
            }
        }

        foreach ($rows as $row) {
            $surveyId = (int)$row['survey_id'];
            $questionId = (int)$row['question_id'];

            $result[$surveyId][] = [
                'question_id' => $questionId,
                'question_order' => (int)$row['question_order'],
                'title' => (string)$row['title'],
                'description' => (string)($row['description'] ?? ''),
                'type' => (string)$row['type'],
                'is_required' => (int)$row['is_required'],
                'min_selection' => $this->nullableInt($row['min_selection'] ?? null),
                'max_selection' => $this->nullableInt($row['max_selection'] ?? null),
                'sort_order' => (int)$row['sort_order'],
                'options' => $optionsByQuestion[$questionId] ?? [],
            ];
        }

        return $result;
    }

    /**
     * @param int[] $surveyIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadLocationsBySurvey(array $surveyIds): array
    {
        $result = [];
        if (empty($surveyIds)) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $mapTable = $this->resource->getTableName('artm_customersurvey_survey_location');
        $locationTable = $this->resource->getTableName('artm_customersurvey_location');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['sl' => $mapTable], ['survey_id', 'location_id'])
                ->join(['l' => $locationTable], 'l.location_id = sl.location_id', [
                    'name', 'description', 'page_type', 'url_pattern', 'css_selector', 'position', 'screenshot'
                ])
                ->where('sl.survey_id IN (?)', $surveyIds)
                ->where('l.status = ?', 1)
                ->order('l.location_id ASC')
        );

        foreach ($rows as $row) {
            $surveyId = (int)$row['survey_id'];
            $result[$surveyId][] = [
                'location_id' => (int)$row['location_id'],
                'name' => (string)$row['name'],
                'description' => (string)($row['description'] ?? ''),
                'page_type' => (string)$row['page_type'],
                'url_pattern' => (string)($row['url_pattern'] ?? ''),
                'css_selector' => (string)$row['css_selector'],
                'position' => (string)$row['position'],
                'screenshot' => (string)($row['screenshot'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function trackEvent(string $eventType, array $payload): bool
    {
        $connection = $this->resource->getConnection();
        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');
        return $this->trackEventInternal($eventType, $payload, $connection, $now);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function trackEventInternal(
        string $eventType,
        array $payload,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $now
    ): bool
    {
        $surveyId = isset($payload['survey_id']) ? (int)$payload['survey_id'] : 0;
        if ($surveyId <= 0) {
            throw new LocalizedException(__('Survey id is required.'));
        }

        $customerId = $this->resolveCustomerId(isset($payload['customer_id']) ? (int)$payload['customer_id'] : 0);
        $visitorKey = trim((string)($payload['visitor_key'] ?? ''));
        if ($visitorKey === '') {
            $visitorKey = $customerId > 0 ? 'customer_' . $customerId : '';
        }
        if ($visitorKey === '') {
            throw new LocalizedException(__('visitor_key is required for guest tracking.'));
        }

        $eventTable = $this->resource->getTableName('artm_customersurvey_event');
        $stateTable = $this->resource->getTableName('artm_customersurvey_audience_state');

        $connection->insert($eventTable, [
            'survey_id' => $surveyId,
            'visitor_key' => $visitorKey,
            'customer_id' => $customerId ?: null,
            'event_type' => $eventType,
            'created_at' => $now,
        ]);

        $stateSelect = $connection->select()
            ->from($stateTable, ['state_id', 'impression_count'])
            ->where('survey_id = ?', $surveyId)
            ->where('visitor_key = ?', $visitorKey)
            ->limit(1);

        $state = $connection->fetchRow($stateSelect);
        if (!$state) {
            $survey = $this->loadSurveyFrequency($surveyId);
            $nextAllowed = $this->calculateNextAllowedDisplayAt($eventType, $survey, $now);
            $data = [
                'survey_id' => $surveyId,
                'visitor_key' => $visitorKey,
                'customer_id' => $customerId ?: null,
                'impression_count' => $eventType === 'impression' ? 1 : 0,
                'popup_open_count' => $eventType === 'popup_open' ? 1 : 0,
                'close_count' => $eventType === 'closed' ? 1 : 0,
                'answer_count' => $eventType === 'answered' ? 1 : 0,
                'last_impression_at' => $eventType === 'impression' ? $now : null,
                'next_allowed_display_at' => $nextAllowed,
                'closed_at' => $eventType === 'closed' ? $now : null,
                'answered_at' => $eventType === 'answered' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $connection->insert($stateTable, $data);
            return true;
        }

        $update = ['updated_at' => $now];
        if ($eventType === 'popup_open') {
            $update['popup_open_count'] = (int)($state['popup_open_count'] ?? 0) + 1;
        }
        if ($eventType === 'impression') {
            $update['impression_count'] = (int)$state['impression_count'] + 1;
            $update['last_impression_at'] = $now;
        } elseif ($eventType === 'closed') {
            $update['close_count'] = (int)($state['close_count'] ?? 0) + 1;
            $update['closed_at'] = $now;
        } elseif ($eventType === 'answered') {
            $update['answer_count'] = (int)($state['answer_count'] ?? 0) + 1;
            $update['answered_at'] = $now;
        }

        $survey = $this->loadSurveyFrequency($surveyId);
        $nextAllowed = $this->calculateNextAllowedDisplayAt($eventType, $survey, $now);
        if ($nextAllowed !== null || $eventType === 'impression') {
            $update['next_allowed_display_at'] = $nextAllowed;
        }

        $connection->update(
            $stateTable,
            $update,
            ['state_id = ?' => (int)$state['state_id']]
        );

        return true;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveVisitorKey(array $context): string
    {
        $visitorKey = trim((string)($context['visitor_key'] ?? ''));
        if ($visitorKey !== '') {
            return $visitorKey;
        }

        $customerId = (int)($context['customer_id'] ?? 0);
        if ($customerId > 0) {
            return 'customer_' . $customerId;
        }

        return '';
    }

    /**
     * @return string[]
     */
    private function explodeCsv(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static function (string $item): bool {
            return $item !== '';
        }));
    }

    /**
     * @param mixed $value
     */
    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int)$value;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSurveyFrequency(int $surveyId): array
    {
        $connection = $this->resource->getConnection();
        $surveyTable = $this->resource->getTableName('artm_customersurvey_survey');

        $row = $connection->fetchRow(
            $connection->select()
                ->from($surveyTable, [
                    'survey_id',
                    'frequency_if_not_answered',
                    'frequency_if_not_answered_days',
                    'frequency_if_answered',
                    'frequency_if_answered_days'
                ])
                ->where('survey_id = ?', $surveyId)
                ->limit(1)
        );

        return is_array($row) ? $row : [];
    }

    /**
     * @param array<string, mixed> $survey
     */
    private function calculateNextAllowedDisplayAt(string $eventType, array $survey, string $now): ?string
    {
        if ($eventType === 'impression' || $eventType === 'popup_open') {
            return null;
        }

        $baseTs = strtotime($now) ?: time();

        if ($eventType === 'closed') {
            $mode = (string)($survey['frequency_if_not_answered'] ?? 'every_visit');
            if ($mode === 'every_visit') {
                return null;
            }
            if ($mode === 'once_never') {
                return self::FUTURE_DATE;
            }
            if ($mode === 'after_days') {
                $days = max(0, (int)($survey['frequency_if_not_answered_days'] ?? 0));
                return gmdate('Y-m-d H:i:s', strtotime('+' . $days . ' days', $baseTs) ?: $baseTs);
            }
        }

        if ($eventType === 'answered') {
            $mode = (string)($survey['frequency_if_answered'] ?? 'never');
            if ($mode === 'never') {
                return self::FUTURE_DATE;
            }
            if ($mode === 'after_days') {
                $days = max(0, (int)($survey['frequency_if_answered_days'] ?? 0));
                return gmdate('Y-m-d H:i:s', strtotime('+' . $days . ' days', $baseTs) ?: $baseTs);
            }
        }

        return null;
    }

    private function resolveIpAddress(): ?string
    {
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_STORE_IP);
        $storeIp = $configValue === null || $configValue === '' ? true : (bool)$configValue;
        if (!$storeIp) {
            return null;
        }

        $ip = (string)$this->remoteAddress->getRemoteAddress();
        if ($ip === '') {
            return null;
        }

        return mb_substr($ip, 0, 45);
    }

    private function resolveCustomerId(int $payloadCustomerId): int
    {
        if ($payloadCustomerId > 0) {
            return $payloadCustomerId;
        }

        try {
            $sessionCustomerId = (int)$this->customerSession->getCustomerId();
            return $sessionCustomerId > 0 ? $sessionCustomerId : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
