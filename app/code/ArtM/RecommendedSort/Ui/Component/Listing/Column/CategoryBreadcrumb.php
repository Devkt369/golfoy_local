<?php

namespace ArtM\RecommendedSort\Ui\Component\Listing\Column;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryBreadcrumb extends Column
{
    protected $categoryRepository;
    protected $categoryCollectionFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CategoryRepository $categoryRepository,
        CollectionFactory $categoryCollectionFactory,
        array $components = [],
        array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!empty($item['categories'])) {
                    $categoryIds = explode(',', $item['categories']);
                    $breadcrumbs = [];

                    foreach ($categoryIds as $categoryId) {
                        try {
                            $category = $this->categoryRepository->get($categoryId);
                            $breadcrumb = [];

                            foreach (explode('/', $category->getPath()) as $pathId) {
                                if (in_array($pathId, ['1', '2'])) {
                                    continue; // Skip root and default
                                }
                                try {
                                    $pathCategory = $this->categoryRepository->get($pathId);
                                    $breadcrumb[] = $pathCategory->getName();
                                } catch (\Exception $e) {
                                    // Skip invalid categories
                                }
                            }

                            $breadcrumbs[] = implode(' > ', $breadcrumb);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }

                    $item[$this->getData('name')] = implode('<br/>', $breadcrumbs);
                }
            }
        }

        return $dataSource;
    }
}
