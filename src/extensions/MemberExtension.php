<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Config\Config;
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

    private static $db = [
        "PhoneNumber"   => "Varchar",
        "Company"       => "Varchar(99)"
    ];

    private static $has_many = [
        "Orders"        => Invoice::class,
        "Estimates"     => Estimate::class,
        "Addresses"     => MemberAddress::class
    ];
    
    private static $casting = [
        'Address1'          => 'Varchar',
        'Address2'          => 'Varchar',
        'City'              => 'Varchar',
        'PostCode'          => 'Varchar',
        'Country'           => 'Varchar'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->remove("PhoneNumber");

        $fields->addFieldToTab(
            "Root.Main",
            TextField::create("PhoneNumber"),
            "Password"
        );

        $fields->addFieldToTab(
            "Root.Main",
            TextField::create("Company"),
            "FirstName"
        );

        return $fields;
    }

    /**
     * Get all orders that have been generated and are marked as paid or
     * processing
     *
     * @return DataList
     */
    public function OutstandingOrders()
    {
        return $this
            ->owner
            ->Orders()
            ->filter(array(
                "Status" => Config::inst()->get(Invoice::class, "outstanding_statuses")
            ));
    }

    /**
     * Get all orders that have been generated and are marked as dispatched or
     * canceled
     *
     * @return DataList
     */
    public function HistoricOrders()
    {
        return $this
            ->owner
            ->Orders()
            ->filter(array(
                "Status" => Config::inst()->get(Invoice::class, "historic_statuses")
            ));
    }


    public function DefaultAddress()
    {
        return $this
            ->owner
            ->Addresses()
            ->sort("Default", "DESC")
            ->first();
    }
    
    /**
     * Get address line one from our default address
     * 
     * @return String
     */
    public function getAddress1()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->Address1;
        }
    }
    
    /**
     * Get address line two from our default address
     * 
     * @return String
     */
    public function getAddress2()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->Address2;
        }
    }
    
    /**
     * Get city from our default address
     * 
     * @return String
     */
    public function getCity()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->City;
        }
    }
    
    public function getPostCode()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->PostCode;
        }
    }
    
    /**
     * Get country from our default address
     * 
     * @return String
     */
    public function getCountry()
    {
        if ($address = $this->owner->getDefaultAddress()) {
            return $address->Country;
        }
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
}
