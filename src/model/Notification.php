<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;

class Notification extends DataObject
{
    private static $table_name = 'OrderNotification';

    /**
     * @config
     */
    private static $db = [
        "Status" => "Varchar",
        "SendNotificationTo" => "Enum('Customer,Vendor,Both','Customer')",
        "CustomSubject" => "Varchar(255)",
        "FromEmail" => "Varchar",
        "VendorEmail" => "Varchar"
    ];
    
    /**
     * @config
     */
    private static $has_one = [
        "Parent" => SiteConfig::class
    ];
    
    /**
     * @config
     */
    private static $summary_fields = [
        "Status",
        "SendNotificationTo",
        "FromEmail",
        "VendorEmail",
        "CustomSubject"
    ];
    
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $status_field = DropdownField::create(
                "Status",
                $this->fieldLabel("Status"),
                Invoice::config()->get("statuses")
            );
            
            $fields->replaceField("Status", $status_field);
            
            $vendor = $fields->dataFieldByName("VendorEmail");
            
            if ($vendor) {
                $vendor->setDescription(_t(
                    "Orders.VendorEmailDescription",
                    "Only needed when notification sent to vendor (or both)"
                ));
            }
            
            $subject = $fields->dataFieldByName("CustomSubject");
            
            if ($subject) {
                $subject->setDescription(_t(
                    "Orders.CustomSubjectDescription",
                    "Overwrite the default subject created in the notification email"
                ));
            }
        });

        return parent::getCMSFields();
    }
    
    /**
     * Deal with sending a notification. This is assumed to be an email
     * by default, but can be extended through "augmentSend" to allow
     * adding of additional notification types (such as SMS, XML, etc)
     *
     */
    public function sendNotification($order)
    {
        // Deal with customer email
        if ($order->Email && ($this->SendNotificationTo == 'Customer' || $this->SendNotificationTo == "Both")) {
            if ($this->CustomSubject) {
                $subject = $this->CustomSubject;
            } else {
                $subject = _t('Orders.Order', 'Order') . " {$order->Number} {$order->Status}";
            }

            $email = Email::create()
                ->setSubject($subject)
                ->setTo($order->Email)
                ->setHTMLTemplate("\\SilverCommerce\\OrdersAdmin\\Email\\OrderNotificationEmail_Customer")
                ->setData([
                    "Order" => $order,
                    "SiteConfig" => $this->Parent(),
                    "Notification" => $this
                ]);

            if ($this->FromEmail) {
                $email->setFrom($this->FromEmail);
            }
            
            $this->extend("augmentEmailCustomer", $email, $order);
            
            $email->send();
        }

        // Deal with vendor email
        if ($this->VendorEmail && ($this->SendNotificationTo == 'Vendor' || $this->SendNotificationTo == "Both")) {
            if ($this->CustomSubject) {
                $subject = $this->CustomSubject;
            } else {
                $subject = _t('Orders.Order', 'Order') . " {$order->Number} {$order->Status}";
            }
            
            $email = Email::create()
                ->setSubject($subject)
                ->setTo($this->VendorEmail)
                ->setHTMLTemplate("\\SilverCommerce\\OrdersAdmin\\Email\\OrderNotificationEmail_Vendor")
                ->setData([
                    "Order" => $order,
                    "Notification" => $this
                ]);

            if ($this->FromEmail) {
                $email->setFrom($this->FromEmail);
            }
            
            $this->extend("augmentEmailVendor", $email, $order);
            
            $email->send();
        }
        
        $this->extend("augmentSend", $order);
    }
}
