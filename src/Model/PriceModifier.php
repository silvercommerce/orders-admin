<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverCommerce\TaxAdmin\Traits\Taxable;
use SilverStripe\ORM\DataObject;

/**
 * A single pricing modification (either positive or negative)
 *
 * The idea is that modules (such as bulk pricing, sale prices, etc) can
 * apply transparent modifiers without having to adjust the
 * base price directly
 *
 * @property string Name
 * @property float  ModifyPrice
 *
 * @method LineItem LineItem
 * @method LineItemCustomisation Customisation
 */
class PriceModifier extends DataObject
{
    use Taxable;

    private static $table_name = 'Orders_PriceModifier';

    private static $db = [
        'Name' => 'Varchar',
        'ModifyPrice' => 'Decimal(9,3)'
    ];

    private static $has_one = [
        'LineItem' => LineItem::class,
        'Customisation' => LineItemCustomisation::class
    ];

    private static $summary_fields = [
        'Name',
        'ModifyPrice'
    ];

    private static $field_labels = [
        'Name' => 'Modification applied',
        'ModifyPrice' => 'Modify base item price'
    ];

    public function isNegative(): bool
    {
        return ($this->ModifyPrice < 0);
    }

    public function isPositive(): bool
    {
        return ($this->ModifyPrice >= 0);
    }

    public function getLocale()
    {
        return $this->LineItem()->getLocale();
    }

    public function getBasePrice()
    {
        return $this->dbObject('ModifyPrice')->getValue();
    }

    public function getTaxRate()
    {
        return $this->LineItem()->getTaxRate();
    }

    public function getShowPriceWithTax()
    {
        return $this->LineItem()->getShowPriceWithTax();
    }

    public function getShowTaxString()
    {
        return false;
    }
}
