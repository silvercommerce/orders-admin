<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Core\Config\Config;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Model\MemberAddress;

/**
 * Add additional settings to a memeber object
 * 
 * @package orders-admin
 * @subpackage extensions
 */
class MemberExtension extends DataExtension
{

    private static $casting = [
        "ContactTitle" => "Varchar"
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->ID) {
            $fields->addFieldToTab(
                "Root.Main",
                ReadonlyField::create("ContactTitle")
            );
        }
    }

    /**
     * Find a contact associated with this account
     *
     * @return Contact
     */
    public function Contact()
    {
        $contact = Contact::get()
            ->find("MemberID", $this->owner->ID);
        
        if (!$contact) {
            $contact = Contact::create();
            $contact->ID = -1;
        }

        return $contact;
    }

    /**
     * The name of the contact assotiated with this account
     *
     * @return void
     */
    public function getContactTitle()
    {
        $contact = $this->owner->Contact();

        return $contact->Title;
    }

    /**
     * Get a discount from the groups this member is in
     *
     * @return Discount
     */
    public function getDiscount()
    {
        $discounts = ArrayList::create();

        foreach ($this->owner->Groups() as $group) {
            foreach ($group->Discounts() as $discount) {
                $discounts->add($discount);
            }
        }

        $discounts->sort("Amount", "DESC");

        return $discounts->first();
    }

    /**
     * Get all invoices from a contact that are designated
     * "outstanding" 
     *
     * @return DataList
     */
    public function OutstandingInvoices()
    {
        return $this
            ->owner
            ->Contact()
            ->OutstandingInvoices();
    }

    /**
     * Get all invoices from a contact that are designated
     * "historic"
     *
     * @return DataList
     */
    public function HistoricInvoices()
    {
        return $this
            ->owner
            ->Contact()
            ->HistoricInvoices();
    }

    /**
     * If no contact exists for this account, then create one
     *
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (!$this->Contact()->exists()) {
            $contact = Contact::create([
                "FirstName" => $this->owner->FirstName,
                "Surname" => $this->owner->Surname,
                "Email" => $this->owner->Email
            ]);
            $contact->MemberID = $this->owner->ID;
            $contact->write();
        }
    }
}
