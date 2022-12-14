<?php

namespace SilverCommerce\OrdersAdmin\Tasks;

use StatusChangeRule;
use SilverStripe\Control\Director;
use SilverStripe\Dev\MigrationTask;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Notifications\InvoiceVendorEmail;
use SilverCommerce\OrdersAdmin\Notifications\InvoiceCustomerEmail;
use SilverCommerce\OrdersAdmin\Model\Notification as LegacyNotification;
use SilverCommerce\OrdersAdmin\Notifications\InvoiceNotification;

class NotificationMigrationTask extends MigrationTask
{
    private static $run_during_dev_build = true;

    private static $segment = 'NotificationMigrationTask';

    protected $description = "Migrate order notifications";

    public function run($request)
    {
        if ($request->getVar('direction') == 'down') {
            $this->down();
        } else {
            $this->up();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $migrated = 0;

        if (class_exists(Subsite::class)) {
            $notifications = Subsite::get_from_all_subsites(LegacyNotification::class);
        } else {
            $notifications = LegacyNotification::get();
        }

        $total = $notifications->count();

        $this->log("- Migrating: {$migrated}/{$total} notifications", true);

        /** @var LegacyNotification $notification */
        foreach ($notifications as $notification) {
            $customer = false;
            $vendor = false;

            /** @var InvoiceNotification */
            $new = InvoiceNotification::create();
            $new->BaseClassName = Invoice::class;
            $new->write();

            $rule = StatusChangeRule::create();
            $rule->FieldName = 'Status';
            $rule->Value = $notification->Status;
            $rule->NotificationID = $new->ID;
            $rule->write();

            switch ($notification->SendNotificationTo) {
                case 'Customer':
                    $customer = true;
                    break;
                case 'Vendor':
                    $vendor = true;
                    break;
                case 'Both':
                    $customer = true;
                    $vendor = true;
                    break;
            }

            if ($customer == true) {
                $cus_type = InvoiceCustomerEmail::create();
                $cus_type->AltRecipient = 'Customer.Email';
                $cus_type->Subject = $notification->CustomSubject;
                $cus_type->From = $notification->FromEmail;
                $cus_type->NotificationID = $new->ID;
                $cus_type->write();
            }

            if ($vendor == true) {
                $ven_type = InvoiceVendorEmail::create();

                if (!empty($notification->VendorEmail)) {
                    $ven_type->Recipient = $notification->VendorEmail;
                }

                $ven_type->Subject = $notification->CustomSubject;
                $ven_type->From = $notification->FromEmail;
                $ven_type->NotificationID = $new->ID;
                $ven_type->write();
            }

            $notification->delete();
            $migrated++;

            $this->log("- Migrating: {$migrated}/{$total} notifications", true);
        }

        $this->log("- Migrating: {$migrated}/{$total} notifications", false);
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->log('Downgrade Not Possible');
    }

    /**
     * Log a message to the terminal/browser
     * 
     * @param string $message   Message to log
     * @param bool   $linestart Set cursor to start of line (instead of return)
     * 
     * @return null
     */
    protected function log($message, $linestart = false)
    {
        if (Director::is_cli()) {
            $end = ($linestart) ? "\r" : "\n";
            print_r($message . $end);
        } else {
            print_r($message . "<br/>");
        }
    }
}
