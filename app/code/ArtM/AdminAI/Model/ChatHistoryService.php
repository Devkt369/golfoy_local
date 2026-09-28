<?php
namespace ArtM\AdminAI\Model;

use ArtM\AdminAI\Model\ResourceModel\ChatMessage\CollectionFactory as ChatMessageCollectionFactory;
use ArtM\AdminAI\Model\ResourceModel\ChatSession\CollectionFactory as ChatSessionCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ChatHistoryService
{
    /**
     * @var ChatSessionFactory
     */
    private $chatSessionFactory;

    /**
     * @var ChatMessageFactory
     */
    private $chatMessageFactory;

    /**
     * @var ChatSessionCollectionFactory
     */
    private $chatSessionCollectionFactory;

    /**
     * @var ChatMessageCollectionFactory
     */
    private $chatMessageCollectionFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ChatSessionFactory $chatSessionFactory
     * @param ChatMessageFactory $chatMessageFactory
     * @param ChatSessionCollectionFactory $chatSessionCollectionFactory
     * @param ChatMessageCollectionFactory $chatMessageCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        ChatSessionFactory $chatSessionFactory,
        ChatMessageFactory $chatMessageFactory,
        ChatSessionCollectionFactory $chatSessionCollectionFactory,
        ChatMessageCollectionFactory $chatMessageCollectionFactory,
        DateTime $dateTime
    ) {
        $this->chatSessionFactory = $chatSessionFactory;
        $this->chatMessageFactory = $chatMessageFactory;
        $this->chatSessionCollectionFactory = $chatSessionCollectionFactory;
        $this->chatMessageCollectionFactory = $chatMessageCollectionFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * @param string|null $title
     * @param int $adminUserId
     * @param string $adminUsername
     * @param string $adminName
     * @return int
     */
    public function createSession(
        ?string $title = null,
        int $adminUserId = 0,
        string $adminUsername = '',
        string $adminName = ''
    ): int {
        $title = trim((string)$title);
        if ($title === '') {
            $title = 'New Chat - ' . $this->dateTime->gmtDate('Y-m-d H:i');
        }

        $session = $this->chatSessionFactory->create();
        $session->setData('title', $title);
        if ($adminUserId > 0) {
            $session->setData('admin_user_id', $adminUserId);
            $session->setData('admin_username', $adminUsername);
            $session->setData('admin_name', $adminName);
        }

        try {
            $session->save();
        } catch (\Exception $e) {
            // Columns may not exist yet (setup:upgrade not run).
            // Retry saving without user fields so chats still work.
            $session->unsetData('admin_user_id');
            $session->unsetData('admin_username');
            $session->unsetData('admin_name');
            $session->setId(null);
            $session->save();
        }

        return (int)$session->getId();
    }

    /**
     * @param int $sessionId
     * @param string $role
     * @param string $content
     * @return void
     */
    public function appendMessage(int $sessionId, string $role, string $content): void
    {
        $content = trim($content);
        if ($sessionId <= 0 || $content === '') {
            return;
        }

        $message = $this->chatMessageFactory->create();
        $message->setData('session_id', $sessionId);
        $message->setData('role', $role);
        $message->setData('content', $content);
        $message->save();

        $session = $this->chatSessionFactory->create()->load($sessionId);
        if ($session->getId()) {
            $session->setData('updated_at', $this->dateTime->gmtDate());
            $session->save();
        }
    }

    /**
     * List sessions for a specific admin user.
     *
     * @param int $adminUserId  0 = no filter (returns all)
     * @return array
     */
    public function listSessions(int $adminUserId = 0): array
    {
        $sessions = [];
        $collection = $this->chatSessionCollectionFactory->create();
        if ($adminUserId > 0) {
            try {
                $collection->addFieldToFilter('admin_user_id', $adminUserId);
            } catch (\Exception $e) {
                // Column not yet created; return all sessions.
            }
        }
        $collection->setOrder('updated_at', 'DESC');

        foreach ($collection as $session) {
            $sessionId = (int)$session->getId();
            $sessions[] = [
                'session_id'     => $sessionId,
                'title'          => (string)$session->getData('title'),
                'created_at'     => (string)$session->getData('created_at'),
                'updated_at'     => (string)$session->getData('updated_at'),
                'last_message'   => $this->getLastMessagePreview($sessionId),
                'admin_user_id'  => (int)$session->getData('admin_user_id'),
                'admin_username' => (string)$session->getData('admin_username'),
                'admin_name'     => (string)$session->getData('admin_name')
            ];
        }

        return $sessions;
    }

    /**
     * List ALL sessions across all admin users (for super admin view).
     *
     * @return array
     */
    public function listAllSessions(): array
    {
        return $this->listSessions(0);
    }

    /**
     * @param int $sessionId
     * @return array
     */
    public function getMessages(int $sessionId): array
    {
        if ($sessionId <= 0) {
            return [];
        }

        $messages = [];
        $collection = $this->chatMessageCollectionFactory->create();
        $collection->addFieldToFilter('session_id', $sessionId);
        $collection->setOrder('message_id', 'ASC');

        foreach ($collection as $message) {
            $messages[] = [
                'message_id' => (int)$message->getId(),
                'session_id' => (int)$message->getData('session_id'),
                'role' => (string)$message->getData('role'),
                'content' => (string)$message->getData('content'),
                'created_at' => (string)$message->getData('created_at')
            ];
        }

        return $messages;
    }

    /**
     * @param int $sessionId
     * @param string $title
     * @return bool
     */
    public function renameSession(int $sessionId, string $title): bool
    {
        $title = trim($title);
        if ($sessionId <= 0 || $title === '') {
            return false;
        }

        $session = $this->chatSessionFactory->create()->load($sessionId);
        if (!$session->getId()) {
            return false;
        }

        $session->setData('title', substr($title, 0, 255));
        $session->setData('updated_at', $this->dateTime->gmtDate());
        $session->save();

        return true;
    }

    /**
     * Check whether a session exists and optionally belongs to the given admin user.
     *
     * @param int $sessionId
     * @param int $adminUserId  0 = skip ownership check
     * @return bool
     */
    public function sessionExists(int $sessionId, int $adminUserId = 0): bool
    {
        if ($sessionId <= 0) {
            return false;
        }

        $session = $this->chatSessionFactory->create()->load($sessionId);
        if (!$session->getId()) {
            return false;
        }

        if ($adminUserId > 0 && (int)$session->getData('admin_user_id') !== $adminUserId) {
            return false;
        }

        return true;
    }

    /**
     * If the session still has the default "New Chat - ..." title and this is the
     * first message, auto-rename using the first 60 characters of that message.
     *
     * @param int $sessionId
     * @param string $firstMessage
     * @return void
     */
    public function autoTitleIfDefault(int $sessionId, string $firstMessage): void
    {
        if ($sessionId <= 0) {
            return;
        }

        $session = $this->chatSessionFactory->create()->load($sessionId);
        if (!$session->getId()) {
            return;
        }

        $currentTitle = (string)$session->getData('title');
        if (strpos($currentTitle, 'New Chat - ') !== 0) {
            return;
        }

        $collection = $this->chatMessageCollectionFactory->create();
        $collection->addFieldToFilter('session_id', $sessionId);
        if ($collection->getSize() !== 1) {
            return;
        }

        $autoTitle = substr(trim($firstMessage), 0, 60);
        if ($autoTitle === '') {
            return;
        }

        $session->setData('title', $autoTitle);
        $session->setData('updated_at', $this->dateTime->gmtDate());
        $session->save();
    }

    /**
     * @param int $sessionId
     * @return string
     */
    private function getLastMessagePreview(int $sessionId): string
    {
        $collection = $this->chatMessageCollectionFactory->create();
        $collection->addFieldToFilter('session_id', $sessionId);
        $collection->setOrder('message_id', 'DESC');
        $collection->setPageSize(1);

        $last = $collection->getFirstItem();
        if (!$last->getId()) {
            return '';
        }

        return substr((string)$last->getData('content'), 0, 120);
    }
}
