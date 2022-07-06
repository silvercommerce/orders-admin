<?php

namespace SilverCommerce\OrdersAdmin\Interfaces;

/**
 * Scaffold for an orderable object
 */
interface Orderable
{
    /**
     * Return the total quantity of items in the cart
     *
     * @return int
     */
    public function getTotalItems(): int;

    /**
    * Return the total weight of all items in the cart
    *
    * @return float
    */
    public function getTotalWeight(): float;

    /**
     * Total values of items (without tax)
     *
     * @return float
     */
    public function getSubTotal(): float;

    /**
     * Total tax for the order
     *
     * @return float
     */
    public function getTaxTotal(): float;

    /**
     * Total value of order (including tax, if relevent)
     *
     * @return float
     */
    public function getTotal(): float;

    /**
     * Does the current estimate/invoice need to be delivered?
     * Normally this returns true if ANY items are considered
     * deliverable.
     *
     * @return bool
     */
    public function isDeliverable(): bool;

    /**
     * Should the current estimate/invoice be considered
     * (locked)?
     * 
     * Usually this means the estimate only contains locked
     * items.
     *
     * @return bool
     */
    public function isLocked(): bool;

    /**
     * Set if this estimate can display negative values?
     *
     * @return bool
     */
    public function setAllowNegativeValue(bool $allow_negative): self;

    /**
     * Can this estimate display negative values? If this is
     * true then the the estimate's subtotal/total will return 0
     * if the total value is less than zero
     *
     * @return bool
     */
    public function canHaveNegativeValue(): bool;
}
