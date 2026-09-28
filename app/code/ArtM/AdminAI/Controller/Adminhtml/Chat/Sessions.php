<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Chat;

use ArtM\AdminAI\Model\ChatHistoryService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Sessions extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ChatHistoryService
     */
    private $chatHistoryService;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ChatHistoryService $chatHistoryService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->chatHistoryService = $chatHistoryService;
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $adminUser = $this->_auth ? $this->_auth->getUser() : null;
        $adminUserId = $adminUser ? (int)$adminUser->getId() : 0;

        return $result->setData([
            'success' => true,
            'sessions' => $this->chatHistoryService->listSessions($adminUserId)
        ]);
    }
}
