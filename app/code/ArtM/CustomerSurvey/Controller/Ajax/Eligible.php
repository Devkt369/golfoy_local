<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Controller\Ajax;

use ArtM\CustomerSurvey\Api\SurveyEngineInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Eligible extends AbstractJsonAction
{
    /**
     * @var SurveyEngineInterface
     */
    private $surveyEngine;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        SurveyEngineInterface $surveyEngine,
        ResourceConnection $resource,
        DateTime $dateTime
    ) {
        parent::__construct($context, $jsonFactory);
        $this->surveyEngine = $surveyEngine;
        $this->resource = $resource;
        $this->dateTime = $dateTime;
    }

    public function execute()
    {
        try {
            $payload = $this->getPayload();
            $context = isset($payload['context']) && is_array($payload['context'])
                ? $payload['context']
                : [];
            $items = $this->surveyEngine->getEligibleSurveys($context);

            return $this->jsonResponse([
                'items' => $items,
                'trace' => $this->buildTrace($context, count($items)),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function buildTrace(array $context, int $eligibleCount): array
    {
        $conn = $this->resource->getConnection();
        $locationTable = $this->resource->getTableName('artm_customersurvey_location');
        $surveyTable = $this->resource->getTableName('artm_customersurvey_survey');
        $surveyLocationTable = $this->resource->getTableName('artm_customersurvey_survey_location');

        $pageType = (string)($context['page_type'] ?? '');
        $url = (string)($context['url'] ?? '');
        $storeId = isset($context['store_id']) ? (int)$context['store_id'] : 0;
        $now = $this->dateTime->gmtDate('Y-m-d H:i:s');

        $pageTypeCandidates = [];
        $matchedLocations = [];
        if ($pageType !== '') {
            $pageTypeCandidates = $conn->fetchAll(
                $conn->select()
                    ->from($locationTable, ['location_id', 'page_type', 'url_pattern'])
                    ->where('status = ?', 1)
                    ->where('page_type IN (?)', [$pageType, 'custom_url'])
            );

            foreach ($pageTypeCandidates as $row) {
                $rowPageType = (string)($row['page_type'] ?? '');
                $pattern = trim((string)($row['url_pattern'] ?? ''));
                if ($rowPageType === 'custom_url') {
                    if ($url === '' || $pattern === '' || !fnmatch($pattern, $url)) {
                        continue;
                    }
                }
                $matchedLocations[] = (int)$row['location_id'];
            }
        }

        $matchedLocations = array_values(array_unique(array_filter($matchedLocations)));

        $mappedSurveyIds = [];
        if (!empty($matchedLocations)) {
            $mappedSurveyRows = $conn->fetchAll(
                $conn->select()
                    ->from($surveyLocationTable, ['survey_id'])
                    ->where('location_id IN (?)', $matchedLocations)
                    ->group('survey_id')
            );
            $mappedSurveyIds = array_values(array_map(static function (array $row): int {
                return (int)($row['survey_id'] ?? 0);
            }, $mappedSurveyRows));
            $mappedSurveyIds = array_values(array_filter($mappedSurveyIds));
        }

        $filterStats = [
            'mapped_total' => count($mappedSurveyIds),
            'after_status' => 0,
            'after_date' => 0,
            'after_store' => 0,
        ];

        $rejectedByStage = [
            'status' => [],
            'date' => [],
            'store' => [],
        ];

        $dateWindowBySurvey = [];

        if (!empty($mappedSurveyIds)) {
            $surveyRows = $conn->fetchAll(
                $conn->select()
                    ->from($surveyTable, ['survey_id', 'status', 'start_date', 'end_date', 'store_ids'])
                    ->where('survey_id IN (?)', $mappedSurveyIds)
            );

            $statusPassed = [];
            foreach ($surveyRows as $row) {
                $surveyId = (int)($row['survey_id'] ?? 0);
                $status = (string)($row['status'] ?? '');
                if ($status === 'active') {
                    $statusPassed[] = $surveyId;
                } else {
                    $rejectedByStage['status'][] = $surveyId;
                }
            }
            $statusPassed = array_values(array_unique(array_filter($statusPassed)));
            $filterStats['after_status'] = count($statusPassed);

            $datePassed = [];
            foreach ($surveyRows as $row) {
                $surveyId = (int)($row['survey_id'] ?? 0);
                if (!in_array($surveyId, $statusPassed, true)) {
                    continue;
                }

                $startDate = (string)($row['start_date'] ?? '');
                $endDate = (string)($row['end_date'] ?? '');
                $startOk = ($startDate === '' || $startDate <= $now);
                $endOk = ($endDate === '' || $endDate >= $now);

                 $dateWindowBySurvey[$surveyId] = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'now_gmt' => $now,
                    'start_ok' => $startOk,
                    'end_ok' => $endOk,
                ];

                if ($startOk && $endOk) {
                    $datePassed[] = $surveyId;
                } else {
                    $rejectedByStage['date'][] = $surveyId;
                }
            }
            $datePassed = array_values(array_unique(array_filter($datePassed)));
            $filterStats['after_date'] = count($datePassed);

            $storePassed = [];
            foreach ($surveyRows as $row) {
                $surveyId = (int)($row['survey_id'] ?? 0);
                if (!in_array($surveyId, $datePassed, true)) {
                    continue;
                }

                $storeIds = (string)($row['store_ids'] ?? '');
                if ($storeId <= 0 || $storeIds === '' || in_array(trim($storeIds), ['0'], true)) {
                    $storePassed[] = $surveyId;
                    continue;
                }

                $parts = array_values(array_filter(array_map('trim', explode(',', $storeIds)), static function (string $item): bool {
                    return $item !== '';
                }));

                if (in_array('0', $parts, true) || in_array((string)$storeId, $parts, true)) {
                    $storePassed[] = $surveyId;
                } else {
                    $rejectedByStage['store'][] = $surveyId;
                }
            }
            $storePassed = array_values(array_unique(array_filter($storePassed)));
            $filterStats['after_store'] = count($storePassed);
        }

        $candidateSurveyRows = [];
        if (!empty($matchedLocations)) {
            $select = $conn->select()
                ->from(['s' => $surveyTable], ['survey_id'])
                ->join(['sl' => $surveyLocationTable], 's.survey_id = sl.survey_id', [])
                ->where('sl.location_id IN (?)', $matchedLocations)
                ->where('s.status = ?', 'active')
                ->where('(s.start_date IS NULL OR s.start_date <= ?)', $now)
                ->where('(s.end_date IS NULL OR s.end_date >= ?)', $now)
                ->group('s.survey_id');

            if ($storeId > 0) {
                $select->where('(s.store_ids IS NULL OR s.store_ids = "" OR FIND_IN_SET(0, s.store_ids) OR FIND_IN_SET(?, s.store_ids))', $storeId);
            }

            $candidateSurveyRows = $conn->fetchAll($select);
        }

        $candidateSurveyIds = array_values(array_map(static function (array $row): int {
            return (int)($row['survey_id'] ?? 0);
        }, $candidateSurveyRows));

        $hint = 'Eligibility resolved.';
        if ($pageType === '') {
            $hint = 'Missing page_type in context.';
        } elseif (empty($pageTypeCandidates)) {
            $hint = 'No active locations found for this page_type.';
        } elseif (empty($matchedLocations)) {
            $hint = 'Locations found but none matched URL pattern for custom_url type.';
        } elseif (empty($mappedSurveyIds)) {
            $hint = 'Location matched, but no surveys are mapped to this location.';
        } elseif (empty($candidateSurveyIds)) {
            $hint = 'Locations matched, but no active surveys mapped for current store/date.';
            if (!empty($rejectedByStage['date'])) {
                $hint = 'Survey is mapped and active, but current GMT time is outside survey start/end date window.';
            }
        } elseif ($eligibleCount === 0) {
            $hint = 'Candidate surveys exist, but frequency/audience limits filtered them out.';
        }

        return [
            'page_type' => $pageType,
            'url' => $url,
            'store_id' => $storeId,
            'now_gmt' => $now,
            'active_location_candidates' => count($pageTypeCandidates),
            'matched_location_ids' => $matchedLocations,
            'mapped_survey_ids' => $mappedSurveyIds,
            'candidate_survey_ids' => $candidateSurveyIds,
            'candidate_survey_count' => count($candidateSurveyIds),
            'filter_stats' => $filterStats,
            'rejected_survey_ids' => $rejectedByStage,
            'date_window_by_survey' => $dateWindowBySurvey,
            'eligible_count' => $eligibleCount,
            'hint' => $hint,
        ];
    }
}
