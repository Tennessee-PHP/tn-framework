<?php

namespace TN\TN_Billing\Component\GooglePlay\Api;

use TN\TN_Billing\Model\Provider\GooglePlay\RequestLog;
use TN\TN_Billing\Model\Provider\GooglePlay\Subscription as GPSub;
use TN\TN_Core\Component\Renderer\Text\Text;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;

class Subscription extends Text
{
    public function prepare(): void
    {
        $log = RequestLog::getInstance();
        $log->update([
            'startTs' => Time::getNow()
        ]);
        try {
            // decode the data sent!
            $localTesting = false;
            $json = json_decode(file_get_contents('php://input'), true);
            $encodedData = $json['message']['data'];
            $decodedData = base64_decode($encodedData);
            $data = json_decode($decodedData, true);
            // save this to the log
            $log->update([
                'request' => json_encode($data)
            ]);
            $subscriptionNotification = $data['subscriptionNotification'];
            if (!is_array($subscriptionNotification)) {
                $log->update([
                    'result' => 'no subscriptionNotification',
                    'success' => true,
                    'completed' => true
                ]);
                return;
            }
            $purchaseToken = $subscriptionNotification['purchaseToken'];
            $productId = $subscriptionNotification['subscriptionId'];
            $packageName = $data['packageName'] ?? '';


            $gpSub = GPSub::getFromPurchaseToken($purchaseToken);
            $gpSub->update([
                'productId' => $productId,
                'packageName' => $packageName
            ]);

            $gpSub->updateTnSubscription();

            $log->update([
                'result' => 'got through past updateTnSubscription',
                'success' => true,
                'completed' => true
            ]);
        } catch (\Exception $e) {
            $log->update([
                'result' => $e->getMessage(),
                'success' => false,
                'completed' => true
            ]);
            throw new ValidationException($e->getMessage());
        }
    }
}
