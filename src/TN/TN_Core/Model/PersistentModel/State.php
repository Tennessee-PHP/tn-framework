<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\NoCacheInvalidation;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Error\ValidationException;

/**
 * concerning the state of persistent models
 */
trait State
{
    /** @var array Properties with NoCacheInvalidation that were updated */
    #[Impersistent]
    private array $pendingNoCacheUpdates = [];

    public function beforeErase(): void {}
    public function afterErase(): void {}
    protected function beforeSave(array $changedProperties): array
    {
        return [];
    }
    protected function afterSaveUpdate(array $changedProperties): void {}
    protected function afterSaveInsert(): void {}

    public function absentIdBeforeSave(): void {}

    /**
     * Get properties marked with NoCacheInvalidation attribute from a list of changed properties
     * 
     * @param array $changedProperties List of property names that changed
     * @return array Properties that have NoCacheInvalidation attribute
     */
    private function getNoCacheInvalidationProperties(array $changedProperties): array
    {
        $class = new \ReflectionClass(static::class);
        $noCacheProperties = [];
        
        foreach ($changedProperties as $propertyName) {
            if ($class->hasProperty($propertyName)) {
                $property = $class->getProperty($propertyName);
                $noCacheAttributes = $property->getAttributes(NoCacheInvalidation::class);
                
                // Include property if it has NoCacheInvalidation attribute
                if (!empty($noCacheAttributes)) {
                    $noCacheProperties[] = $propertyName;
                }
            }
        }
        
        return $noCacheProperties;
    }

    /**
     * Process changed properties to separate those that should and shouldn't trigger cache invalidation
     * 
     * @param array $changedProperties Properties that changed
     * @return array Properties that should trigger cache invalidation (excludes NoCacheInvalidation properties)
     */
    private function processNoCacheInvalidationProperties(array $changedProperties): array
    {
        // Separate properties that should and shouldn't trigger cache invalidation
        $noCacheProperties = $this->getNoCacheInvalidationProperties($changedProperties);
        $normalProperties = array_diff($changedProperties, $noCacheProperties);
        
        // Store no-cache properties for later processing
        $this->pendingNoCacheUpdates = $noCacheProperties;
        
        // Only return properties that should trigger cache invalidation
        return $normalProperties;
    }

    /**
     * Update individual object cache for NoCacheInvalidation properties without invalidating searches/counts
     */
    private function updateNoCacheProperties(): void
    {
        // Update individual object cache for no-cache properties without invalidating searches/counts
        if (!empty($this->pendingNoCacheUpdates) && static::cacheEnabled()) {
            static::objectSetCache($this->id, $this);
        }
        
        // Clear the pending updates
        $this->pendingNoCacheUpdates = [];
    }

    abstract protected function saveStorage(array $changedProperties = []): SaveType;
    public function save(array $changedProperties = []): void
    {
        try {
            $changedProperties = array_merge($changedProperties, $this->beforeSave($changedProperties));
            
            // Process NoCacheInvalidation properties
            $cacheInvalidatingProperties = $this->processNoCacheInvalidationProperties($changedProperties);
            
            $saveType = $this->saveStorage($changedProperties);
            if ($saveType === SaveType::Update) {
                $this->afterSaveUpdate($cacheInvalidatingProperties);
                
                // Only invalidate cache for properties that should trigger cache invalidation
                if (count($cacheInvalidatingProperties) > 0) {
                    $this->invalidateCache();
                }
                
                // Update individual object cache for NoCacheInvalidation properties
                $this->updateNoCacheProperties();
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
            } catch (ValidationException $e) {
                // Skip this object on validation exceptions
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
