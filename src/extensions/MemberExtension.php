<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Add additional settings to a memeber object
 *
 * @package orders-admin
 * @subpackage extensions
 */
class MemberExtension extends DataExtension
{

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
