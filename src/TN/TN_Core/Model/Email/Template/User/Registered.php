<?php

namespace TN\TN_Core\Model\Email\Template\User;

class Registered extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'user/registered';
    protected string $name = 'Users Registered';
    protected string $subject = 'Welcome to {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Users/Registered.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'email' => 'userEmail@placeholder.com'
    ];
}