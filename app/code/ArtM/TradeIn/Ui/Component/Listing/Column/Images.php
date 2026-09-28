<?php

namespace ArtM\TradeIn\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Store\Model\StoreManagerInterface;

class Images extends Column
{
    protected $storeManager;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $imageNames = $item[$fieldName];
                    $imageNames = ltrim($imageNames, ',');
                    $imageUrls = explode(',', $imageNames);
                    $imageHtml = '<div class="image_section_box" style="min-width: 100px;">';
                    
                    if (!empty($imageUrls) && !empty(trim($imageNames))) {
                        foreach ($imageUrls as $index => $imageUrl) {
                            if (trim($imageUrl) == '') {
                                continue;
                            }
                            $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                            $fullImageUrl = $baseUrl . 'pub/media/review_images/' . trim($imageUrl);
                            $imageHtml .= '<img src="' . $fullImageUrl . '" 
                                               alt="Image" 
                                               class="tradein-thumbnail" 
                                               data-image="' . $fullImageUrl . '"
                                               data-images="' . htmlspecialchars($imageNames) . '"
                                               data-index="' . $index . '"
                                               onclick="openImageModal(this)"
                                               style="width: 31%; display: inline-block; margin: 1%; border: 1px solid #d1d1d1; max-width: 60px; height: auto; cursor: pointer;" />';
                        }
                    } else {
                        $imageHtml .= 'No Image';
                    }
                    
                    $imageHtml .= '</div>';
                    $item[$fieldName] = $imageHtml;
                }
            }
        }

        return $dataSource;
    }
}
