<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

use Curl\Curl;
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

    /**
     * Next unattempted row, oldest first.
     * $exceptIds are rows this process already put back after a failed lookup, so the rest of the queue can proceed.
     *
     * @param int[] $exceptIds
     */
    public static function getNextRequest(array $exceptIds = []): ?Request
    {
        $conditions = [
            new SearchComparison('`attempted`', '=', 0),
        ];
        if ($exceptIds !== []) {
            $conditions[] = new SearchComparison('`id`', 'NOT IN', $exceptIds);
        }

        return static::searchOne(new SearchArguments(
            $conditions,
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

        try {
            $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
            $action = $this->action;

            if ($action === 'update_subscriber_fields') {
                $result = $this->requestUpdateSubscriberFields($api);
            } elseif ($action === 'update_subscriber_by_id') {
                $result = $this->requestUpdateSubscriberById($api);
            } elseif ($action === 'add_subscriber_to_sequence') {
                $args = unserialize($this->serializedArguments);
                $result = $api->$action($args[0], $args[1]['email']);
            } else {
                $result = $api->$action(...unserialize($this->serializedArguments));
            }
        } catch (SubscriberLookupFailed $e) {
            $this->update([
                'attempted' => false,
                'completed' => false,
                'requestTs' => 0,
                'result' => serialize([
                    'lookup_failed' => true,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ])
            ]);
            return false;
        } catch (\Throwable $e) {
            if ($this->isRateLimitError($e)) {
                $this->update([
                    'attempted' => false,
                    'completed' => false,
                    'requestTs' => 0,
                    'result' => serialize([
                        'rate_limited' => true,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ])
                ]);
                return false;
            }

            $this->update([
                'completed' => false,
                'result' => serialize([
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ])
            ]);
            return false;
        }

        $this->update([
            'completed' => $result !== false,
            'result' => serialize($result)
        ]);
        return $result !== false;
    }

    /**
     * @param array<string, string> $fields
     */
    protected function requestUpdateSubscriberById(\ConvertKit_API\ConvertKit_API $api): mixed
    {
        [$subscriberId, $fields] = unserialize($this->serializedArguments);
        return $api->make_request('subscribers/' . (int) $subscriberId, 'PUT', [
            'api_secret' => $_ENV['CONVERTKIT_SECRET'],
            'fields' => $fields
        ]);
    }

    /**
     * @param array<string, string> $fields
     */
    protected function requestUpdateSubscriberFields(\ConvertKit_API\ConvertKit_API $api): mixed
    {
        [$email, $fields] = unserialize($this->serializedArguments);
        $subscriber = $this->findKitSubscriber($email);

        if ($subscriber === null) {
            $api->form_subscribe(Queue::USERS_FORM_ID, ['email' => $email]);
            $subscriberId = $api->get_subscriber_id($email);
        } elseif (($subscriber->state ?? '') !== 'active') {
            return ['skipped' => (string) ($subscriber->state ?? '')];
        } else {
            $subscriberId = (int) ($subscriber->id ?? 0);
            if ($subscriberId <= 0) {
                throw new SubscriberLookupFailed('Kit returned an active subscriber without an id');
            }
        }

        if ($subscriberId === false || $subscriberId === 0) {
            return false;
        }

        return $api->make_request('subscribers/' . $subscriberId, 'PUT', [
            'api_secret' => $_ENV['CONVERTKIT_SECRET'],
            'fields' => $fields
        ]);
    }

    /**
     * Kit v4 list, including cancelled subscribers. Null means this email has never been on the account.
     * A failed call throws. It is not the same as an empty list.
     *
     * @throws SubscriberLookupFailed
     */
    protected function findKitSubscriber(string $email): ?object
    {
        $apiKey = $_ENV['CONVERTKIT_V4_KEY'] ?? '';
        if ($apiKey === '') {
            throw new SubscriberLookupFailed('Kit v4 key is not set');
        }

        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_TIMEOUT, 20);
        $curl->setHeader('X-Kit-Api-Key', $apiKey);
        $curl->setHeader('Accept', 'application/json');

        try {
            $curl->get('https://api.kit.com/v4/subscribers', [
                'email_address' => $email,
                'status' => 'all',
                'slim' => 'true',
            ]);
        } catch (\Throwable $e) {
            throw new SubscriberLookupFailed($e->getMessage(), (int) $e->getCode(), $e);
        }

        $statusCode = (int) $curl->http_status_code;
        if ($curl->error || $statusCode >= 400 || $statusCode === 0) {
            $message = is_string($curl->error_message) && $curl->error_message !== ''
                ? $curl->error_message
                : 'Kit subscriber lookup failed';
            throw new SubscriberLookupFailed($message, $statusCode);
        }

        $payload = json_decode((string) $curl->response);
        if (!is_object($payload) || !isset($payload->subscribers) || !is_array($payload->subscribers)) {
            throw new SubscriberLookupFailed('Kit subscriber lookup did not return a subscriber list');
        }

        if ($payload->subscribers === []) {
            return null;
        }

        $subscriber = $payload->subscribers[0];
        if (!is_object($subscriber)) {
            throw new SubscriberLookupFailed('Kit subscriber lookup did not return a subscriber list');
        }

        return $subscriber;
    }

    public function failedDueToRateLimit(): bool
    {
        if ($this->completed || $this->attempted) {
            return false;
        }

        $result = @unserialize($this->result);
        return is_array($result) && !empty($result['rate_limited']);
    }

    public function failedDueToLookup(): bool
    {
        if ($this->completed || $this->attempted) {
            return false;
        }

        $result = @unserialize($this->result);
        return is_array($result) && !empty($result['lookup_failed']);
    }

    protected function isRateLimitError(\Throwable $e): bool
    {
        if ($e->getCode() === 429) {
            return true;
        }

        return str_contains($e->getMessage(), '429 Too Many Requests');
    }
}
