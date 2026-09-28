<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Question;

use ArtM\CustomerSurvey\Api\QuestionRepositoryInterface;
use Magento\Backend\App\Action;

class MassDelete extends Action
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
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$ids) {
            $this->messageManager->addErrorMessage(__('Please select questions to delete.'));
            return $resultRedirect->setPath('*/*/index');
        }

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->questionRepository->deleteById((int)$id);
                $deleted++;
            } catch (\Exception $exception) {
                continue;
            }
        }

        if ($deleted > 0) {
            $this->messageManager->addSuccessMessage(__('%1 question(s) deleted.', $deleted));
        } else {
            $this->messageManager->addErrorMessage(__('No question was deleted.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
