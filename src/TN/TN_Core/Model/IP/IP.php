<?php

namespace TN\TN_Core\Model\IP;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Storage\Redis as RedisDB;

/**
 * class to restrict spamming from a specific IP address
 *
 */
class IP
{
    use \TN\TN_Core\Model\PersistentModel\Storage\Redis;

    /** @var string the IP address used */
    protected string $id;

    protected array $events = [];

    /**
     * private constructor
     * @see self::getInstance()
     */
    private function __construct()
    {

    }

    /**
     * use this e.g. to set an expire key
     */
    protected function afterSave(): void
    {
        $client = RedisDB::getInstance();
        $key = self::getObjectKey($this->id);
        $client->expire($key, 86400);
    }

    public static function getAddress(): string
    {
        $request = HTTPRequest::get();
        return $request->getServer('REMOTE_ADDR', '') . '-' . $request->getServer('HTTP_X_FORWARDED_FOR', '');
    }

    /**
     * gets the instance of an IP limiter
     * @param bool|string $address
     * @return IP
     */
    public static function getInstance(bool|string $address = false): IP
    {
        if ($address === false) {
            $address = self::getAddress();
        }
        $ip = self::readFromId($address);
        if (!($ip instanceof IP)) {
            $ip = new IP();
            $ip->id = $address;
        }
        return $ip;
    }

    /**
     * record an event for this IP address
     * @param string $event
     * @return bool
     */
    public function recordEvent(string $event): bool
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        // am not using Time::getNow() here because redis' expire can't possibly be compatible
        $this->events[$event][] = time();
        return $this->save();
    }

    /**
     * @param string $event
     * @param string $inLastSeconds
     * @return int
     */
    public function eventCount(string $event, string $inLastSeconds): int
    {
        if (!isset($this->events[$event])) {
            return 0;
        }
        $gt = time() - $inLastSeconds;
        $count = 0;
        foreach ($this->events[$event] as $ts) {
            if ($ts > $gt) {
                $count += 1;
            }
        }
        return $count;
    }

    /**
     * should an event be allowed?
     * @param string $event
     * @param int $maxQuantity
     * @param int $inLastSeconds
     * @return bool
     */
    public function eventAllowed(string $event, int $maxQuantity, int $inLastSeconds): bool
    {
        $count = $this->eventCount($event, $inLastSeconds);
        return $count < $maxQuantity;

    }

}