<?php

namespace TN\TN_Core\CLI\Email\ConvertKit;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Provider\ConvertKit\Request;

class SendFromQueue extends CLI
{
    public function run(): void
    {
        try {
            $sent = 0;
            $failed = 0;
            for ($i = 0; $i < 200; $i += 1) {
                $request = Request::getNextRequest();
                if (!$request) {
                    break;
                }
                $sent++;
                if (!$request->request()) {
                    $failed++;
                }
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