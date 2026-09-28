<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Question;

use ArtM\CustomerSurvey\Api\QuestionRepositoryInterface;
use Magento\Backend\App\Action;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::question_delete';

    /**
     * @var QuestionRepositoryInterface
     */
    private $questionRepository;

    public function __construct(
        Action\Context $context,
        QuestionRepositoryInterface $questionRepository
    ) {
        parent::__construct($context);
        $this->questionRepository = $questionRepository;
    }

    public function execute()
    {
        $questionId = (int)$this->getRequest()->getParam('question_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$questionId) {
            $this->messageManager->addErrorMessage(__('Question id is required.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->questionRepository->deleteById($questionId);
            $this->messageManager->addSuccessMessage(__('Question has been deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Unable to delete question.'));
            return $resultRedirect->setPath('*/*/edit', ['question_id' => $questionId]);
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
