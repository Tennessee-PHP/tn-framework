<?php

namespace TN\TN_Core\Model\Email\Template\ManualTest;

/**
 * Temporary template for manual SMTP / Resend checks via CLI.
 * Safe to delete after validation (see ne6-php/tmp/send-element-email-test.php).
 */
class ElementTest extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'manualtest/elementtest';
    protected string $name = 'Manual element email test';
    protected string $subject = '{$testSubject}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/ManualTest/ElementTest.tpl';
    protected array $sampleData = [
        'testSubject' => 'New Element Email Test',
        'paragraph1' => 'First paragraph.',
        'paragraph2' => 'Second paragraph.',
    ];
}
