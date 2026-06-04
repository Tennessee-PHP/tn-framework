<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

/**
 * Synchronous Kit v3 form subscribe used by Insider API (fields + optional tags).
 */
class KitV3FormSubscribe
{
    /**
     * @param int $formId
     * @param string $email
     * @param array<string, string> $fields
     * @param int[] $tagIds
     */
    public static function subscribe(int $formId, string $email, array $fields, array $tagIds): bool
    {
        $api = new \ConvertKit_API\ConvertKit_API($_ENV['CONVERTKIT_KEY'], $_ENV['CONVERTKIT_SECRET']);
        $payload = ['email' => $email];
        if ($fields !== []) {
            $payload['fields'] = $fields;
        }

        $result = $api->form_subscribe($formId, $payload);
        if ($result === false) {
            return false;
        }

        foreach ($tagIds as $tagId) {
            if ($tagId < 1) {
                continue;
            }
            $tagResult = $api->add_tag($tagId, ['email' => $email]);
            if ($tagResult === false) {
                return false;
            }
        }

        return true;
    }
}
