<?php

declare(strict_types=1);

namespace ArtM\LiveChat\Controller\Token;

use ArtM\LiveChat\Model\Token;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use RuntimeException;

class Customer extends Action
{
    public function __construct(
        Context $context,
        private readonly Session $customerSession,
        private readonly JsonFactory $jsonFactory,
        private readonly Token $token
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, private', true);
        if (!$this->customerSession->isLoggedIn()) {
            return $result->setHttpResponseCode(401)->setData(['error' => 'Authentication required.']);
        }

        $customer = $this->customerSession->getCustomer();
        try {
            $accessToken = $this->token->issue([
                'sub' => (string) $customer->getId(),
                'role' => 'customer',
                'name' => trim((string) $customer->getFirstname() . ' ' . (string) $customer->getLastname()),
                'email' => (string) $customer->getEmail(),
            ]);
        } catch (RuntimeException $exception) {
            return $result->setHttpResponseCode(503)->setData(['error' => 'Live chat is not configured.']);
        }

        return $result->setData(['token' => $accessToken]);
    }
}