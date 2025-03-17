<?php

namespace TN\TN_Core\Model\Email\Template;

use TN\TN_Core\Component\TemplateEngine;
use TN\TN_Core\Model\Email\CustomTemplate;

/**
 * an email template
 * 
 */
class Template
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;
    use \TN\TN_Core\Trait\Template\Evaluate;

    /** @var string identifying key */
    protected string $key;

    /** @var string a name to identify it */
    protected string $name;

    /** @var string default subject */
    protected string $subject;

    /** @var string default template file */
    protected string $defaultTemplateFile;

    /** @var array array of sample data for testing of new templates */
    protected array $sampleData = [];

    /** @var array array of error messages */
    protected array $errors;

    /** @return CustomTemplate|null */
    private function getCustomTemplate(): ?CustomTemplate
    {
        return CustomTemplate::getFromKey($this->key);
    }

    /** get the subject to use */
    public function getSubject(array $data = []): string
    {
        $customTemplate = $this->getCustomTemplate();
        $data['SITE_NAME'] = $_ENV['SITE_NAME'];
        $data['SITE_EMAIL'] = $_ENV['SITE_EMAIL'];
        $subjectTemplateString = $customTemplate instanceof CustomTemplate ? $customTemplate->subject : $this->subject;
        return $this->evaluateTemplate('string:' . $subjectTemplateString, $data);
    }

    /**
     * @param array $data
     * @return string|bool
     */
    public function getBody(array $data = []): string|bool
    {
        $customTemplate = $this->getCustomTemplate();
        if ($customTemplate instanceof CustomTemplate) {
            $bodyTpl = 'string:' . $customTemplate->template;
        } else {
            $bodyTpl = $this->defaultTemplateFile;
        }
        $data['SITE_NAME'] = $_ENV['SITE_NAME'];
        $data['SITE_EMAIL'] = $_ENV['SITE_EMAIL'];
        $body = $this->evaluateTemplate($bodyTpl, $data);
        if ($body === false) {
            return false;
        }
        try {
            $engine = TemplateEngine::getInstance();
            $engine->assignData(array_merge($data, ['body' => $body]));
            return $engine->fetch('TN/Model/Email/Email.tpl');
        } catch (\Exception $e) {
            return false;
        }
    }

}