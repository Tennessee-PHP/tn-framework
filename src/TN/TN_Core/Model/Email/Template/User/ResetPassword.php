<?php

namespace TN\TN_Core\Model\Email\Template\User;

class ResetPassword extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'user/resetpassword';
    protected string $name = 'Reset Password Link';
    protected string $subject = 'Password Reset at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/User/ResetPassword.tpl';
    protected array $sampleData = [
        'username' => 'username',
        'key' => 'somelongkey'
    ];
}
