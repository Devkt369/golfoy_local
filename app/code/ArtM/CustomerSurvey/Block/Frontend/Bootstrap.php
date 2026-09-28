<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Bootstrap extends Template
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Template\Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
    }

    /**
     * @return array<string, mixed>
     */
    public function getWidgetConfig(): array
    {
        $store = $this->storeManager->getStore();
        $isSecure = (bool)$store->isCurrentlySecure();

        return [
            'storeId' => (int)$store->getId(),
            'mediaUrl' => $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
            'api' => [
                'eligible' => $this->getUrl('customersurvey/ajax/eligible', ['_secure' => $isSecure]),
                'impression' => $this->getUrl('customersurvey/ajax/impression', ['_secure' => $isSecure]),
                'closed' => $this->getUrl('customersurvey/ajax/closed', ['_secure' => $isSecure]),
                'popupOpen' => $this->getUrl('customersurvey/ajax/popupOpen', ['_secure' => $isSecure]),
                'answered' => $this->getUrl('customersurvey/ajax/answered', ['_secure' => $isSecure]),
                'submit' => $this->getUrl('customersurvey/ajax/submit', ['_secure' => $isSecure]),
            ],
        ];
    }
}
