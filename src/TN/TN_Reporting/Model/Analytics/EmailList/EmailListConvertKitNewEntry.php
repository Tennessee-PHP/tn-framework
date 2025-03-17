<?php

namespace TN\TN_Reporting\Model\Analytics\EmailList;
use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_email_list_new_entries')]
class EmailListConvertKitNewEntry extends EmailListConvertKitTypeEntry
{
    public static string $type = 'new';

    // or cancellations, new_subscribers
    public static string $apiProperty = 'new_subscribers';
}