<?php

namespace TN\TN_Core\Component\Input\Select;

use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\Request\HTTPRequest;

abstract class Select extends HTMLComponent
{
    use ReadOnlyProperties;

    /** @var string the key used to get the selected value from the request */
    public string $requestKey;

    /** @var Option[] the options for the select */
    public array $options;

    /** @var Option|null the selected item (if any) */
    public mixed $selected = null;

    /** @var bool  */
    public bool $multi = false;

    public string $template = 'TN_Core/Component/Input/Select/Select.tpl';

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $this->options = $this->getOptions();
        $request = HTTPRequest::get();
        $value = $request->getRequest($this->requestKey);
        if ($value !== null) {
            foreach ($this->options as $option) {

                if ($value == $option->key) {
                    $this->selected = $option;
                }
            }
        }
        if (!$this->selected) {
            $this->selected = $this->getDefaultOption();
        }
    }

    /**
     * @return Option[] the options for this select
     */
    abstract protected function getOptions(): array;

    /**
     * @return Option|null the default option
     */
    protected function getDefaultOption(): Option|null
    {
        return !empty($this->options) ? $this->options[0] : null;
    }

}