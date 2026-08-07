<?php

namespace TN\TN_Core\Model\User;

use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\MySQL\Index;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Time\Time;

/**
 * One row per personal API key auth attempt. DB-only; pruned after 30 days.
 */
#[TableName('user_api_key_request_logs')]
class UserApiKeyRequestLog implements Persistence
{
    use MySQL;
    use PersistentModel;
    use MySQLPrune;

    protected static int $lifespan = 30 * Time::ONE_DAY;
    protected static string $tsProp = 'ts';

    public const AUTH_OK = 'ok';
    public const AUTH_EXPIRED = 'expired';
    public const AUTH_REVOKED = 'revoked';
    public const AUTH_INVALID = 'invalid';
    public const AUTH_RATE_LIMITED = 'rate_limited';

    private const USER_AGENT_MAX = 255;

    #[Index('idx_user_api_key_request_logs_ts')]
    public int $ts = 0;

    #[Index('idx_user_api_key_request_logs_key')]
    public ?int $userApiKeyId = null;

    public ?int $userId = null;

    #[Strlen(0, 32)]
    public string $prefix = '';

    #[Strlen(0, 16)]
    public string $method = '';

    #[Strlen(0, 500)]
    public string $path = '';

    public int $statusCode = 0;

    public int $durationMs = 0;

    #[Strlen(0, 64)]
    public string $ip = '';

    #[Strlen(0, 255)]
    public string $userAgent = '';

    #[Strlen(0, 32)]
    public string $authResult = '';

    /**
     * @param array{
     *   userApiKeyId?: ?int,
     *   userId?: ?int,
     *   prefix?: string,
     *   method?: string,
     *   path?: string,
     *   statusCode?: int,
     *   durationMs?: int,
     *   ip?: string,
     *   userAgent?: string,
     *   authResult?: string
     * } $data
     */
    public static function write(array $data): void
    {
        $log = self::getInstance();
        $log->ts = Time::getNow();
        $log->userApiKeyId = isset($data['userApiKeyId']) ? $data['userApiKeyId'] : null;
        $log->userId = isset($data['userId']) ? $data['userId'] : null;
        $log->prefix = (string) ($data['prefix'] ?? '');
        $log->method = (string) ($data['method'] ?? '');
        $log->path = substr((string) ($data['path'] ?? ''), 0, 500);
        $log->statusCode = (int) ($data['statusCode'] ?? 0);
        $log->durationMs = (int) ($data['durationMs'] ?? 0);
        $log->ip = substr((string) ($data['ip'] ?? ''), 0, 64);
        $ua = (string) ($data['userAgent'] ?? '');
        $log->userAgent = strlen($ua) > self::USER_AGENT_MAX
            ? substr($ua, 0, self::USER_AGENT_MAX)
            : $ua;
        $log->authResult = (string) ($data['authResult'] ?? '');
        $log->save();
    }
}
