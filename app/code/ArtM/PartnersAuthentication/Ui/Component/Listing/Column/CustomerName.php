<?php

namespace ArtM\PartnersAuthentication\Ui\Component\Listing\Column;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class CustomerName extends Column
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerRepositoryInterface $customerRepository,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $item['name'] ?? '';
                $email = $item['email'] ?? '';

                if ($email) {
                    try {
                        $customer = $this->customerRepository->get($email, 5);
                        $customerId = $customer->getId();

                        $url = $this->urlBuilder->getUrl(
                            'customer/index/edit',
                            ['id' => $customerId]
                        );

                        $item[$this->getData('name')] = sprintf(
                            '<a href="%s" target="_blank">%s</a>',
                            $url,
                            $name ?: $email
                        );
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        // Customer with this email doesn't exist
                        $item[$this->getData('name')] = $name;
                    }
                } else {
                    $item[$this->getData('name')] = $name;
                }
            }
        }

        return $dataSource;
    }
}
