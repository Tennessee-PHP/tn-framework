<?php

namespace TN\TN_Core\Trait\IdStrategy;
use Ramsey\Uuid\Uuid as RamseyUuid;

/**
 * handles updating a validatable object
 * 
 */
trait Uuid
{
    /**
     * @see \TN\TN_Core\Interface\Persistence::absentIdBeforeSave()
     */
    public function absentIdBeforeSave()
    {
        $this->id = (string)RamseyUuid::uuid4();
    }
}