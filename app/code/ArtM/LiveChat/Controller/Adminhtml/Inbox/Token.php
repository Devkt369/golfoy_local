<?php

declare(strict_types=1);

namespace ArtM\LiveChat\Controller\Adminhtml\Inbox;

use ArtM\LiveChat\Model\Token as TokenService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use RuntimeException;

class Token extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_LiveChat::inbox';

    public function __construct(
        Context $context,
        private readonly Session $authSession,
        private readonly JsonFactory $jsonFactory,
        private readonly TokenService $token
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, private', true);
        $admin = $this->authSession->getUser();
        if (!$admin) {
            return $result->setHttpResponseCode(401)->setData(['error' => 'Authentication required.']);
        }

        try {
            $accessToken = $this->token->issue([
                'sub' => (string) $admin->getId(),
                'role' => 'agent',
                'name' => (string) $admin->getUserName(),
                'email' => (string) $admin->getEmail(),
            ]);
        } catch (RuntimeException $exception) {
            return $result->setHttpResponseCode(503)->setData(['error' => 'Live chat is not configured.']);
        }

        return $result->setData(['token' => $accessToken]);
    }
}