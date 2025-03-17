<?php

namespace TN\TN_Reporting\Model\Analytics\EmailList;
use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_email_list_active_entries')]
class EmailListConvertKitActiveEntry extends EmailListConvertKitTypeEntry
{
    public static string $type = 'active';

    // or cancellations, new_subscribers
    public static string $apiProperty = 'subscribers';
}