<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText as HTMLText;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverStripe\Forms\DropdownField;

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
 * @author Mo <morven@ilateral.co.uk>
 */
class LineItem extends DataObject
{
    private static $table_name = 'LineItem';

    /**
     * The name of the param used on a related product to
     * track Stock Levels.
     * 
     * Defaults to StockLevel
     *
     * @var string
     * @config
     */
    private static $stock_param = "StockLevel";

    /**
     * Standard database columns
     * 
     * @var array
     * @config
     */
    private static $db = [
        "Key"           => "Varchar(255)",
        "Title"         => "Varchar",
        "Content"       => "HTMLText",
        "Quantity"      => "Int",
        "Price"         => "Currency",
        "Weight"        => "Decimal",
        "StockID"       => "Varchar(100)",
        "ProductClass"  => "Varchar",
        "Locked"        => "Boolean",
        "Stocked"       => "Boolean",
        "Deliverable"   => "Boolean",
        "Customisation" => "Text",
    ];

    /**
     * Foreign key associations in DB
     * 
     * @var array
     * @config
     */
    private static $has_one = [
        "Parent"      => Estimate::class,
        "Tax"         => TaxRate::class,
    ];
    
    /**
     * One to many associations
     *
     * @var array
     * @config
     */
    private static $has_many = [
        "Customisations" => LineItemCustomisation::class
    ];

    /**
     * Specify default values of a field
     *
     * @var array
     * @config
     */
    private static $defaults = [
        "Quantity"      => 1,
        "ProductClass"  => "Product",
        "Locked"        => false,
        "Stocked"       => false,
        "Deliverable"   => true
    ];

    /**
     * Fields to display in list tables
     * 
     * @var array
     * @config
     */
    private static $summary_fields = [
        "Quantity"  => "Quantity",
        "Title"     => "Title",
        "StockID"   => "Stock ID",
        "Price"     => "Item Price",
        "TaxID"    => "Tax",
        "CustomisationAndPriceList" => "Customisations"
    ];
    
    /**
     * Function to DB Object conversions
     * 
     * @var array
     * @config
     */
    private static $casting = [
        "UnitPrice" => "Currency",
        "UnitTax"   => "Currency",
        "SubTotal"  => "Currency",
        "TaxRate"   => "Decimal",
        "TaxTotal"  => "Currency",
        "Total"     => "Currency"
    ];

    /**
     * Modify default field scaffolding in admin
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $config = SiteConfig::current_site_config();

        $fields->removeByName("Customisation");

        $fields->addFieldToTab(
            "Root.Main",
            ReadonlyField::create("Key"),
            "Title"
        );

        $fields->addFieldToTab(
            "Root.Main",
            DropdownField::create(
                "TaxID",
                $this->fieldLabel("TaxID"),
                $config->TaxRates()->map()
            ),
            "Weight"
        );

        $fields->addFieldsToTab(
            "Root.Description",
            array(
                HTMLEditorField::create("Content")
                    ->addExtraClass("stacked")
            )
        );

        // Change unlink button to remove on customisation
        $custom_field = $fields->dataFieldByName("Customisations");

        if ($custom_field) {
            $config = $custom_field->getConfig();
            $config
                ->removeComponentsByType(GridFieldDeleteAction::class)
                ->removeComponentsByType(GridFieldDataColumns::class)
                ->removeComponentsByType(GridFieldEditButton::class)
                ->removeComponentsByType(GridFieldAddNewButton::class)
                ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
                ->addComponents(
                    new GridFieldEditableColumns(),
                    new GridFieldAddNewInlineButton(),
                    new GridFieldEditButton(),
                    new GridFieldDeleteAction()
                );
            
                $custom_field->setConfig($config);
        }

        $this->extend("updateCMSFields", $fields);

        return $fields;
    }

    /**
     * Get the rate of tax for this item
     * 
     * @return float
     */
    public function getTaxRate()
    {
        $rate = ($this->Tax()->exists()) ? $this->Tax()->Rate : 0;

        $this->extend("updateTaxRate", $rate);

        return $rate;
    }
    
    /**
     * Get the price for a single line item (unit), minus any
     * tax
     * 
     * @return float
     */
    public function getUnitPrice()
    {
        $total = $this->Price;

        foreach ($this->Customisations() as $customisation) {
            $total += $customisation->Price;
        }

        $this->extend("updateUnitPrice", $total);

        return $total;
    }

    /**
     * Get the amount of tax for a single unit of this item
     * 
     * @return float
     */
    public function getUnitTax()
    {
        $total = ($this->UnitPrice / 100) * $this->TaxRate;

        $this->extend("updateUnitTax", $total);

        return $total;
    }

    /**
     * Get the value of this item, minus any tax
     * 
     * @return float
     */
    public function getSubTotal()
    {
        $total = $this->UnitPrice * $this->Quantity;

        $this->extend("updateSubTotal", $total);

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

        $this->extend("updateTaxTotal", $total);

        return $total;
    }

    /**
     * Get the value of this item, minus any tax
     * 
     * @return float
     */
    public function getTotal()
    {
        $total = $this->SubTotal + $this->Tax;

        $this->extend("updateTotal", $total);

        return $total;
    }

    /**
     * Get an image object associated with this line item.
     * By default this is retrieved from the base product.
     * 
     * @return Image | null
     */
    public function Image()
    {
        $product = $this->FindStockItem();

        if ($product && method_exists($product, "SortedImages")) {
            return  $product->SortedImages()->first();
        } elseif ($product && method_exists($product, "Images")) {
            return $product->Images()->first();
        } elseif ($product && method_exists($product, "Image") && $product->Image()->exists()) {
            return $product->Image();
        }
    }
    
    /**
     * Provide a string of customisations seperated by a comma but not
     * including a price
     *
     * @return string
     */
    public function CustomisationList()
    {
        $return = "";
        $items = $this->Customisations();
        
        if ($items && $items->exists()) {
            $map = [];

            foreach ($items as $item) {
                $map[] = $item->Title . ': ' . $item->Value;
            }

            $return = implode(", ", $map);
        }

        $this->extend("updateCustomisationList", $return);
        
        return $return;
    }

    /**
     * Provide a string of customisations seperated by a comma and
     * including a price
     *
     * @return string
     */
    public function CustomisationAndPriceList()
    {
        $return = "";
        $items = $this->Customisations();
        
        if ($items && $items->exists()) {
            $map = [];

            foreach ($items as $item) {
                $map[] = $item->Title . ': ' . $item->Value . ' (' . $item->dbObject("Price")->Nice() . ')';
            }

            $return = implode(", ", $map);
        }

        $this->extend("updateCustomisationAndPriceList", $return);
        
        return $return;
    }
    
    /**
     * Unserialise the list of customisations and rendering into a basic
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
     * Match this item to another object in the Database, by the
     * provided details.
     * 
     * @param $relation_name = The class name of the related dataobject
     * @param $relation_col = The column name of the related object
     * @param $match_col = The column we use to match the two objects
     * @return DataObject
     */
    public function Match($relation_name = null, $relation_col = "StockID", $match_col = "StockID")
    {
        // Try to determine relation name
        if (!$relation_name && !$this->ProductClass) {
            $relation_name = "Product";
        } elseif(!$relation_name && $this->ProductClass) {
            $relation_name = $this->ProductClass;
        }
        
        return $relation_name::get()
            ->filter($relation_col, $this->$match_col)
            ->first();
    }

    /**
     * Find our original stock item (useful for adding links back to the
     * original product).
     * 
     * This function is a synonym for @Link Match (as a result of) merging
     * LineItem
     * 
     * @return DataObject
     */
    public function FindStockItem()
    {
        return $this->Match();
    }

    /**
     * Check stock levels for this item, will return the actual number
     * of remaining stock after removing the current quantity
     * 
     * @param $qty The quantity we want to check against
     * @return Int
     */
    public function checkStockLevel($qty)
    {
        $stock_param = $this->config()->get("stock_param");
        $item = $this->Match();
        $stock = ($item->$stock_param) ? $item->$stock_param : 0;
        $return = $stock - $qty;

        $this->extend("updateCheckStockLevel", $return);
        
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
     * Pre-write tasks
     *
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Generate a unique item key based on 
        $this->Key = $this->StockID . ':' . base64_encode(json_encode($this->Customisations()->toArray()));
    }

    /**
     * Perform post-DB write functions
     *
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->Customisation) {
            $data = unserialize($this->Customisation);

            if ($data instanceof ArrayList) {
                foreach ($data as $data_item) {
                    $data_item->ParentID = $this->ID;
                    $data_item->write();
                }

                $this->Customisation = null;
                $this->write();
            }
        }
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
}