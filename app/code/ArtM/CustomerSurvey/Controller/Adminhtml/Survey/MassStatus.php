<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Survey;

use ArtM\CustomerSurvey\Model\Survey;
use ArtM\CustomerSurvey\Model\ResourceModel\Survey as SurveyResource;
use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;

class MassStatus extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::survey_mass';

    /** @var Filter */
    private $filter;

    /** @var \ArtM\CustomerSurvey\Model\ResourceModel\Survey\CollectionFactory */
    private $collectionFactory;

    /** @var SurveyResource */
    private $surveyResource;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        \ArtM\CustomerSurvey\Model\ResourceModel\Survey\CollectionFactory $collectionFactory,
        SurveyResource $surveyResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->surveyResource = $surveyResource;
    }

    public function execute()
    {
        $status = (string)$this->getRequest()->getParam('status', Survey::STATUS_DRAFT);
        if (!in_array($status, Survey::getAvailableStatuses(), true)) {
            $this->messageManager->addErrorMessage(__('Invalid status selected.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated = 0;

        foreach ($collection as $survey) {
            try {
                $survey->setData('status', $status);
                $this->surveyResource->save($survey);
                $updated++;
            } catch (\Exception $exception) {
            }
        }

        if ($updated > 0) {
            $this->messageManager->addSuccessMessage(__('A total of %1 survey journeys have been updated.', $updated));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
