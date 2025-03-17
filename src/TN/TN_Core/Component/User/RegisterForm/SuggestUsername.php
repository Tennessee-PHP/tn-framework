<?php

namespace TN\TN_Core\Component\User\RegisterForm;

use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\User\User;

class SuggestUsername extends JSON {
    #[FromQuery] public string $email = '';
    public function prepare(): void {
        $this->data = [
            'result' => 'success',
            'username' => User::emailToUniqueUsername($this->email)
        ];
    }

}