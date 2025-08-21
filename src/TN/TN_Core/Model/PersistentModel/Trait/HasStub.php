<?php

namespace TN\TN_Core\Model\PersistentModel\Trait;

use TN\TN_Core\Attribute\Constraints\Strlen;

/**
 * Trait for automatically generating URL-safe stubs from text properties
 * 
 * This trait provides automatic stub generation for models that need URL-safe identifiers.
 * It hooks into the save process to generate or update stubs based on specified source properties.
 */
trait HasStub
{
    #[Strlen(max: 255)] public string $stub = '';

    /**
     * Get the property name that should be used to generate the stub
     * Override this method in your model to specify which property to use
     * 
     * @return string Property name (e.g., 'name', 'title')
     */
    protected function getStubSourceProperty(): string
    {
        // Default to 'name' - override in your model if different
        return 'name';
    }

    /**
     * Generate a URL-safe stub from the given text
     * 
     * @param string $text Source text to convert to stub
     * @return string URL-safe stub
     */
    protected function generateStub(string $text): string
    {
        // Convert to lowercase
        $stub = strtolower($text);

        // Replace whitespace and underscores with hyphens
        $stub = preg_replace('/[\s_]+/', '-', $stub);

        // Remove everything except letters, numbers, and hyphens
        $stub = preg_replace('/[^a-z0-9\-]/', '', $stub);

        // Remove duplicate hyphens
        $stub = preg_replace('/-+/', '-', $stub);

        // Trim hyphens from start and end
        $stub = trim($stub, '-');

        // Ensure it's not empty
        if (empty($stub)) {
            $stub = 'item-' . time();
        }

        return $stub;
    }

    /**
     * Generate a unique stub that doesn't conflict with existing records
     * 
     * @param string $text Source text to convert to stub
     * @return string Unique URL-safe stub
     */
    protected function generateUniqueStub(string $text): string
    {
        $baseStub = $this->generateStub($text);
        $uniqueStub = $baseStub;
        $counter = 1;

        // Keep trying until we find a unique stub
        while ($this->stubExists($uniqueStub)) {
            $counter++;
            $uniqueStub = $baseStub . '-' . $counter;
        }

        return $uniqueStub;
    }

    /**
     * Check if a stub already exists in the database
     * 
     * @param string $stub Stub to check
     * @return bool True if stub exists in another record
     */
    protected function stubExists(string $stub): bool
    {
        // Search for existing records with this stub
        $existingRecords = static::searchByProperties(['stub' => $stub]);

        // If no records found, stub is available
        if (empty($existingRecords)) {
            return false;
        }

        // If we found records, check if any of them are different from current record
        foreach ($existingRecords as $record) {
            // For new records (no ID yet), any existing record is a conflict
            if (empty($this->id)) {
                return true;
            }

            // For existing records, conflict only if it's a different record
            if ($record->id !== $this->id) {
                return true;
            }
        }

        // No conflicts found
        return false;
    }

    /**
     * Check if stub needs to be generated or updated
     * 
     * @param array $changedProperties Properties that have changed
     * @return bool True if stub should be regenerated
     */
    protected function shouldRegenerateStub(array $changedProperties): bool
    {
        $sourceProperty = $this->getStubSourceProperty();

        // Generate stub if it's empty or if the source property changed
        return empty($this->stub) || isset($changedProperties[$sourceProperty]);
    }

    /**
     * Generate stub before saving if needed
     * Call this method from your model's beforeSave method
     * 
     * @param array $changedProperties Properties that have changed
     * @return array Additional properties to save (including stub if changed)
     */
    protected function generateStubBeforeSave(array $changedProperties): array
    {
        $additionalChanges = [];

        if ($this->shouldRegenerateStub($changedProperties)) {
            $sourceProperty = $this->getStubSourceProperty();

            // Get the source text for stub generation
            $sourceText = $this->$sourceProperty ?? '';

            if (!empty($sourceText)) {
                $newStub = $this->generateUniqueStub($sourceText);

                // Only update if the stub actually changed
                if ($newStub !== $this->stub) {
                    $this->stub = $newStub;
                    $additionalChanges['stub'] = $newStub;
                }
            }
        }

        return $additionalChanges;
    }

    /**
     * Generate stub for new records without explicit changes
     * Call this method from your model's absentIdBeforeSave method
     */
    protected function generateStubForNewRecord(): void
    {
        // For new records, always generate stub if empty
        if (empty($this->stub)) {
            $sourceProperty = $this->getStubSourceProperty();
            $sourceText = $this->$sourceProperty ?? '';

            if (!empty($sourceText)) {
                $this->stub = $this->generateUniqueStub($sourceText);
            }
        }
    }
}
