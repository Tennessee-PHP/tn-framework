<?php

namespace TN\TN_Core\Component;

use Error;
use TN\TN_Core\Attribute\Components\From;
use TN\TN_Core\Attribute\Components\FromActiveUser;
use TN\TN_Core\Attribute\Components\FromJSONBody;
use TN\TN_Core\Attribute\Components\FromPath;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\FromRequest;
use TN\TN_Core\Error\JSONParseException;
use TN\TN_Core\Model\PersistentModel\ReadOnlyProperties;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\User;

/**
 * A component to display on a page (or AJAX request!)
 *
 * This could be a select menu, e.g. for selecting the number of years, that is passed through as a variable to a
 * template that then renders it
 *
 */
abstract class Component
{
    use ReadOnlyProperties;

    /**
     * @throws JSONParseException
     */
    public function __construct(array $options = [], array $pathArguments = [])
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $reflection = new \ReflectionClass($this);
        $request = HTTPRequest::get();

        foreach ($reflection->getProperties() as $propertyReflection) {
            $attributes = $propertyReflection->getAttributes(From::class, \ReflectionAttribute::IS_INSTANCEOF);
            if (empty($attributes)) {
                continue;
            }
            $propertyName = $propertyReflection->getName();
            $type = (string)$propertyReflection->getType();

            foreach ($attributes as $attribute) {
                $attribute = $attribute->newInstance();
                // if the attribute is FromPath, look in $pathArguments for its value
                if ($attribute instanceof FromPath) {
                    if (array_key_exists($propertyName, $pathArguments)) {
                        $this->$propertyName = $pathArguments[$propertyName];
                    }
                }
                $tmp = null;
                if ($attribute instanceof FromQuery) {
                    $tmp = $request->getQuery($propertyName);
                }
                if ($attribute instanceof FromPost) {
                    $tmp = $request->getPost($propertyName);
                }
                if ($attribute instanceof FromRequest) {
                    $tmp = $request->getRequest($propertyName);
                }
                if ($attribute instanceof FromJSONBody) {
                    $jsonBody = $request->getJSONRequestBody();
                    if ($jsonBody !== null && isset($jsonBody[$propertyName])) {
                        $tmp = $jsonBody[$propertyName];
                    } else {
                        $tmp = null;
                    }
                }

                if ($tmp !== null) {
                    try {
                        if ($type === 'bool') {
                            if ($tmp === 'false') {
                                $tmp = false;
                            }
                            $this->$propertyName = (bool)$tmp;
                        } elseif ($type === 'array') {
                            // Handle both JSON arrays and comma-separated strings
                            if (is_array($tmp)) {
                                // Already an array from JSON
                                $this->$propertyName = $tmp;
                            } elseif (is_string($tmp) && !empty($tmp)) {
                                // Split comma-separated strings
                                $this->$propertyName = array_filter(array_map('trim', explode(',', $tmp)), function ($value) {
                                    return !empty($value);
                                });
                            } else {
                                $this->$propertyName = [];
                            }
                        } else {
                            $this->$propertyName = $tmp;
                        }
                    } catch (\Exception | Error $e) {
                        // do nothing
                    }
                }

                if ($attribute instanceof FromActiveUser) {
                    $this->$propertyName = User::getActive();
                }
            }
        }

        foreach ($pathArguments as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }
            $this->$key = $value;
        }
    }

    /**
     * overwrite this method to do any code that is required to render the component
     * @return void
     */
    public function prepare(): void {}

    /**
     * all components must have a way to render themselves to a string
     * @return string
     */
    abstract public function render(): string;
}
