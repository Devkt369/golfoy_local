<?php
namespace ArtM\AdminAI\Model\Repository;

use ArtM\AdminAI\Api\ChatResultRepositoryInterface;
use ArtM\AdminAI\Model\ChatResult;
use ArtM\AdminAI\Model\ChatResultFactory;
use ArtM\AdminAI\Model\ResourceModel\ChatResult as ChatResultResource;

class ChatResultRepository implements ChatResultRepositoryInterface
{
    /** @var ChatResultFactory */
    private $chatResultFactory;

    /** @var ChatResultResource */
    private $chatResultResource;

    public function __construct(
        ChatResultFactory $chatResultFactory,
        ChatResultResource $chatResultResource
    ) {
        $this->chatResultFactory = $chatResultFactory;
        $this->chatResultResource = $chatResultResource;
    }

    public function create(): ChatResult
    {
        return $this->chatResultFactory->create();
    }

    public function save(ChatResult $chatResult): ChatResult
    {
        $this->chatResultResource->save($chatResult);
        return $chatResult;
    }
}
