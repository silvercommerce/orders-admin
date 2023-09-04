<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\i18n\i18n;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Versioned\Versioned;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverCommerce\TaxAdmin\Traits\Taxable;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Subsites\State\SubsiteState;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\ORM\FieldType\DBHTMLText as HTMLText;
use SilverCommerce\TaxAdmin\Interfaces\TaxableProvider;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;
use SilverCommerce\OrdersAdmin\Forms\GridField\LineItemRelationConfig;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use SilverCommerce\VersionHistoryField\Forms\VersionHistoryField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * A LineItem is a single line item on an order, extimate or even in
 * the shopping cart.
 *
 * An item has a number of fields that describes a product:
 *
 * - Key: ID used to detect this item
 * - Title: Title of the item
 * - Content: Description of this object
 * - Quantity: Number or items in this order
 * - Weight: Weight of this item (unit of measurment is defined globally)
 * - TaxRate: Rate of tax for this item (e.g. 20.00 for 20%)
 * - ProductClass: ClassName of product that this item is matched against
 * - StockID: Unique identifier of this item (used with ProductClass
 *            match to a product)
 * - Locked: Is this a locked item? Locked items cannot be changed in the
 *           shopping cart
 * - Deliverable: Is this a product that can be delivered? This can effect
 *                delivery options
 *
 * @property string Key
 * @property string Title
 * @property float  UnmodifiedPrice
 * @property int    Quantity
 * @property string StockID
 * @property bool   Locked
 * @property bool   Stocked
 * @property bool   Deliverable
 * @property float  UnitPrice
 * @property float  UnitTax
 * @property float  UnitTotal
 * @property float  SubTotal
 * @property float  TaxRate
 * @property float  TaxTotal
 * @property float  Total
 * @property string CustomisationsString
 * @property string CustomisationAndPriceList
 * @property string Currency
 * @property string CurrencySymbol
 * @property float  NoTaxPrice
 * @property float  TaxPercentage
 *
 * @method Estimate Parent
 * @method TaxRate TaxRate
 * @method HasManyList PriceModifications
 * @method HasManyList Customisations
 *
 */
class LineItem extends DataObject implements TaxableProvider
{
    use Taxable;

    const STOCKID = "StockID";

    private static $table_name = 'LineItem';

    /**
     * The name of the param used on a related product to
     * track Stock Levels.
     *
     * Defaults to StockLevel
     *
     * @var string
     */
    private static $stock_param = "StockLevel";

    private static $db = [
        'Key'               => 'Varchar(255)',
        'Title'             => 'Varchar(255)',
        'Quantity'          => 'Int',
        'StockID'           => 'Varchar(100)',
        'UnmodifiedPrice'   => 'Decimal(9,3)', // Basic, unmodified price for a single unit
        'Locked'            => 'Boolean',
        'Stocked'           => 'Boolean',
        'Deliverable'       => 'Boolean',

        // Polymorphic-ish filds to handle storing product data from versioned table
        'ProductID' => 'Int',
        'ProductClass' => 'Varchar(255)',
        'ProductVersion' => 'Int',

        // Legacy fields
        "BasePrice"     => "Decimal(9,3)",
        "Price"         => "Currency",
    ];

    private static $has_one = [
        "Parent"      => Estimate::class,
        "TaxRate"     => TaxRate::class,

        // Legacy params
        "Tax"         => TaxRate::class,
    ];

    private static $has_many = [
        'PriceModifications' => PriceModifier::class,
        'Customisations'     => LineItemCustomisation::class
    ];

    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    private static $versioning = [
        "History"
    ];

    private static $owned_by = [
        'Parent'
    ];

    private static $cascade_deletes = [
        'Customisations'
    ];

    private static $defaults = [
        "Quantity"      => 1,
        "Locked"        => false,
        "Stocked"       => false,
        "Deliverable"   => true
    ];

    private static $casting = [
        'UnitPrice' => 'Currency(9,3)',
        'UnitTax' => 'Currency(9,3)',
        'UnitTotal' => 'Currency(9,3)',
        'SubTotal' => 'Currency(9,3)',
        'TaxRate' => 'Decimal',
        'TaxTotal' => 'Currency(9,3)',
        'Total' => 'Currency(9,3)',
        'UnitWeight' => 'Decimal',
        'TotalWeight' => 'Decimal',
        'CustomisationList' => 'Text',
        'PriceModificationString' => 'Text',
        'CustomisationAndPriceList' => 'Text'
    ];

    private static $summary_fields = [
        "Quantity",
        "Title",
        "StockID",
        "UnmodifiedPrice",
        "TaxRateID",
        "PriceModificationString"
    ];

    private static $field_labels = [
        "UnitPrice"         => "Single Item Price",
        "TaxRateID"         => "Tax",
        "PriceModificationString" => "Modifications"
    ];

    /**
     * If this object is linked to an existing object,
     * return that, else return the default
     *
     * @return string
     */
    public function getStockID(): string
    {
        $product = $this->findStockItem();

        if ($product->exists()) {
            return (string)$product->StockID;
        }

        return (string)$this->dbObject('StockID');
    }

    /**
     * Get the basic price for this line without
     * modification
     *
     * @return float
     */
    public function getBasePrice(): float
    {
        // First check if the single unit price is set
        $price = $this
            ->dbObject('UnmodifiedPrice')
            ->getValue();

        // If no cached price, attempt to get the price from the
        // stock item
        if ($price == 0) {
            $product = $this->findStockItem();
            $price = $product->getBasePrice();
        }

        $this->extend('updateBasePrice', $price);

        return (float) $price;
    }

    /**
     * Get the price for a single line item (unit), minus any tax
     *
     * @return float
     */
    public function getNoTaxPrice(): float
    {
        $price = $this->getBasePrice();

        /** @var PriceModifier modifier */
        foreach ($this->PriceModifications() as $modifier) {
            $price += $modifier->ModifyPrice;
        }

        $this->extend('updateNoTaxPrice', $price);

        return $price;
    }

    public function getUnitPrice(): float
    {
        return $this->getNoTaxPrice();
    }

    /**
     * Stub method that is more logically named
     *
     * @return float
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $config = SiteConfig::current_site_config();
            $product = $this->findStockItem();

            $fields->removeByName([
                'Customisation',
                'Price',
                'TaxID',
                'ProductID',
                'ProductClass',
                'ProductVersion',
                'BasePrice'
            ]);

            $fields->addFieldToTab(
                "Root.Main",
                ReadonlyField::create("Key"),
                "Title"
            );

            // If a product is set, remove unmodified price and
            // swap with disabled product price
            if ($product->exists()) {
                $fields->replaceField(
                    'UnmodifiedPrice',
                    ReadonlyField::create(
                        'ProductBasePrice',
                        $this->fieldLabel('BasePrice')
                    )->setValue($product->BasePrice)
                );
            }

            $fields->addFieldToTab(
                "Root.Main",
                DropdownField::create(
                    "TaxRateID",
                    $this->fieldLabel("TaxRate"),
                    $config->TaxRates()->map()
                ),
                "Locked"
            );

            $custom_field = $fields->dataFieldByName("Customisations");

            if ($custom_field) {
                $custom_field->setConfig(LineItemRelationConfig::create());
            }

            $modifiers_field = $fields->dataFieldByName("PriceModifications");

            if ($modifiers_field) {
                $modifiers_field->setConfig(LineItemRelationConfig::create());
            }

            $fields->addFieldToTab(
                "Root.History",
                VersionHistoryField::create(
                    "History",
                    _t("SilverCommerce\VersionHistoryField.History", "History"),
                    $this
                )->addExtraClass("stacked")
            );
        });

        return parent::getCMSFields();
    }

    /**
     * Return the tax rate for this Object
     *
     * @return TaxRate
     */
    public function getTaxRate(): TaxRate
    {
        $rate = $this->TaxRate();

        $this->extend('updateTaxRate', $rate);

        return $rate;
    }

    /**
     * Get the amount of tax for a single unit of this item
     *
     * **NOTE** Tax is rounded at the single item price to avoid multiplication
     * weirdness. For example 49.995 + 20% is 59.994 for one product,
     * but 239.976 for 4 (it should be 239.96)
     *
     * @return float
     */
    public function getUnitTax()
    {
        // Calculate and round tax now to try and minimise penny rounding issues
        $total = ($this->UnitPrice / 100) * $this->TaxPercentage;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateUnitTax", $total)
        );

        if (!empty($result)) {
            return $result;
        }

        return $total;
    }

    /**
     * Overwrite TaxAmount with unit tax
     *
     * @return float
     */
    public function getTaxAmount()
    {
        $tax = $this->UnitTax;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateTaxAmount", $tax)
        );

        if (!empty($result)) {
            return $result;
        }

        return $tax;
    }

    /**
     * Get the total price and tax for a single unit
     *
     * @return float
     */
    public function getUnitTotal()
    {
        $total = $this->UnitPrice + $this->UnitTax;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateUnitTotal", $total)
        );

        if (!empty($result)) {
            return $result;
        }

        return $total;
    }

    /**
     * Get the value of this item, minus any tax
     *
     * @return float
     */
    public function getSubTotal()
    {
        $total = $this->NoTaxPrice * $this->Quantity;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateSubTotal", $total)
        );

        if (!empty($result)) {
            return $result;
        }

        return $total;
    }

    /**
     * Get the total amount of tax for a single unit of this item
     *
     * @return float
     */
    public function getTaxTotal()
    {
        $total = $this->UnitTax * $this->Quantity;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateTaxTotal", $total)
        );

        if (!empty($result)) {
            return (float) $result;
        }

        return (float) $total;
    }

    /**
     * Get the value of this item, minus any tax
     *
     * @return float
     */
    public function getTotal()
    {
        $total = $this->SubTotal + $this->TaxTotal;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateTotal", $total)
        );

        if (!empty($result)) {
            return $result;
        }
    
        return $total;
    }

    /**
     * Get the weight of the related product
     * (if available)
     *
     * @return float
     */
    public function getUnitWeight(): float
    {
        $product = $this->findStockItem();
        $weight = 0;

        if (!empty($product)) {
            $weight = $product->Weight;
        }

        return (float) $weight;
    }

    /**
     * Get the total weight for this line
     *
     * @return float
     */
    public function getTotalWeight(): float
    {
        return $this->UnitWeight * $this->Quantity;
    }

    /**
     * Get the locale from the site
     *
     * @return string
     */
    public function getLocale(): string
    {
        return i18n::get_locale();
    }

    /**
     * We don't want to show a tax string on Line Items
     *
     * @return bool
     */
    public function getShowTaxString(): bool
    {
        $show = false;

        $this->extend('updateShowTaxString', $show);
        
        return $show;
    }

    /**
     * Get should this field automatically show the price including TAX?
     *
     * @return bool
     */
    public function getShowPriceWithTax(): bool
    {
        $config = SiteConfig::current_site_config();
        $show = $config->ShowPriceAndTax;

        $result = $this->filterTaxableExtensionResults(
            $this->extend("updateShowPriceWithTax", $show)
        );

        if (!empty($result)) {
            return (bool)$result;
        }

        return (bool)$show;
    }

    /**
     * Find the stock item linked from this line item
     *
     * **NOTE** This method will return a product from the
     * version table, so modifying or writing it will result in a
     * duplicate item being created.
     *
     * @return DataObject
     */
    public function findStockItem(): DataObject
    {
        $stock_id = $this
            ->dbObject('StockID')
            ->getValue();
        $class = $this->ProductClass;
        $id = $this->ProductID;
        $version = $this->ProductVersion;
        $product = null;
        $filter = [];

        $subsites_exists = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('silverstripe/subsites');

        if ($subsites_exists) {
            $filter['SubsiteID'] = SubsiteState::singleton()->getSubsiteId();
        }

        // Backwards compatability for older line items
        if ((empty($id) || empty($version))
            && !empty($stock_id) && !empty($class)
        ) {
            $filter['StockID'] = $stock_id;
            $product = $class::get()
                ->filter($filter)
                ->first();
        }

        // Finally, try and get product from versions table
        if (empty($product) && !empty($class) && $version > 0) {
            $product = Versioned::get_version($class, $id, $version);
        }

        if (empty($product)) {
            $product = CatalogueProduct::create(['ID', -1]);
        }

        return $product;
    }
    
    /**
     * Provide a string of customisations seperated by a comma but not
     * including a price
     *
     * @return string
     */
    public function getCustomisationsString()
    {
        $return = [];
        $items = $this->Customisations();

        if ($items && $items->exists()) {
            foreach ($items as $item) {
                $return[] = $item->Title . ': ' . $item->Value;
            }
        }

        $this->extend("updateCustomisationList", $return);

        return implode(", ", $return);
    }

    /**
     * Generate a string of price modifications
     *
     * @return string
     */
    public function getPriceModificationString()
    {
        $return = [];
        $modifications = $this->PriceModifications();

        foreach ($modifications as $modification) {
            $return[] = $modification->Name . ' (' . $modification->getFormattedPrice() . ')';
        }

        $this->extend("updateCustomisationAndPriceList", $return);

        return implode(", ", $return);
    }
    
    /**
     * Get list of customisations rendering into a basic
     * HTML string
     *
     * @return HTMLText
     */
    public function CustomisationHTML()
    {
        $return = HTMLText::create();
        $items = $this->Customisations();
        $html = "";
        
        if ($items && $items->exists()) {
            foreach ($items as $item) {
                $html .= $item->Title . ': ' . $item->Value . ";<br/>";
            }
        }

        $return->setValue($html);

        $this->extend("updateCustomisationHTML", $return);

        return $return;
    }

    /**
     * Check stock levels for this item, will return the actual number
     * of remaining stock after removing the current quantity
     *
     * @param int $qty The quantity we want to check against
     *
     * @return int
     */
    public function checkStockLevel($qty)
    {
        $stock_param = $this->config()->get("stock_param");
        $item = $this->findStockItem();
        $stock = ($item->$stock_param) ? $item->$stock_param : 0;
        $return = $stock - $qty;

        $this->extend("updateCheckStockLevel", $return, $qty);
        
        return $return;
    }

    /**
     * Only order creators or users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null, $context = [])
    {
        $extended = $this->extend('canView', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return $this->Parent()->canView($member);
    }

    /**
     * Anyone can create an order item
     *
     * @return Boolean
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extend('canCreate', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return true;
    }

    /**
     * No one can edit items once they are created
     *
     * @return Boolean
     */
    public function canEdit($member = null, $context = [])
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return $this->Parent()->canEdit($member);
    }

    /**
     * No one can delete items once they are created
     *
     * @return Boolean
     */
    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extend('canDelete', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        return $this->Parent()->canEdit($member);
    }

    /**
     * Overwrite default duplicate function
     *
     * @param boolean $doWrite (write the cloned object to DB)
     * @return DataObject $clone The duplicated object
     */
    public function duplicate($doWrite = true, $manyMany = "many_many")
    {
        $clone = parent::duplicate($doWrite);

        // Ensure we clone any customisations
        if ($doWrite) {
            foreach ($this->Customisations() as $customisation) {
                $new_item = $customisation->duplicate(false);
                $new_item->ParentID = $clone->ID;
                $new_item->write();
            }
        }

        return $clone;
    }

    /**
     * Clean up DB on deletion
     *
     * @return void
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        foreach ($this->Customisations() as $customisation) {
            $customisation->delete();
        }
    }

    /********* DEPRECIATED METHODS ***********/
    public function getCustomisationAndPriceList()
    {
        Deprecation::notice('2.0', 'Customisation prices migrated to pricing modifiers');

        return $this->getPriceModificationString();
    }

    /**
     * Provide a string of customisations seperated by a comma but not
     * including a price
     *
     * @return string
     */
    public function getCustomisationList()
    {
        Deprecation::notice('2.0', 'Customisation list renamed');

        return $this->getCustomisationsString();
    }

    public function Match()
    {
        Deprecation::notice('2.0', 'Match will be removed in favour of "findStockItem"');

        return $this->findStockItem();
    }

    public function Image()
    {
        Deprecation::notice('2.0', 'Image method will be depretiated');

        return $this->getImage();
    }

    public function generateKey()
    {
        // Generate a unique item key based on the current ID and customisations
        $key = base64_encode(
            json_encode(
                $this->Customisations()->map("Title", "Value")->toArray()
            )
        );
        return $this->StockID . ':' . $key;
    }
}
