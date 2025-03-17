<?php

namespace TN\TN_Core\Model\PersistentModel;

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
    }

    abstract protected function eraseStorage(): void;
    public function erase(): void
    {
        $this->beforeErase();
        $this->eraseStorage();
        $this->afterErase();
        $this->invalidateCache();
    }

    abstract protected static function batchSaveInsertStorage(array $objects, bool $useSetId = false): void;

    public static function batchSaveInsert(array $objects, bool $useSetId = false): void
    {
        static::batchSaveInsertStorage($objects, $useSetId);
        foreach ($objects as $object) {
            $object->afterSaveInsert();
            $object->invalidateCache();
        }
    }

    abstract protected static function batchEraseStorage(array $objects): void;
    public static function batchErase(array $objects): void
    {
        foreach ($objects as $object) {
            $object->beforeErase();
        }
        static::batchEraseStorage($objects);
        foreach ($objects as $object) {
            $object->afterErase();
            $object->invalidateCache();
        }
    }
}
