<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Question;

use ArtM\CustomerSurvey\Api\QuestionRepositoryInterface;
use Magento\Backend\App\Action;

class MassStatus extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::question_mass';

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
        $ids = (array)$this->getRequest()->getParam('question_ids', []);
        $status = $this->getRequest()->getParam('status', null);
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$ids) {
            $this->messageManager->addErrorMessage(__('Please select questions to update.'));
            return $resultRedirect->setPath('*/*/index');
        }

        if ($status === null || $status === '') {
            $this->messageManager->addErrorMessage(__('Invalid status value.'));
            return $resultRedirect->setPath('*/*/index');
        }

        $updated = 0;
        foreach ($ids as $id) {
            try {
                $question = $this->questionRepository->getById((int)$id);
                $question->setStatus((int)$status);
                $this->questionRepository->save($question);
                $updated++;
            } catch (\Exception $exception) {
                continue;
            }
        }

        if ($updated > 0) {
            $this->messageManager->addSuccessMessage(__('%1 question(s) updated.', $updated));
        } else {
            $this->messageManager->addErrorMessage(__('No question was updated.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
