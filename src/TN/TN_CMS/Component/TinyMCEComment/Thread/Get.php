<?php

namespace TN\TN_CMS\Component\TinyMCEComment\Thread;

use TN\TN_CMS\Model\TinyMCEComment\Thread;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;

class Get extends JSON {
    public string|int $threadId;
    public function prepare(): void
    {
        $thread = Thread::readFromId($this->threadId);
        if (!$thread) {
            throw new ValidationException('Thread no longer exists');
        }
        $this->data = $thread->getData();
    }
}