<?php
namespace ArtM\AdminAI\Api;

use ArtM\AdminAI\Model\ChatResult;

interface ChatResultRepositoryInterface
{
    public function create(): ChatResult;

    public function save(ChatResult $chatResult): ChatResult;
}
