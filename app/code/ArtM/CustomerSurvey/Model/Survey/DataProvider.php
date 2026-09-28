<?php

namespace ArtM\CustomerSurvey\Model\Survey;

use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory as LocationCollectionFactory;
use ArtM\CustomerSurvey\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use ArtM\CustomerSurvey\Model\ResourceModel\Survey\CollectionFactory;
use ArtM\CustomerSurvey\Model\ResourceModel\SurveyLocation\CollectionFactory as SurveyLocationCollectionFactory;
use ArtM\CustomerSurvey\Model\ResourceModel\SurveyQuestion\CollectionFactory as SurveyQuestionCollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private $loadedData = [];

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var SurveyQuestionCollectionFactory
     */
    private $surveyQuestionCollectionFactory;

    /**
     * @var SurveyLocationCollectionFactory
     */
    private $surveyLocationCollectionFactory;

    /**
     * @var QuestionCollectionFactory
     */
    private $questionCollectionFactory;

    /**
     * @var LocationCollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        SurveyQuestionCollectionFactory $surveyQuestionCollectionFactory,
        SurveyLocationCollectionFactory $surveyLocationCollectionFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        LocationCollectionFactory $locationCollectionFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->surveyQuestionCollectionFactory = $surveyQuestionCollectionFactory;
        $this->surveyLocationCollectionFactory = $surveyLocationCollectionFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->locationCollectionFactory = $locationCollectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $survey) {
            $surveyId = (int)$survey->getId();
            $data = $survey->getData();

            $questionRows = [];
            $questionOrderMap = [];
            $questionMappings = $this->surveyQuestionCollectionFactory->create();
            $questionMappings->addFieldToFilter('survey_id', $surveyId);
            $questionMappings->setOrder('question_order', 'ASC');
            foreach ($questionMappings as $mapping) {
                $questionId = (int)$mapping->getData('question_id');
                $questionOrder = (int)$mapping->getData('question_order');
                $questionRows[] = ['question_id' => $questionId, 'question_order' => $questionOrder];
                $questionOrderMap[] = $questionId . ':' . $questionOrder;
            }
            $data['question_mappings'] = $questionRows;
            $data['question_ids'] = array_map('strval', array_column($questionRows, 'question_id'));
            $data['question_order_map'] = implode(PHP_EOL, $questionOrderMap);

            $locationIds = [];
            $locationMappings = $this->surveyLocationCollectionFactory->create();
            $locationMappings->addFieldToFilter('survey_id', $surveyId);
            foreach ($locationMappings as $mapping) {
                $locationIds[] = (string)$mapping->getData('location_id');
            }
            $data['location_ids'] = $locationIds;

            $popupTriggers = trim((string)($data['popup_triggers'] ?? ''));
            $data['popup_triggers'] = $popupTriggers === ''
                ? []
                : array_values(array_filter(array_map('trim', explode(',', $popupTriggers)), static function (string $id): bool {
                    return $id !== '';
                }));

            $storeIds = trim((string)($data['store_ids'] ?? ''));
            $data['store_ids'] = $storeIds === ''
                ? []
                : array_values(array_filter(array_map('trim', explode(',', $storeIds)), static function (string $id): bool {
                    return $id !== '';
                }));

            $customerGroupIds = trim((string)($data['customer_group_ids'] ?? ''));
            $data['customer_group_ids'] = $customerGroupIds === ''
                ? []
                : array_values(array_unique(array_filter(array_map('trim', explode(',', $customerGroupIds)), static function (string $id): bool {
                    return $id !== '' && ctype_digit($id);
                })));

            $this->loadedData[$surveyId] = $data;
        }

        $persistedData = $this->dataPersistor->get('artm_customersurvey_survey');
        if (!empty($persistedData)) {
            if (isset($persistedData['customer_group_ids']) && is_string($persistedData['customer_group_ids'])) {
                $persistedData['customer_group_ids'] = array_values(array_unique(array_filter(array_map('trim', explode(',', $persistedData['customer_group_ids'])), static function (string $id): bool {
                    return $id !== '' && ctype_digit($id);
                })));
            }
            $survey = $this->collection->getNewEmptyItem();
            $survey->setData($persistedData);
            $this->loadedData[(int)$survey->getId()] = $survey->getData();
            $this->dataPersistor->clear('artm_customersurvey_survey');
        }

        return $this->loadedData;
    }
}
