<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Survey;

use ArtM\CustomerSurvey\Api\SurveyRepositoryInterface;
use ArtM\CustomerSurvey\Model\ResourceModel\SurveyLocation as SurveyLocationResource;
use ArtM\CustomerSurvey\Model\ResourceModel\SurveyQuestion as SurveyQuestionResource;
use ArtM\CustomerSurvey\Model\Survey;
use ArtM\CustomerSurvey\Model\SurveyFactory;
use ArtM\CustomerSurvey\Model\SurveyLocationFactory;
use ArtM\CustomerSurvey\Model\SurveyQuestionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::survey_save';

    /** @var SurveyFactory */
    private $surveyFactory;

    /** @var SurveyRepositoryInterface */
    private $surveyRepository;

    /** @var SurveyQuestionFactory */
    private $surveyQuestionFactory;

    /** @var SurveyQuestionResource */
    private $surveyQuestionResource;

    /** @var SurveyLocationFactory */
    private $surveyLocationFactory;

    /** @var SurveyLocationResource */
    private $surveyLocationResource;

    /** @var DataPersistorInterface */
    private $dataPersistor;

    /** @var ResourceConnection */
    private $resourceConnection;

    public function __construct(
        Action\Context $context,
        SurveyFactory $surveyFactory,
        SurveyRepositoryInterface $surveyRepository,
        SurveyQuestionFactory $surveyQuestionFactory,
        SurveyQuestionResource $surveyQuestionResource,
        SurveyLocationFactory $surveyLocationFactory,
        SurveyLocationResource $surveyLocationResource,
        DataPersistorInterface $dataPersistor,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->surveyFactory = $surveyFactory;
        $this->surveyRepository = $surveyRepository;
        $this->surveyQuestionFactory = $surveyQuestionFactory;
        $this->surveyQuestionResource = $surveyQuestionResource;
        $this->surveyLocationFactory = $surveyLocationFactory;
        $this->surveyLocationResource = $surveyLocationResource;
        $this->dataPersistor = $dataPersistor;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/index');
        }

        $surveyId = isset($data['survey_id']) ? (int)$data['survey_id'] : 0;

        try {
            $survey = $surveyId
                ? $this->surveyRepository->getById($surveyId)
                : $this->surveyFactory->create();

            $this->validateData($data);
            $this->validateSchemaReadiness($data);

            $survey->setName((string)$data['name']);
            $survey->setDescription((string)($data['description'] ?? ''));
            $survey->setStatus((string)$data['status']);
            $survey->setPriority((int)($data['priority'] ?? 0));
            $survey->setSortOrder((int)($data['sort_order'] ?? 0));
            $survey->setStartDate($this->normalizeDate($data['start_date'] ?? null));
            $survey->setEndDate($this->normalizeDate($data['end_date'] ?? null));
            $survey->setData('display_type', (string)($data['display_type'] ?? Survey::DISPLAY_TYPE_INLINE));
            $survey->setData('popup_triggers', implode(',', $this->normalizeIds($data['popup_triggers'] ?? [])));
            $survey->setData('popup_delay_seconds', $this->normalizeNullableInt($data['popup_delay_seconds'] ?? null));
            $survey->setData('popup_page_views', $this->normalizeNullableInt($data['popup_page_views'] ?? null));
            $survey->setData('store_ids', implode(',', $this->normalizeIds($data['store_ids'] ?? [])));
            $survey->setData('customer_group_ids', implode(',', $this->normalizeIds($data['customer_group_ids'] ?? [])));
            $survey->setData('frequency_if_not_answered', (string)($data['frequency_if_not_answered'] ?? Survey::FREQ_NOT_ANSWERED_EVERY_VISIT));
            $survey->setData('frequency_if_not_answered_days', $this->normalizeNullableInt($data['frequency_if_not_answered_days'] ?? null));
            $survey->setData('frequency_if_answered', (string)($data['frequency_if_answered'] ?? Survey::FREQ_ANSWERED_NEVER));
            $survey->setData('frequency_if_answered_days', $this->normalizeNullableInt($data['frequency_if_answered_days'] ?? null));
            $survey->setData('max_impressions', $this->normalizeNullableInt($data['max_impressions'] ?? null));
            $survey->setData('limit_daily', $this->normalizeNullableInt($data['limit_daily'] ?? null));
            $survey->setData('limit_weekly', $this->normalizeNullableInt($data['limit_weekly'] ?? null));
            $survey->setData('limit_monthly', $this->normalizeNullableInt($data['limit_monthly'] ?? null));
            $survey->setData('max_response_count', $this->normalizeNullableInt($data['max_response_count'] ?? null));

            $this->surveyRepository->save($survey);
            $surveyId = (int)$survey->getSurveyId();

            $this->saveQuestionMappings($surveyId, $data);
            $this->saveLocationMappings($surveyId, $data);

            $this->messageManager->addSuccessMessage(__('Survey journey has been saved.'));
            $this->dataPersistor->clear('artm_customersurvey_survey');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['survey_id' => $surveyId]);
            }

            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to save survey journey right now.'));
        }

        $this->dataPersistor->set('artm_customersurvey_survey', $data);
        return $resultRedirect->setPath('*/*/edit', ['survey_id' => $surveyId ?: null]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateData(array $data): void
    {
        $name = trim((string)($data['name'] ?? ''));
        $status = (string)($data['status'] ?? '');

        if ($name === '') {
            throw new LocalizedException(__('Survey name is required.'));
        }

        if (!in_array($status, Survey::getAvailableStatuses(), true)) {
            throw new LocalizedException(__('Please select a valid survey status.'));
        }

        $displayType = (string)($data['display_type'] ?? Survey::DISPLAY_TYPE_INLINE);
        if (!in_array($displayType, Survey::getAvailableDisplayTypes(), true)) {
            throw new LocalizedException(__('Please select a valid display type.'));
        }

        $inlinePosition = (string)($data['inline_position'] ?? '');
        if ($displayType === Survey::DISPLAY_TYPE_INLINE
            && $inlinePosition !== ''
            && !in_array($inlinePosition, Survey::getAvailableInlinePositions(), true)
        ) {
            throw new LocalizedException(__('Please select a valid inline position.'));
        }

        $notAnsweredFrequency = (string)($data['frequency_if_not_answered'] ?? Survey::FREQ_NOT_ANSWERED_EVERY_VISIT);
        if (!in_array($notAnsweredFrequency, Survey::getAvailableNotAnsweredFrequencies(), true)) {
            throw new LocalizedException(__('Please select a valid frequency for not answered state.'));
        }

        $answeredFrequency = (string)($data['frequency_if_answered'] ?? Survey::FREQ_ANSWERED_NEVER);
        if (!in_array($answeredFrequency, Survey::getAvailableAnsweredFrequencies(), true)) {
            throw new LocalizedException(__('Please select a valid frequency for answered state.'));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateSchemaReadiness(array $data): void
    {
        $customerGroupIds = $this->normalizeIds($data['customer_group_ids'] ?? []);
        if (empty($customerGroupIds)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('artm_customersurvey_survey');
        $columns = $connection->describeTable($tableName);

        if (!isset($columns['customer_group_ids'])) {
            throw new LocalizedException(
                __('Customer group targeting schema is not applied yet. Run setup:upgrade and clear cache before saving this field.')
            );
        }
    }

    /**
     * @param mixed $value
     */
    private function normalizeDate($value): ?string
    {
        $date = trim((string)$value);
        return $date === '' ? null : $date;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveQuestionMappings(int $surveyId, array $data): void
    {
        $connection = $this->surveyQuestionResource->getConnection();
        $table = $this->surveyQuestionResource->getMainTable();
        $connection->delete($table, ['survey_id = ?' => $surveyId]);

        $selectedQuestionIds = [];
        $ids = $this->normalizeIds($data['question_ids'] ?? []);
        foreach ($ids as $questionId) {
            $qId = (int)$questionId;
            if ($qId > 0) {
                $selectedQuestionIds[$qId] = $qId;
            }
        }

        $explicitOrderMap = [];
        if (isset($data['question_order_map'])) {
            $lines = preg_split('/\r\n|\r|\n/', (string)$data['question_order_map']);
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line === '' || strpos($line, ':') === false) {
                    continue;
                }
                [$questionId, $questionOrder] = array_map('trim', explode(':', $line, 2));
                $qId = (int)$questionId;
                if ($qId > 0) {
                    $explicitOrderMap[$qId] = (int)$questionOrder;
                    $selectedQuestionIds[$qId] = $qId;
                }
            }
        }

        $rows = [];
        $defaultOrder = 1;
        foreach ($selectedQuestionIds as $qId) {
            $rows[] = [
                'survey_id' => $surveyId,
                'question_id' => $qId,
                'question_order' => $explicitOrderMap[$qId] ?? $defaultOrder,
            ];
            $defaultOrder++;
        }

        if (!empty($rows)) {
            $connection->insertMultiple($table, array_values($rows));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveLocationMappings(int $surveyId, array $data): void
    {
        $connection = $this->surveyLocationResource->getConnection();
        $table = $this->surveyLocationResource->getMainTable();
        $connection->delete($table, ['survey_id = ?' => $surveyId]);

        $rows = [];
        $locationIds = $this->normalizeIds($data['location_ids'] ?? []);
        foreach ($locationIds as $locationId) {
            $locId = (int)$locationId;
            if ($locId <= 0) {
                continue;
            }
            $rows[$locId] = [
                'survey_id' => $surveyId,
                'location_id' => $locId,
            ];
        }

        if (!empty($rows)) {
            $connection->insertMultiple($table, array_values($rows));
        }
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeIds($value): array
    {
        $rawValues = $this->extractIdValues($value);
        $ids = [];

        foreach ($rawValues as $raw) {
            $id = trim((string)$raw);
            if ($id === '' || !ctype_digit($id)) {
                continue;
            }
            $ids[$id] = $id;
        }

        return array_values($ids);
    }

    /**
     * @param mixed $value
     * @return array<int, mixed>
     */
    private function extractIdValues($value): array
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }

            if ($trimmed[0] === '[' || $trimmed[0] === '{') {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return $this->extractIdValues($decoded);
                }
            }

            return array_map('trim', explode(',', $trimmed));
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (is_bool($item)) {
                if ($item === true && (is_int($key) || ctype_digit((string)$key))) {
                    $result[] = (string)$key;
                }
                continue;
            }

            if (is_scalar($item) || $item === null) {
                $result[] = $item;
                continue;
            }

            if (is_array($item)) {
                if (isset($item['value']) && (is_scalar($item['value']) || $item['value'] === null)) {
                    $result[] = $item['value'];
                }
                if (isset($item['id']) && (is_scalar($item['id']) || $item['id'] === null)) {
                    $result[] = $item['id'];
                }
                $result = array_merge($result, $this->extractIdValues($item));
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     */
    private function normalizeNullableInt($value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        return (int)$value;
    }
}
