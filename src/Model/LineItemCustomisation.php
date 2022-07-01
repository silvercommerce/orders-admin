<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverCommerce\TaxAdmin\Interfaces\TaxableProvider;
use SilverCommerce\TaxAdmin\Traits\Taxable;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DataObject;

/**
 * Customisations allow basic key - value pairs to be assigned
 * to line items (for example: Colour - Blue)
 *
 * @property string Title name of the customisation (eg. "Colour")
 * @property string Value data associated customisation (eg. "Red")
 *
 * @method LineItem Parent
 */
class LineItemCustomisation extends DataObject implements TaxableProvider
{
    use Taxable;

    private static $table_name = 'LineItemCustomisation';

    private static $db = [
        'Name' => 'Varchar',
        'Value' => 'Text',

        // Legacy, price modifications are now run through
        // seperate modifiers
        'Title' => 'Varchar',
        'BasePrice' => 'Decimal(9,3)',
        'Price' => 'Decimal(9,3)'
    ];

    private static $has_one = [
        'Parent' => LineItem::class
    ];

    private static $casting = [
        'Title' => 'Varchar'
    ];

    private static $summary_fields = [
        'Title',
        'Value'
    ];

    public function getLocale()
    {
        return $this->Parent()->getLocale();
    }

    /**
     * Depreciated methods as of 2.0
     */
    public function getBasePrice()
    {
        return $this->dbObject('BasePrice')->getValue();
    }

    public function getTaxRate()
    {
        Deprecation::notice('2.0', 'Price modifications will be removed from customisations');

        return $this->Parent()->getTaxRate();
    }

    public function getShowPriceWithTax()
    {
        Deprecation::notice('2.0', 'Price modifications will be removed from customisations');

        return $this->Parent()->getShowPriceWithTax();
    }

    public function getShowTaxString()
    {
        Deprecation::notice('2.0', 'Price modifications will be removed from customisations');

        return false;
    }
}
