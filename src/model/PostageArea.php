<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\TaxAdmin\Model\TaxCategory;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;

/**
 * Postage objects list available postage costs and destination locations
 *
 * @author Mo <morven@ilateral.co.uk>
 */
class PostageArea extends DataObject
{
    private static $table_name = 'PostageArea';

    private static $db = [
        "Title"         => "Varchar",
        "Country"       => "Varchar(255)",
        "ZipCode"       => "Text",
        "Calculation"   => "Enum('Price,Weight,Items','Weight')",
        "Unit"          => "Decimal",
        "Cost"          => "Currency"
    ];

    private static $has_one = [
        "Site"          => SiteConfig::class,
        "TaxCategory"   => TaxCategory::class
    ];

    private static $summary_fields = [
        "Title",
        "Country",
        "ZipCode",
        "Calculation",
        "Unit",
        "Cost",
        "TaxRate"
    ];
    
    private static $casting = [
        "TaxRate"       => "Decimal",
        "TaxAmount"     => "Currency",
        "Total"         => "Currency"
    ];

    /**
     * Get the tax rate from the current category (or default) 
     *
     * @return TaxRate | null
     */
    public function getTaxFromCategory()
    {
        $cat = $this->TaxCategory();

        if (!$cat->exists() || !$cat->Rates()->exists()) {
            $config = SiteConfig::current_site_config();
            $cat = $config
                ->TaxCategories()
                ->sort("Default", "DESC")
                ->first();
        }

        if ($cat->exists() && $cat->Rates()->exists()) {
            return $cat->Rates()->first();
        }
    }

    /**
     * Get tax rate for this postage object
     *
     * @return Float
     */
    public function getTaxRate()
    {   
        $tax = $this->getTaxFromCategory();
        $rate = 0;
        
        if ($tax) {
            $rate = $tax->Rate;
        }
        
        $this->extend("updateTaxRate", $rate);

        return $rate;
    }

    /**
     * Get the amount of tax for this postage object.
     *
     * @return Float
     */
    public function getTaxAmount()
    {   
        $tax = $this->TaxCategory();
        $rate = 0;
        
        if ($tax) {
            $rate = $tax->Rate;
        }
        
        return MathsHelper::round_up((($this->Cost / 100) * $rate), 2);
    }


    /**
     * Get the total cost including tax
     * 
     * @param int $decimal_size Should we round this number to a
     *             specific size? If set will round the output. 
     * @return Float
     */
    public function getTotal()
    {
        $total = $this->Cost + $this->getTaxAmount();

        $this->extend("updateTotal", $total);

        return $total; 
    }

    public function canView($member = null, $context = [])
    {
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
    
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }

    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extendedCan('canEdit', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }

    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extendedCan('canDelete', $member);
        if ($extended !== null) {
            return $extended;
        }
        
        return true;
    }
}
