<?php

namespace ArtM\CustomerSurvey\Block\Adminhtml\Question;

use ArtM\CustomerSurvey\Model\Question;
use ArtM\CustomerSurvey\Model\ResourceModel\Question\Collection;
use ArtM\CustomerSurvey\Model\ResourceModel\Question\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;

class Index extends Template
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Collection|null
     */
    private $collection;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
        $this->request = $request;
    }

    public function getQuestions(): array
    {
        return $this->getCollection()->getItems();
    }

    public function getCollection(): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->collectionFactory->create();

            $search = trim((string)$this->request->getParam('search', ''));
            $type = (string)$this->request->getParam('type', '');
            $status = $this->request->getParam('status', '');

            if ($search !== '') {
                $this->collection->addFieldToFilter(
                    ['title', 'description'],
                    [
                        ['like' => '%' . $search . '%'],
                        ['like' => '%' . $search . '%'],
                    ]
                );
            }

            if ($type !== '') {
                $this->collection->addFieldToFilter('type', $type);
            }

            if ($status !== '') {
                $this->collection->addFieldToFilter('status', (int)$status);
            }

            $this->collection->setOrder('sort_order', 'ASC');
            $this->collection->setOrder('question_id', 'DESC');

            $pageSize = (int)$this->request->getParam('limit', 20);
            if (!in_array($pageSize, [20, 50, 100], true)) {
                $pageSize = 20;
            }
            $currentPage = max(1, (int)$this->request->getParam('p', 1));
            $this->collection->setPageSize($pageSize);
            $this->collection->setCurPage($currentPage);
        }

        return $this->collection;
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

    public function getSearchValue(): string
    {
        return (string)$this->request->getParam('search', '');
    }

    public function getTypeValue(): string
    {
        return (string)$this->request->getParam('type', '');
    }

    public function getStatusValue(): string
    {
        return (string)$this->request->getParam('status', '');
    }

    public function getLimitValue(): int
    {
        return (int)$this->getCollection()->getPageSize();
    }

    public function getCurrentPage(): int
    {
        return (int)$this->getCollection()->getCurPage();
    }

    public function getLastPageNumber(): int
    {
        return (int)$this->getCollection()->getLastPageNumber();
    }

    public function getTotalCount(): int
    {
        return (int)$this->getCollection()->getSize();
    }

    public function getFromRecord(): int
    {
        if ($this->getTotalCount() === 0) {
            return 0;
        }

        return (int)(($this->getCurrentPage() - 1) * $this->getLimitValue() + 1);
    }

    public function getToRecord(): int
    {
        return (int)min($this->getCurrentPage() * $this->getLimitValue(), $this->getTotalCount());
    }
}
