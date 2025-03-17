<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

/**
 * a request that either has been, or needs to be made to convertkit
 * 
 */
#[TableName('convertkit_requests')]
class Request implements Persistence
{
    use MySQL;
    use PersistentModel;

    public string $action = '';
    public string $serializedArguments = '';
    public int $originTs = 0;
    public bool $attempted = false;
    public bool $completed = false;
    public int $requestTs = 0;
    public string $result = '';

    /** @return Request|null gets the next request to make */
    public static function getNextRequest(): ?Request
    {
        return static::searchOne(new SearchArguments(
            new SearchComparison('`attempted`', '=', 0),
            new SearchSorter('originTs', 'ASC')
        ));
    }

    /** @return bool actually make the request
     * @throws ValidationException
     */
    public function request(): bool
    {
        $this->update([
            'requestTs' => Time::getNow(),
            'attempted' => true
        ]);
        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        $action = $this->action;

        if ($this->action === 'add_subscriber_to_sequence') {
            $args = unserialize($this->serializedArguments);
            $result = $api->$action($args[0], $args[1]['email']);
        } else {
            $result = $api->$action(...unserialize($this->serializedArguments));
        }
        $this->update([
            'completed' => $result !== false,
            'result' => serialize($result)
        ]);
        return true;
    }
}