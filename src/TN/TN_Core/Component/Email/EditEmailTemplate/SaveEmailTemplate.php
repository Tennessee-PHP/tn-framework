<?php

namespace TN\TN_Core\Component\Email\EditEmailTemplate;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\Email\CustomTemplate;

class SaveEmailTemplate extends JSON {
    public function prepare(): void
    {
        $key = $_POST['key'];
        $name = $_POST['name'];
        $body = $_POST['body'];
        $subject = $_POST['subject'];

        // save whatever values are being passed in
        $template = CustomTemplate::getFromKey($key);

        if (is_bool($template)) {
            $template = CustomTemplate::getInstance();
        }

        $template->update([
            'key' => $key,
            'subject' => $subject,
            'template' => $body,
        ]);

        $this->data = [
            'success' => true,
            'message' => 'Email template saved successfully'
        ];
    }
}