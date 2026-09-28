<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Survey;

use ArtM\CustomerSurvey\Api\SurveyRepositoryInterface;
use Magento\Backend\App\Action;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::survey_delete';

    /**
     * @var SurveyRepositoryInterface
     */
    private $surveyRepository;

    public function __construct(
        Action\Context $context,
        SurveyRepositoryInterface $surveyRepository
    ) {
        parent::__construct($context);
        $this->surveyRepository = $surveyRepository;
    }

    public function execute()
    {
        $surveyId = (int)$this->getRequest()->getParam('survey_id');
        if (!$surveyId) {
            $this->messageManager->addErrorMessage(__('Unable to find survey journey to delete.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        try {
            $this->surveyRepository->deleteById($surveyId);
            $this->messageManager->addSuccessMessage(__('Survey journey has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to delete survey journey right now.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
