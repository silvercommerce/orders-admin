<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;
use SilverCommerce\OrdersAdmin\Forms\GridField\OrdersDetailForm;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;

/**
 * Add additional functions to a contact
 */
class ContactExtension extends DataExtension
{

    private static $has_many = [
        "Invoices" => Invoice::class,
        "Estimates"=> Estimate::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Filter the invoice list
        $invoices_field = $fields->dataFieldByName("Invoices");
        $estimate_field = $fields->dataFieldByName("Estimates");

        if ($invoices_field) {
            $invoices_field
                ->getConfig()
                ->removeComponentsByType(GridFieldDetailForm::class)
                ->addComponent(new OrdersDetailForm());
        }

        if ($estimate_field) {
            $list = $estimate_field->getList();
            $list = $list->filter("ClassName", Estimate::class);
            $estimate_field->setList($list);
            $estimate_field
                ->getConfig()
                ->removeComponentsByType(GridFieldDetailForm::class)
                ->addComponent(new OrdersDetailForm());
        }
    }

    /**
     * Get all orders that have been generated and are marked
     * as paid or processing
     *
     * @return DataList
     */
    public function OutstandingInvoices()
    {
        return $this
            ->owner
            ->Invoices()
            ->filter("Status", Config::inst()->get(Invoice::class, "outstanding_statuses"));
    }

    /**
     * Get all orders that have been generated and are marked
     * as dispatched or canceled
     *
     * @return DataList
     */
    public function HistoricInvoices()
    {
        return $this
            ->owner
            ->Invoices()
            ->filter("Status", Config::inst()->get(Invoice::class, "historic_statuses"));
    }

    public function canView($member)
    {
        // Members can view their own records
        if ($member && $member->exists() && $member->ID == $this->owner->MemberID) {
            return true;
        }
    }

    public function canEdit($member)
    {
        // Members can edit their own records
        if ($member && $member->exists() && $member->ID == $this->owner->MemberID) {
            return true;
        }
    }

    public function canDelete($member)
    {
        // Members can delete their own records
        if ($member && $member->exists() && $member->ID == $this->owner->MemberID) {
            return true;
        }
    }
}
