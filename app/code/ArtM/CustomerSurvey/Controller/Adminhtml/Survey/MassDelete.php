<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Survey;

use ArtM\CustomerSurvey\Api\SurveyRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::survey_mass';

    /** @var Filter */
    private $filter;

    /** @var \ArtM\CustomerSurvey\Model\ResourceModel\Survey\CollectionFactory */
    private $collectionFactory;

    /** @var SurveyRepositoryInterface */
    private $surveyRepository;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        \ArtM\CustomerSurvey\Model\ResourceModel\Survey\CollectionFactory $collectionFactory,
        SurveyRepositoryInterface $surveyRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->surveyRepository = $surveyRepository;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;

        foreach ($collection as $survey) {
            try {
                $this->surveyRepository->deleteById((int)$survey->getId());
                $deleted++;
            } catch (\Exception $exception) {
            }
        }

        if ($deleted > 0) {
            $this->messageManager->addSuccessMessage(__('A total of %1 survey journeys have been deleted.', $deleted));
        } else {
            $this->messageManager->addNoticeMessage(__('No survey journeys were deleted.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
