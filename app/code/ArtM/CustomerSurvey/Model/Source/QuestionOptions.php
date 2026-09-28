<?php

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\ResourceModel\Question\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class QuestionOptions implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        $collection = $this->collectionFactory->create();
        $collection->setOrder('title', 'ASC');

        foreach ($collection as $question) {
            $options[] = [
                'value' => (string)$question->getId(),
                'label' => sprintf('#%d %s', (int)$question->getId(), (string)$question->getData('title')),
            ];
        }

        return $options;
    }
}
