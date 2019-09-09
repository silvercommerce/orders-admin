<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverCommerce\TaxAdmin\Interfaces\TaxableProvider;
use SilverCommerce\TaxAdmin\Traits\Taxable;
use SilverStripe\ORM\DataObject;

/**
 * A single customisation that can be applied to a LineItem.
 *
 * A customisation by default allows the following details:
 *  - Title: The name of the customisation (eg. "Colour")
 *  - Value: The data associated with thie customisation (eg. "Red")
 *  - Price: Does this customisation change the LineItem's price?
 */
class LineItemCustomisation extends DataObject implements TaxableProvider
{
    use Taxable;

    private static $table_name = 'LineItemCustomisation';

    /**
     * Standard database columns
     *
     * @var array
     * @config
     */
    private static $db = [
        "Title" => "Varchar",
        "Value" => "Text",
        "BasePrice" => "Decimal(9,3)",
        "Price" => "Decimal(9,3)"
    ];

    /**
     * DB foreign key associations
     *
     * @var array
     * @config
     */
    private static $has_one = [
        "Parent" => LineItem::class
    ];

    /**
     * Fields to display in gridfields
     *
     * @var array
     * @config
     */
    private static $summary_fields = [
        "Title",
        "Value",
        "Price"
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
        return $this->Parent()->getLocale();
    }
    /**
     * Get should this field automatically show the price including TAX?
     *
     * @return bool
     */
    public function getShowPriceWithTax()
    {
        return $this->Parent()->getShowPriceWithTax();
    }

    /**
     * We don't want to show a tax string on Line Items
     *
     * @return false
     */
    public function getShowTaxString()
    {
        return false;
    }
}
