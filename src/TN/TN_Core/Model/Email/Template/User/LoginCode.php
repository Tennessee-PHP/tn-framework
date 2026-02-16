<?php

namespace TN\TN_Core\Model\Email\Template\User;

class LoginCode extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'user/logincode';
    protected string $name = 'Login Code';
    protected string $subject = 'Your {$SITE_NAME} login code';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/User/LoginCode.tpl';
    protected array $sampleData = [
        'username' => 'username',
        'code' => '123456',
    ];
}
