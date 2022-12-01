<?php

namespace SilverCommerce\OrdersAdmin\Notifications;

use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Email\Email;
use ilateral\SilverStripe\Notifier\Types\EmailNotification;

class InvoiceVendorEmail extends EmailNotification
{
    private static $table_name = "Notifications_InvoiceVendorEmail";

    private static $singular_name = 'Invoice Vendor Email';

    private static $plural_name = 'Invoice Vendor Email';

    private static $template = self::class;

    public function populateDefaults()
    {
        $sender = Config::inst()->get(Email::class, 'admin_email');
        $this->From = $sender;
    }
}
