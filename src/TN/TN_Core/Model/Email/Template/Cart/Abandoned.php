<?php

namespace TN\TN_Core\Model\Email\Template\Cart;

class Abandoned extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'cart/abandoned';
    protected string $name = 'Abandoned Cart Reminder';
    protected string $subject = 'Abandoned Cart at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Cart/Body.tpl';
    protected array $sampleData = [
        'planName' => 'planName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}