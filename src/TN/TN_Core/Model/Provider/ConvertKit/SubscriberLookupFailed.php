<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

/**
 * Kit v4 subscriber lookup failed. This is not "no subscriber".
 * The queue row should be tried again later, with no signup and no field write.
 */
class SubscriberLookupFailed extends \RuntimeException
{
}
