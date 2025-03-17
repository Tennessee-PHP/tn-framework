<?php

namespace TN\TN_CMS\Component\TinyMCEComment\Thread;

use TN\TN_CMS\Model\TinyMCEComment\Thread;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;

class Delete extends JSON {
    public string|int $threadId;
    public function prepare(): void
    {
        try {
            $thread = Thread::readFromId($this->threadId);
            if (!$thread) {
                throw new ValidationException('Thread no longer exists');
            }
            $thread->delete();
            $this->data = [
                'canDelete' => true
            ];
        } catch (ValidationException $e) {
            $this->data = [
                'canDelete' => false,
                'reason' => $e->getMessage()
            ];
        }
    }
}