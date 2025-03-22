<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;

/**
 * Base trait providing persistence functionality for models in the TN Framework.
 * 
 * This trait provides core persistence features but requires a storage implementation
 * to be mixed in. Typically this is done by using the MySQL trait:
 *
 * ```php
 * class User implements \TN\TN_Core\Interface\Persistence {
 *     use PersistentModel;
 *     use MySQL;
 * }
 * ```
 *
 * Features included:
 * - Factory methods for object creation
 * - Caching support
 * - Validation framework
 * - Property tracking
 * - Search capabilities
 * - State management
 *
 * @see \TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL For MySQL storage implementation
 */
trait PersistentModel
{
    #[AutoIncrement] #[PrimaryKey] public int $id;

    use Factory;
    use Cache;
    use Validation;
    use PersistentProperties;
    use Search;
    use State;
}
