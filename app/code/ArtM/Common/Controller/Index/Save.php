<?php

namespace ArtM\Common\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use ArtM\OrderFeedback\Model\SurveyFactoryFactory;

class Save extends Action
{
    protected $resultJsonFactory;
    protected $resourceConnection;
    protected $SurveyFactoryFactory;

    public function __construct(
        Context $context,
        SurveyFactoryFactory $SurveyFactoryFactory,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->SurveyFactoryFactory = $SurveyFactoryFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $customerId = $this->getRequest()->getParam('customer_id');

            if (!$orderId || !$customerId) {
                return $resultJson->setData(['status' => 'error', 'message' => 'Missing order_id or customer_id']);
            }

            // Get all survey params except order_id and customer_id
            $newSurveyResults = $this->getRequest()->getParams();
            unset($newSurveyResults['order_id'], $newSurveyResults['customer_id']);
            $newSurveyResultsJson = json_encode($newSurveyResults);

            // Load existing survey for customer
            $surveyModel = $this->SurveyFactoryFactory->create();
            $existingSurvey = $surveyModel->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->setOrder('craeted_at', 'DESC')
                ->getFirstItem();

            $shouldCreateNew = false;

            if ($existingSurvey->getId()) {
                $createdAt = strtotime($existingSurvey->getData('craeted_at'));
                $oneYearAgo = strtotime('-1 year');

                // ✅ Check if older than 1 year
                if ($createdAt < $oneYearAgo) {
                    $shouldCreateNew = true;
                }
            } else {
                $shouldCreateNew = true;
            }

            if ($shouldCreateNew) {
                // Create a fresh record
                $surveyModel->addData([
                    'order_id' => $orderId,
                    'customer_id' => $customerId,
                    'survey_results' => $newSurveyResultsJson,
                    'craeted_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                // Update existing record
                $existingResults = $existingSurvey->getData('survey_results');
                $updatedResults = $existingResults . "\n" . $newSurveyResultsJson;

                $existingSurvey->setData('survey_results', $updatedResults);
                $existingSurvey->setData('order_id', $orderId);
                $surveyModel = $existingSurvey;
            }

            $surveyModel->save();

            return $resultJson->setData([
                'status' => 'success',
                'message' => $shouldCreateNew ? 'New survey created' : 'Existing survey updated',
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

}
