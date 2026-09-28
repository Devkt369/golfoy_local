<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Chat;

use ArtM\AdminAI\Model\ChatHistoryService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class RenameSession extends Action implements HttpPostActionInterface
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
        $sessionId = (int)$this->getRequest()->getParam('session_id', 0);
        $title = (string)$this->getRequest()->getParam('title', '');

        $ok = $this->chatHistoryService->renameSession($sessionId, $title);

        return $result->setData([
            'success' => $ok
        ]);
    }
}
