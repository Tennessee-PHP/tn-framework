<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;

/**
 * a class with a collection of traits, to which only a trait like MySQL needs to be added for a final class
 */
trait PersistentModel
{
    #[AutoIncrement] #[PrimaryKey] public int $id = 0;

    use Factory;
    use Cache;
    use Validation;
    use PersistentProperties;
    use Search;
    use State;
}