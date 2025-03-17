<?php

namespace TN\TN_Core\Component\Input\DateInput;

use TN\TN_Core\Component\Component;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Component\Renderer\TemplateRender;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Time\Time;

class DateInput extends HTMLComponent
{
    use ReadOnlyProperties;

    public string $htmlClass = 'tn-component-input-date';
    public string $htmlId = 'TN_Component_Input_Date';
    public string $requestKey = 'date';
    public ?string $default = null;
    public ?string $value = null;


    public function __construct(string $htmlId, string $requestKey, ?string $default = null)
    {
        $this->htmlId = $htmlId;
        $this->requestKey = $requestKey;
        $this->default = $this->parseToStringDate($default ?? date('Y-m-d', Time::getNow()));
        parent::__construct();
    }

    public function prepare(): void
    {
        $request = HTTPRequest::get();
        $value = $request->getRequest($this->requestKey);
        if ($value !== null) {
            $this->value = $this->parseToStringDate($value);
        } else {
            $this->value = $this->default;
        }
    }

    protected function parseToStringDate(string $date): string
    {
        return date('Y-m-d', strtotime($date));
    }
}