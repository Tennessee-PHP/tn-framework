<?php

namespace TN\TN_Reporting\Model\Analytics\EmailList;

use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_email_list_cancellations_entries')]
class EmailListConvertKitCancellationsEntry extends EmailListConvertKitTypeEntry
{
    public static string $type = 'cancellations';

    // or cancellations, new_subscribers
    public static string $apiProperty = 'cancellations';
}