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
     * Get all invoices from a contact
     *
     * @return DataList
     */
    public function Invoices()
    {
        return $this
            ->owner
            ->Contact()
            ->Invoices();
    }

    /**
     * Get all estimates from a contact
     *
     * @return DataList
     */
    public function Estimates()
    {
        return $this
            ->owner
            ->Contact()
            ->Estimates();
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
}
