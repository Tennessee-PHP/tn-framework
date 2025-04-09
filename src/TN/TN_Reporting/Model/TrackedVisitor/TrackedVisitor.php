<?php

namespace TN\TN_Reporting\Model\TrackedVisitor;

use Ramsey\Uuid\Uuid as RamseyUuid;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Storage\Redis as RedisDB;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Campaign\Campaign;
use TN\TN_Reporting\Model\Funnel\Funnel;

/**
 * let's track a visitor to utm parameters and a campaign through funnels
 *
 */
#[TableName('tracked_visitors')]
class TrackedVisitor implements Persistence
{
    use MySQL;
    use PersistentModel;
    use TrackedVisitor_ABTest;
    use MySQLPrune;

    protected static int $lifespan = Time::ONE_MONTH;
    protected static string $tsProp = 'firstVisitTs';

    const int SESSION_EXPIRES = (86400 * 28);

    public string $uuId;
    public string $ip;
    public int $firstVisitTs = 0;
    public int $lastVisitTs = 0;
    public string $referrerUrl = '';
    public string $afterPurchaseUrl = '';
    public int $campaignId = 0;
    public string $convertKitTag = '';
    public string $utmSource = '';
    public string $utmMedium = '';
    public string $utmContent = '';
    public string $utmTerm = '';

    protected static TrackedVisitor $instance;

    /**
     * @return TrackedVisitor factory method
     * @throws ValidationException
     */
    public static function get(): TrackedVisitor
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        $request = HTTPRequest::get();

        // if we have the cookie and we still have the data
        $trackedVisitor = false;
        $tnTvIdCookie = $request->getCookie('TN_tvid');
        if (isset($tnTvIdCookie) && !empty($tnTvIdCookie)) {
            $trackedVisitors = TrackedVisitor::searchByProperty('uuId', $tnTvIdCookie);
            if (count($trackedVisitors)) {
                $trackedVisitor = $trackedVisitors[0];
                $trackedVisitor->update([
                    'uuId' => $tnTvIdCookie,
                    'ip' => IP::getAddress(),
                    'lastVisitTs' => Time::getNow()
                ]);
            }
        }

        if (!$trackedVisitor) {
            $className = Stack::resolveClassName(__CLASS__);
            $trackedVisitor = new $className;
            $trackedVisitor->update([
                'uuId' => (string)RamseyUuid::uuid4(),
                'ip' => IP::getAddress(),
                'firstVisitTs' => Time::getNow(),
                'lastVisitTs' => Time::getNow(),
                'utmSource' => $request->getQuery('utm_source', ''),
                'utmMedium' => $request->getQuery('utm_medium', ''),
                'utmContent' => $request->getQuery('utm_content', ''),
                'utmTerm' => $request->getQuery('utm_term', ''),
                'referrerUrl' => $request->getServer('HTTP_REFERER', '')
            ]);
        }

        $afterPurchaseUrlQuery = $request->getQuery('afterpurchaseurl');
        if ($trackedVisitor && !empty($afterPurchaseUrlQuery) && str_contains($afterPurchaseUrlQuery, $_ENV['BASE_URL'])) {
            $trackedVisitor->update([
                'afterPurchaseUrl' => $afterPurchaseUrlQuery
            ]);
        }

        $trackedVisitor->setCookie();

        // set the static singleton
        self::$instance = $trackedVisitor;

        return $trackedVisitor;
    }

    /** set the constructor to protected */
    protected function __construct() {}

    /**
     * gets all the funnels that this route appears in
     * @param string $routeCls
     * @return array
     */
    protected function getRouteFunnelStages(string $routeCls): array
    {
        $funnelStages = [];
        foreach (Funnel::getInstances() as $funnel) {
            $stage = $funnel->getRouteStage($routeCls);
            if ($stage !== false) {
                $funnelStages[$funnel->key] = $stage;
            }
        }
        return $funnelStages;
    }

    /** sets cookie */
    protected function setCookie(): void
    {
        // unit testing? don't bother!
        if (defined('UNIT_TESTING') && UNIT_TESTING) {
            return;
        }

        // already set to this? don't bother!
        $request = HTTPRequest::get();
        if ($request->getCookie('TN_tvid', null) === $this->uuId) {
            return;
        }

        // set it!
        $request->setCookie('TN_tvid', $this->uuId, [
            'expires' => Time::getNow() + self::SESSION_EXPIRES,
            'secure' => $_ENV['ENV'] === 'development',
            'domain' => $_ENV['COOKIE_DOMAIN'],
            'path' => '/'
        ]);
    }

    /** @return ?Campaign get the associated campaign */
    public function getCampaign(): ?Campaign
    {
        if ($this->campaignId === 0) {
            return null;
        }
        return Campaign::readFromId($this->campaignId) ?? null;
    }

    /**
     * @return VoucherCode|null
     */
    public function getActiveVoucherCode(): ?VoucherCode
    {
        $voucherCode = null;
        $campaign = $this->getCampaign();
        if ($campaign instanceof Campaign) {
            if ($campaign->voucherCodeId > 0) {
                $voucherCode = VoucherCode::readFromId($campaign->voucherCodeId);
                if ($voucherCode instanceof VoucherCode) {
                    $voucherCode = VoucherCode::getActiveFromCode($voucherCode->code);
                }
            }
        }
        return $voucherCode;
    }

    /** @param int $campaignId change this prospect's campaign
     * @throws ValidationException
     */
    public function setCampaign(int $campaignId): void
    {
        $newCampaign = Campaign::readFromId($campaignId);
        if ($this->campaignId === 0 || $newCampaign->voucherCodeId > 0) {
            $this->update([
                'campaignId' => $campaignId
            ]);
            $this->clearRouteData();
        }
    }

    /** @param string $tag change this prospect's convert kit tag
     * @throws ValidationException
     */
    public function setConvertKitTag(string $tag): void
    {
        $this->update([
            'convertKitTag' => $tag
        ]);
    }

    /**
     * gets the redis key
     * @param int $id
     * @return string
     */
    protected static function getKey(int $id): string
    {
        return __CLASS__ . ':' . $id . ':' . date('Y-m-d', Time::getNow());
    }

    /** clears all data */
    protected function clearRouteData(): void
    {
        $client = RedisDB::getInstance();
        $client->del(self::getKey($this->id));
    }

    /**
     * writes redis hash data
     * @param string $hash
     * @param mixed $value
     */
    protected function setHash(string $hash, mixed $value): void
    {
        $client = RedisDB::getInstance();
        $client->hset(self::getKey($this->id), $hash, $value);
        $client->expire(self::getKey($this->id), 86400);
        $this->setCookie();
    }

    /**
     * @param string $hash
     * @return string|null
     */
    protected function getHash(string $hash): ?string
    {
        $client = RedisDB::getInstance();
        return $client->hget(self::getKey($this->id), $hash);
    }

    /**
     * whether the prospect has hit this route already
     * @param string $type
     * @param string $item
     * @return bool
     */
    protected function hasHit(string $type, string $item): bool
    {
        return (int)$this->getHash($type . '-' . $item) === 1;
    }

    /**
     * record that the prospect has hit this route
     * @param string $type
     * @param string $item
     */
    protected function recordHit(string $type, string $item): void
    {
        $this->setHash($type . '-' . $item, 1);
    }

    /** @param string $routeCls record a hit by the current user against a routeCls */
    public function registerRouteHit(string $routeCls): void
    {
        $funnelStages = self::getRouteFunnelStages($routeCls);
        if (empty($funnelStages)) {
            return;
        }
        /*

        $dayReport = DayFunnelReport::getFromTs(Time::getNow());
        $first = !$this->hasHit('route', $routeCls);
        if ($first) {
            $this->recordHit('route', $routeCls);
        }
        foreach ($funnelStages as $funnelKey => $stage) {
            $dayReport->incrementCount('funnels', [$funnelKey, 'hits', $stage], 1);
            if ($first) {
                $dayReport->incrementCount('funnels', [$funnelKey, 'uniques', $stage], 1);
            }
        }

        // total hits/uniques on the funnel
        foreach(array_keys($funnelStages) as $funnelKey) {
            $dayReport->incrementCount('funnels', [$funnelKey, 'total-hits'], 1);
            if (!$this->hasHit('funnel', $funnelKey)) {
                $dayReport->incrementCount('funnels', [$funnelKey, 'total-uniques'], 1);
                $this->recordHit('funnel', $funnelKey);
            }
        }

        $campaign = $this->getCampaign();
        if (!($campaign instanceof Campaign) || !isset($funnelStages[$campaign->funnelKey])) {
            return;
        }

        // stage hits on the campaign
        $stage = $funnelStages[$campaign->funnelKey];
        $dayReport->incrementCount('campaigns', [$campaign->key, 'hits', $stage], 1);
        if ($first) {
            $dayReport->incrementCount('campaigns', [$campaign->key, 'uniques', $stage], 1);
        }

        // total hits/uniques on the campaign
        $dayReport->incrementCount('campaigns', [$campaign->key, 'total-hits'], 1);
        if (!$this->hasHit('campaign', $campaign->key)) {
            $dayReport->incrementCount('campaigns', [$campaign->key, 'total-uniques'], 1);
            $this->recordHit('campaign', $campaign->key);
        }*/
    }
}
