<?php

namespace TN\TN_Core\CLI\Email\ConvertKit;

use TN\TN_Core\CLI\CLI;
use TN\TN_Core\Model\Provider\ConvertKit\Request;

class SendFromQueue extends CLI
{
    public function run(): void
    {
        try {
            for ($i = 0; $i < 200; $i += 1) {
                $request = Request::getNextRequest();
                if (!$request) {
                    break;
                }
                $request->request();
            }
            $this->green('Sent ' . $i . ' messages from queue');
        } catch (\Exception $e) {
            $this->red($e->getMessage());
        }
    }
}