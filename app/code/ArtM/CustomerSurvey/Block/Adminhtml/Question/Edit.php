<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Question;

use ArtM\CustomerSurvey\Model\Question;
use ArtM\CustomerSurvey\Model\QuestionFactory;
use ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption\CollectionFactory as OptionCollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class Edit extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var QuestionFactory
     */
    private $questionFactory;

    /**
     * @var OptionCollectionFactory
     */
    private $optionCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        Registry $registry,
        QuestionFactory $questionFactory,
        OptionCollectionFactory $optionCollectionFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->questionFactory = $questionFactory;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->storeManager = $storeManager;
    }

    public function getQuestion(): Question
    {
        $question = $this->registry->registry('artm_customersurvey_question');
        if (!$question) {
            $question = $this->questionFactory->create();
        }

        return $question;
    }

    public function getQuestionTypes(): array
    {
        return [
            Question::TYPE_TEXT => (string)__('Text Input'),
            Question::TYPE_TEXTAREA => (string)__('Textarea'),
            Question::TYPE_RADIO => (string)__('Radio'),
            Question::TYPE_CHECKBOX => (string)__('Checkbox'),
            Question::TYPE_DROPDOWN => (string)__('Dropdown'),
            Question::TYPE_RATING => (string)__('Rating'),
            Question::TYPE_YES_NO => (string)__('Yes/No'),
            Question::TYPE_IMAGE_SELECTION => (string)__('Image Selection'),
        ];
    }

    public function getStatusOptions(): array
    {
        return [
            1 => (string)__('Enabled'),
            0 => (string)__('Disabled'),
        ];
    }

    public function getOptionBasedTypes(): array
    {
        return Question::getOptionBasedTypes();
    }

    public function getOptions(): array
    {
        $questionId = (int)$this->getQuestion()->getQuestionId();
        if (!$questionId) {
            return [];
        }

        $collection = $this->optionCollectionFactory->create();
        $collection->addFieldToFilter('question_id', $questionId);
        $collection->setOrder('sort_order', 'ASC');
        $collection->setOrder('option_id', 'ASC');

        return $collection->getItems();
    }

    public function getMediaBaseUrl(): string
    {
        return (string)$this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getOptionImageUrl(?string $image): string
    {
        if (!$image) {
            return '';
        }

        return $this->getMediaBaseUrl() . ltrim($image, '/');
    }
}
