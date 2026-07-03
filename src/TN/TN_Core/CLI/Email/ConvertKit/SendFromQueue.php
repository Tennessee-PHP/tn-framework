<?php

namespace TN\TN_Core\CLI\Email\ConvertKit;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Provider\ConvertKit\Request;

class SendFromQueue extends CLI
{
    /** Kit API key limit: 120 requests per rolling 60s window. */
    private const KIT_API_REQUESTS_PER_MINUTE = 118;

    /** Field updates typically perform a lookup plus a PUT. */
    private const ESTIMATED_API_CALLS_PER_ITEM = 2;

    /** Leave headroom before the next scheduled cron invocation. */
    private const MAX_RUN_SECONDS = 240;

    public function run(): void
    {
        try {
            $sent = 0;
            $failed = 0;
            $maxItemsPerMinute = (int) floor(
                self::KIT_API_REQUESTS_PER_MINUTE / self::ESTIMATED_API_CALLS_PER_ITEM
            );
            $delayMicroseconds = (int) floor(60_000_000 / max(1, $maxItemsPerMinute));
            $deadline = time() + self::MAX_RUN_SECONDS;

            while (time() < $deadline) {
                $request = Request::getNextRequest();
                if (!$request) {
                    break;
                }

                $sent++;
                if (!$request->request()) {
                    if ($request->failedDueToRateLimit()) {
                        $this->yellow('Kit rate limit hit; stopping this run so rows can retry');
                        break;
                    }
                    $failed++;
                }

                usleep($delayMicroseconds);
            }

            $this->green('Processed ' . $sent . ' messages from queue');
            if ($failed > 0) {
                $this->red($failed . ' messages failed; see convertkit_requests.result for details');
            }
        } catch (\Throwable $e) {
            $this->red($e->getMessage());
        }
    }
}
