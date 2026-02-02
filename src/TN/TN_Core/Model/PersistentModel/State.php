<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Error\ValidationException;

/**
 * concerning the state of persistent models
 */
trait State
{
    public function beforeErase(): void {}
    public function afterErase(): void {}
    protected function beforeSave(array $changedProperties): array
    {
        return [];
    }
    protected function afterSaveUpdate(array $changedProperties): void {}
    protected function afterSaveInsert(): void {}

    public function absentIdBeforeSave(): void {}

    abstract protected function saveStorage(array $changedProperties = []): SaveType;
    public function save(array $changedProperties = []): void
    {
        try {
            $changedProperties = array_merge($changedProperties, $this->beforeSave($changedProperties));
            if ($this->saveStorage($changedProperties) === SaveType::Update) {
                $this->afterSaveUpdate($changedProperties);
                if (count($changedProperties) > 0) {
                    $this->invalidateCache();
                }
            } else {
                $this->afterSaveInsert();
                $this->invalidateCache();
            }
        } catch (ValidationException $e) {
            // Silent return on validation exceptions
            return;
        }
    }

    abstract protected function eraseStorage(): void;
    public function erase(): void
    {
        try {
            $this->beforeErase();
            $this->eraseStorage();
            $this->afterErase();
            $this->invalidateCache();
        } catch (ValidationException $e) {
            // Silent return on validation exceptions
            return;
        }
    }

    abstract protected static function batchSaveInsertStorage(array $objects, bool $useSetId = false): void;

    public static function batchSaveInsert(array $objects, bool $useSetId = false): void
    {
        // Filter out objects that fail validation
        $validObjects = [];
        foreach ($objects as $object) {
            try {
                // Call beforeSave to trigger validation
                $object->beforeSave([]);
                $validObjects[] = $object;
            } catch (ValidationException) {
                continue;
            }
        }

        if (empty($validObjects)) {
            return;
        }

        static::batchSaveInsertStorage($validObjects, $useSetId);
        foreach ($validObjects as $object) {
            $object->afterSaveInsert();
            $object->invalidateCache();
        }
    }

    abstract protected static function batchEraseStorage(array $objects): void;
    public static function batchErase(array $objects): void
    {
        foreach ($objects as $object) {
            try {
                $object->beforeErase();
            } catch (ValidationException $e) {
                // Skip this object on validation exceptions
                continue;
            }
        }
        static::batchEraseStorage($objects);
        foreach ($objects as $object) {
            $object->afterErase();
            $object->invalidateCache();
        }
    }
}
