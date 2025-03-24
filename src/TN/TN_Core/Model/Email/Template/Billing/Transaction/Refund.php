<?php

namespace TN\TN_Core\Model\Email\Template\Billing\Transaction;

class Refund extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'billing/transaction/refund';
    protected string $name = 'Refund';
    protected string $subject = 'Refund at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Billing/Transaction/Refund/Body.tpl';
    protected array $sampleData = [
        'amount' => '20',
        'originalTs' => '1653572238',
        'SITE_NAME' => '{$SITE_NAME}.com'
    ];
}
