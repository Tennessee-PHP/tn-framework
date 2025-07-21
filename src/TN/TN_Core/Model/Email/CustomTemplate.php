<?php

namespace TN\TN_Core\Model\Email;

use SmartyException;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Readable;
use TN\TN_Core\Component\TemplateEngine;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Template\Template;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

#[TableName('email_custom_templates')]
class CustomTemplate implements Persistence
{
    use MySQL;
    use PersistentModel;
    use \TN\TN_Core\Trait\Template\Evaluate;

    public string $key;
    #[Strlen(3, 500)] #[Readable('Email subject')] public string $subject;
    #[Readable('Email template')] public string $template;


    /** apply the defaults of the template */
    public function applyDefaults()
    {
        $template = Template::getInstanceByKey($this->key);
        $this->subject = $template->subject;
        $this->template = file_get_contents($_ENV['TN_ROOT'] . 'view/tpl/' . $template->defaultTemplateFile);
    }

    /**
     * loads up an object from mysql, given its id
     * @param string $key
     * @return CustomTemplate|null
     */
    public static function getFromKey(string $key): ?CustomTemplate
    {
        return static::searchOne(new SearchArguments(new SearchComparison('`key`', '=', $key)));
    }

    /** get the subject to use */
    public function getPreviewSubject(): string|bool
    {
        $data = Template::getInstanceByKey($this->key)->sampleData;
        return $this->evaluateTemplate('string:' . $this->subject, $data);
    }

    /**
     * @return string|bool
     * @throws ValidationException
     */
    public function getPreviewBody(): string|bool
    {
        try {
            $data = Template::getInstanceByKey($this->key)->sampleData;
            $body = $this->evaluateTemplate('string:' . $this->template, $data);
            $engine = TemplateEngine::getInstance();
            $engine->assignData(array_merge($data, ['body' => $body]));
            return $engine->fetch('TN/Model/Email/Email.tpl');
        } catch (\Exception $e) {
            throw new ValidationException($e->getMessage());
        }
    }
}
