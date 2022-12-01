<?php

namespace SilverCommerce\OrdersAdmin\Notifications;

use SilverStripe\Forms\FieldList;
use ilateral\SilverStripe\Notifier\Types\EmailNotification;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;

class InvoiceCustomerEmail extends EmailNotification
{
    private static $table_name = "Notifications_InvoiceCustomerEmail";

    private static $singular_name = 'Invoice Customer Email';

    private static $plural_name = 'Invoice Customer Emails';

    private static $template = self::class;

    private static $alt_recipient_fields = [
        Invoice::class => ['Customer.Email']
    ];

    public function populateDefaults()
    {
        $sender = Config::inst()->get(Email::class, 'admin_email');
        $this->From = $sender;
    }

    private static $defaults = [
        'Recipient' => 'Customer.Email'
    ];

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            
        });

        return parent::getCMSFields();
    }
}
