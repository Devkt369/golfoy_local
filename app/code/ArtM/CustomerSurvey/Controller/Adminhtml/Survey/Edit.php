<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Survey;

use ArtM\CustomerSurvey\Api\SurveyRepositoryInterface;
use ArtM\CustomerSurvey\Model\SurveyFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::survey_journeys';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var SurveyRepositoryInterface
     */
    private $surveyRepository;

    /**
     * @var SurveyFactory
     */
    private $surveyFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        SurveyRepositoryInterface $surveyRepository,
        SurveyFactory $surveyFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->surveyRepository = $surveyRepository;
        $this->surveyFactory = $surveyFactory;
    }

    public function execute()
    {
        $surveyId = (int)$this->getRequest()->getParam('survey_id');

        try {
            $survey = $surveyId
                ? $this->surveyRepository->getById($surveyId)
                : $this->surveyFactory->create();
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('This survey journey no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $this->registry->register('artm_customersurvey_survey', $survey);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_CustomerSurvey::survey_journeys');
        $resultPage->getConfig()->getTitle()->prepend($surveyId ? __('Edit Survey Journey') : __('New Survey Journey'));

        return $resultPage;
    }
}
