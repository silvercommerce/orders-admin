<?php

namespace SilverCommerce\OrdersAdmin\Tests\Model;

use SilverStripe\i18n\i18n;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverCommerce\TaxAdmin\Traits\Taxable;
use SilverCommerce\TaxAdmin\Model\TaxCategory;
use SilverCommerce\TaxAdmin\Interfaces\TaxableProvider;

class TestProduct extends DataObject implements TestOnly, TaxableProvider
{
    use Taxable;

    /**
     * Default behaviour for price with tax (if current instance not set)
     *
     * @var boolean
     */
    private static $show_price_with_tax = false;

    /**
     * Default behaviour for adding the tax string to the rendered currency.
     *
     * @var boolean
     */
    private static $show_tax_string = false;

    private static $db = [
        "Title" => "Varchar",
        "BasePrice" => 'Decimal(9,3)',
        "StockID" => "Varchar",
        "StockLevel" => "Int",
        "Weight" => "Decimal"
    ];

    private static $has_one = [
        'TaxRate' => TaxRate::class,
        'TaxCategory' => TaxCategory::class
    ];

    public function getBasePrice()
    {
        return $this->dbObject('BasePrice')->getValue();
    }

    /**
     * Find a tax rate based on the selected ID, or revert to using the valid tax
     * from the current category
     *
     * @return \SilverCommerce\TaxAdmin\Model\TaxRate
     */
    public function getTaxRate()
    {
        $tax = TaxRate::get()->byID($this->getOwner()->TaxRateID);

        // If no tax explicity set, try to get from category
        if (empty($tax)) {
            $category = TaxCategory::get()->byID($this->getOwner()->TaxCategoryID);

            $tax = (!empty($category)) ? $category->ValidTax() : null ;
        }

        if (empty($tax)) {
            $tax = TaxRate::create();
            $tax->ID = -1;
        }

        return $tax;
    }

    /**
     * Get should this field automatically show the price including TAX?
     *
     * @return bool
     */
    public function getShowPriceWithTax()
    {
        return (bool)$this->config()->get('show_price_with_tax');
    }

    /**
     * Get if this field should add a "Tax String" (EG Includes VAT) to the rendered
     * currency?
     *
     * @return bool|null
     */
    public function getShowTaxString()
    {
        return (bool)$this->config()->get('show_tax_string');
    }

    /**
     * Return the currently available locale
     *
     * @return string
     */
    public function getLocale()
    {
        return i18n::get_locale();
    }
}
