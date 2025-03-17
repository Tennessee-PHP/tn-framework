<?php

namespace TN\TN_Core\Model\Email\Template\Testing;

class Testing extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'testing/sampletestemail';
    protected string $name = 'THIS IS A TEST EMAIL';
    protected string $subject = 'THIS IS A TEST SUBJECT';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Testing/SampleTestEmail.tpl';
    protected array $sampleData = [
        'testVar' => 'THIS IS A VARIABLE'
    ];
}