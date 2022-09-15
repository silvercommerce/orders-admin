<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\Deprecation;
use SilverCommerce\TaxAdmin\Traits\Taxable;
use SilverCommerce\TaxAdmin\Interfaces\TaxableProvider;

/**
 * A single customisation that can be applied to a LineItem.
 *
 * A customisation by default allows the following details:
 *  - Title: The name of the customisation (eg. "Colour")
 *  - Value: The data associated with thie customisation (eg. "Red")
 *  - Price: Does this customisation change the LineItem's price?
 *
 * @property string Title
 * @property string Value
 * @property float BasePrice
 * @property float Price
 *
 * @method LineItem Parent
 */
class LineItemCustomisation extends DataObject implements TaxableProvider
{
    use Taxable;

    private static $table_name = 'LineItemCustomisation';

    /**
     * Standard database columns
     *
     * @var array
     */
    private static $db = [
        "Title" => "Varchar",
        "Value" => "Text",

        // Legacy Fields
        "BasePrice" => "Decimal(9,3)",
        "Price" => "Decimal(9,3)"
    ];

    /**
     * DB foreign key associations
     *
     * @var array
     */
    private static $has_one = [
        "Parent" => LineItem::class
    ];

    /**
     * Fields to display in gridfields
     *
     * @var array
     */
    private static $summary_fields = [
        "Title",
        "Value"
    ];

    private static $field_labels = [
        'BasePrice' => 'Price'
    ];

    public function getBasePrice()
    {
        return $this->dbObject('BasePrice')->getValue();
    }

    public function getTaxRate()
    {
        return $this->Parent()->getTaxRate();
    }

    public function getLocale()
    {
        Deprecation::notice('2.0', 'Customisation prices are depreciated, use PriceModifiers instead');

        return $this->Parent()->getLocale();
    }
    /**
     * Get should this field automatically show the price including TAX?
     *
     * @return bool
     */
    public function getShowPriceWithTax()
    {
        Deprecation::notice('2.0', 'Customisation prices are depreciated, use PriceModifiers instead');

        return $this->Parent()->getShowPriceWithTax();
    }

    /**
     * We don't want to show a tax string on Line Items
     *
     * @return false
     */
    public function getShowTaxString()
    {
        Deprecation::notice('2.0', 'Customisation prices are depreciated, use PriceModifiers instead');

        return false;
    }
}
