<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Core\Config\Config;
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
            $list = $invoices_field->getList();
            $list = $list->filter("ClassName", Invoice::class);
            $invoices_field->setList($list);
        }
    }
}