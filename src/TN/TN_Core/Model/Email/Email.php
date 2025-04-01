<?php

namespace TN\TN_Core\Model\Email;

use SmartyException;
use TN\TN_Core\Attribute\Constraints\EmailAddress;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\CodeException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Template\Template;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Time\Time;

/**
 * an email message sent to someone
 *
 */
#[TableName('emails')]
class Email implements Persistence
{
    use MySQL;
    use PersistentModel;
    use MySQLPrune;

    protected static int $lifespan = Time::ONE_YEAR;
    protected static string $tsProp = 'ts';

    /** @var int timestamp of when the email was sent */
    public int $ts = 0;

    /** @var string subject of the email */
    public string $subject;

    /** @var string recipient's email address */
    #[EmailAddress]
    public string $to;

    /** @var string body of email */
    public string $body;

    /** @var bool has it sent? */
    public bool $sent = false;

    /**
     * @param SmartyException $e
     * @return void
     */
    public static function handleTemplateException(\Exception $e): void
    {
        throw new CodeException($e->getMessage(), 500);
    }

    /**
     * send an email from one of the templates
     * @param string $key
     * @param string $to
     * @param array $data
     * @return bool
     */
    public static function sendFromTemplate(string $key, string $to, array $data = []): bool
    {
        $template = Template::getInstanceByKey($key);
        try {
            $subject = $template->getSubject(array_merge(['to' => $to], $data));
            $body = $template->getBody(array_merge(['subject' => $subject, 'to' => $to], $data));
        } catch (\Exception $e) {
            self::handleTemplateException($e);
            return false;
        }

        $email = self::getInstance();

        try {
            $email->update([
                'subject' => $subject,
                'to' => $to,
                'body' => $body
            ]);
            return $email->send();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * send an email from plaintext
     * @param string $subject
     * @param string $body
     * @param string $to
     * @return bool
     */
    public static function sendFromPlainText(string $subject, string $body, string $to): bool
    {
        $email = self::getInstance();
        try {
            $email->update([
                'subject' => $subject,
                'to' => $to,
                'body' => $body
            ]);
            return $email->send();
        } catch (\Exception $e) {
            return false;
        }
    }

    /** @return bool send the email */
    public function send(): bool
    {
        if (!in_array($_ENV['ENV'], ['production'])) {
            return true;
        }

        // let's see if we have a bad email address for this email, and if so we'll need to not send it


        $transport = (new \Swift_SmtpTransport($_ENV['AWS_SES_HOST'], $_ENV['AWS_SES_PORT']))
            ->setUsername($_ENV['AWS_SES_USERNAME'])
            ->setPassword($_ENV['AWS_SES_PASSWORD'])
            ->setEncryption("tls");
        $mailer = new \Swift_Mailer($transport);
        $message = new \Swift_Message();
        $message->setSubject(($_ENV['ENV'] !== 'production' ? (strtoupper($_ENV['ENV']) . ': ') : '') . $this->subject)
            ->setFrom([$_ENV['SITE_EMAIL'] => $_ENV['SITE_NAME']])
            ->setTo($this->to)
            ->setBody($this->body)
            ->setContentType('text/html');
        $success = (bool)$mailer->send($message);
        if ($success) {
            $this->update([
                'sent' => true,
                'ts' => time()
            ]);
        }
        return $success;
    }
}
