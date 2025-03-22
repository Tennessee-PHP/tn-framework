<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\Constraints\Constraint;
use TN\TN_Core\Attribute\Optional;
use TN\TN_Core\Attribute\Readable;
use TN\TN_Core\Error\ValidationException;

/**
 * validates the object's current values
 *
 */
trait Validation
{
    /**
     * persist the message (may return array of errors in case of a validation error)
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    public function update(array $data): void
    {
        foreach ($data as $prop => $value) {
            if (property_exists($this, $prop)) {
                $this->$prop = $value;
            }
        }

        $this->validate();

        if (!isset($this->id)) {
            $this->absentIdBeforeSave();
        }

        $this->save(array_keys($data));
    }

    /**
     * class specific, complex validations not covered by the standard validation attributes
     * @throws ValidationException
     */
    protected function customValidate(): void {}

    /**
     * validate
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];
        $class = new \ReflectionClass(static::class);

        foreach ($class->getProperties() as $property) {
            // first let's get the name of the property
            $propertyName = $property->getName();
            $readable = $propertyName;
            $readableAttributes = $property->getAttributes(Readable::class);
            if (count($readableAttributes) > 0) {
                $readableInstance = $readableAttributes[0]->newInstance();
                $readable = $readableInstance->readable;
            }

            $optionalAttributes = $property->getAttributes(Optional::class);
            if (count($optionalAttributes) > 0 && (empty($this->$propertyName))) {
                continue;
            }

            $constraintAttributes = $property->getAttributes(Constraint::class, 2);
            foreach ($constraintAttributes as $constraint) {
                $constraintInstance = $constraint->newInstance();
                $constraintInstance->linkToClass($readable, __CLASS__);
                $constraintInstance->validate($this->$propertyName ?? null);
                if (!$constraintInstance->valid) {
                    $errors[] = '(' . $property->getName() . '): ' . $constraintInstance->error;
                }
            }
        }

        try {
            $this->customValidate();
        } catch (ValidationException $e) {
            $errors = array_merge($errors, $e->errors);
        }

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }
}
