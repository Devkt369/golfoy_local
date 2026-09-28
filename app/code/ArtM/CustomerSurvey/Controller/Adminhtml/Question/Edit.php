<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Question;

use ArtM\CustomerSurvey\Api\QuestionRepositoryInterface;
use ArtM\CustomerSurvey\Model\QuestionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::question_bank';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var QuestionRepositoryInterface
     */
    private $questionRepository;

    /**
     * @var QuestionFactory
     */
    private $questionFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        QuestionRepositoryInterface $questionRepository,
        QuestionFactory $questionFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
        $this->questionRepository = $questionRepository;
        $this->questionFactory = $questionFactory;
    }

    public function execute()
    {
        $questionId = (int)$this->getRequest()->getParam('question_id');

        try {
            $question = $questionId
                ? $this->questionRepository->getById($questionId)
                : $this->questionFactory->create();
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('This question no longer exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        $this->registry->register('artm_customersurvey_question', $question);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_CustomerSurvey::question_bank');
        $resultPage->getConfig()->getTitle()->prepend(
            $questionId ? __('Edit Question') : __('New Question')
        );

        return $resultPage;
    }
}
