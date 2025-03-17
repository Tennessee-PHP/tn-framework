<?php

namespace TN\TN_Core\Error\PersistentModel;

use TN\TN_Core\Error\TNException;

class SearchException extends TNException {

    public int $httpResponseCode = 500;
    public bool $messageIsUserFacing = false;

    public function __construct(SearchErrorMessage $message, ?string $extra = null)
    {
        $message = $message->value;
        if (!empty($extra)) {
            $message .= " ({$extra})";
        }
        parent::__construct($message);
    }

}