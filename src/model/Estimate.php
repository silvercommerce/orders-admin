<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\i18n\i18n;
use SilverCommerce\OrdersAdmin\Forms\GridField\AddLineItem;
use SilverCommerce\OrdersAdmin\Forms\GridField\LineItemGridField;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;
use SilverCommerce\ContactAdmin\Model\Contact;
use DateTime;

class Estimate extends DataObject implements PermissionProvider
{
    private static $table_name = 'Estimate';

    private static $db = [
        'OrderNumber'       => 'Varchar',
        
        // Billing Details
        'Company'           => 'Varchar',
        'FirstName'         => 'Varchar',
        'Surname'           => 'Varchar',
        'Address1'          => 'Varchar',
        'Address2'          => 'Varchar',
        'City'              => 'Varchar',
        'PostCode'          => 'Varchar',
        'Country'           => 'Varchar',
        'Email'             => 'Varchar',
        'PhoneNumber'       => 'Varchar',
        
        // Delivery Details
        'DeliveryCompany'   => 'Varchar',
        'DeliveryFirstName' => 'Varchar',
        'DeliverySurname'   => 'Varchar',
        'DeliveryAddress1'  => 'Varchar',
        'DeliveryAddress2'  => 'Varchar',
        'DeliveryCity'      => 'Varchar',
        'DeliveryPostCode'  => 'Varchar',
        'DeliveryCountry'   => 'Varchar',
        
        // Discount Provided
        "DiscountType"      => "Varchar",
        "DiscountAmount"    => "Currency",
        
        // Postage
        "PostageType"       => "Varchar",
        "PostageCost"       => "Currency",
        "PostageTax"        => "Currency"
    ];

    private static $has_one = [
        "Discount"  => Discount::class,
        "Postage"   => PostageArea::class,
        "Customer"  => Contact::class
    ];

    private static $has_many = [
        'Items'     => LineItem::class
    ];

    private static $casting = [
        'BillingAddress'    => 'Text',
        'CountryFull'       => 'Varchar',
        'DeliveryAddress'   => 'Text',
        'DeliveryCountryFull'=> 'Varchar',
        'SubTotal'          => 'Currency',
        'TaxTotal'          => 'Currency',
        'Total'             => 'Currency',
        'TotalItems'        => 'Int',
        'TotalWeight'       => 'Decimal',
        'ItemSummary'       => 'Text',
        'ItemSummaryHTML'   => 'HTMLText',
        'TranslatedStatus'  => 'Varchar',
        'PostageDetails'    => "Varchar",
        'DiscountDetails'   => "Varchar"
    ];

    private static $defaults = [
        'DiscountAmount'    => 0,
        "PostageCost"       => 0
    ];

    /**
     * Fields to show in summary views
     *
     * @var array
     * @config
     */
    private static $summary_fields = [
        "OrderNumber"   => "#",
        'Company'       => 'Company',
        'FirstName'     => 'First Name',
        'Surname'       => 'Surname',
        'Email'         => 'Email',
        'PostCode'      => 'Post Code',
        "Total"         => "Total",
        "Created"       => "Created",
        "LastEdited"    => "Last Edited"
    ];

    private static $extensions = [
        Versioned::class
    ];

    private static $versioning = [
        "History"
    ];

    private static $default_sort = [
        "LastEdited" => "DESC",
        "Created" => "DESC"
    ];

    /**
     * Get the complete billing address for this order
     *
     * @return string
     */
    public function getBillingAddress()
    {
        $address = ($this->Address1) ? $this->Address1 . ",\n" : '';
        $address .= ($this->Address2) ? $this->Address2 . ",\n" : '';
        $address .= ($this->City) ? $this->City . ",\n" : '';
        $address .= ($this->PostCode) ? $this->PostCode . ",\n" : '';
        $address .= ($this->Country) ? $this->Country : '';

        return $address;
    }

    /**
     * Get the rendered name of the billing country, based on the local
     * 
     * @return string
     */
    public function getCountryFull()
    {
        $list = i18n::getData()->getCountries();
        return (array_key_exists($this->Country, $list)) ? $list[$this->Country] : $this->Country;
    }

    /**
     * Get the complete delivery address for this order
     *
     * @return string
     */
    public function getDeliveryAddress()
    {
        $address = ($this->DeliveryAddress1) ? $this->DeliveryAddress1 . ",\n" : '';
        $address .= ($this->DeliveryAddress2) ? $this->DeliveryAddress2 . ",\n" : '';
        $address .= ($this->DeliveryCity) ? $this->DeliveryCity . ",\n" : '';
        $address .= ($this->DeliveryPostCode) ? $this->DeliveryPostCode . ",\n" : '';
        $address .= ($this->DeliveryCountry) ? $this->DeliveryCountry : '';

        return $address;
    }

    /**
     * Get the rendered name of the delivery country, based on the local
     * 
     * @return string 
     */
    public function getDeliveryCountryFull()
    {
        $list = i18n::getData()->getCountries();
        return (array_key_exists($this->DeliveryCountry, $list)) ? $list[$this->DeliveryCountry] : $this->DeliveryCountry;
    }

    /**
     * Generate a string outlining the details of selected
     * postage
     *
     * @return string
     */
    public function getPostageDetails()
    {
        if ($this->PostageType) {
            return $this->PostageType . " (" . $this->dbObject("PostageCost")->Nice() . ")";
        } else {
            return "";
        }      
    }

    /**
     * Generate a string outlining the details of selected
     * discount
     *
     * @return string
     */
    public function getDiscountDetails()
    {
        if ($this->DiscountType) {
            return $this->DiscountType . " (" . $this->dbObject("DiscountAmount")->Nice() . ")";
        } else {
            return "";
        }      
    }

    /**
     * Find the total quantity of items in the shopping cart
     *
     * @return Int
     */
    public function getTotalItems()
    {
        $total = 0;

        foreach ($this->Items() as $item) {
            $total += ($item->Quantity) ? $item->Quantity : 1;
        }

        $this->extend("updateTotalItems", $total);

        return $total;
    }

    /**
    * Find the total weight of all items in the shopping cart
    *
    * @return float
    */
    public function getTotalWeight()
    {
        $total = 0;
        
        foreach ($this->Items() as $item) {
            if ($item->Weight && $item->Quantity) {
                $total = $total + ($item->Weight * $item->Quantity);
            }
        }

        $this->extend("updateTotalWeight", $total);
        
        return $total;
    }

    /**
     * Total values of items in this order (without any tax)
     *
     * @return float
     */
    public function getSubTotal()
    {
        $total = 0;

        // Calculate total from items in the list
        foreach ($this->Items() as $item) {
            $total += $item->SubTotal;
        }
        
        $this->extend("updateSubTotal", $total);

        return $total;
    }

    /**
     * Total values of items in this order
     *
     * @return float
     */
    public function getTaxTotal()
    {
        $total = 0;
        $items = $this->Items();
        
        // Calculate total from items in the list
        foreach ($items as $item) {
            // If a discount applied, get the tax based on the
            // discounted amount
            if ($this->DiscountAmount > 0) {
                $discount = $this->DiscountAmount / $this->TotalItems;
                $price = $item->UnitPrice - $discount;
                $tax = ($price / 100) * $item->TaxRate;
            } else {
                $tax = $item->UnitTax;
            }

            $total += $tax * $item->Quantity;
        }
        
        if ($this->PostageTax) {
            $total += $this->PostageTax;
        }
        
        $this->extend("updateTaxTotal", $total);

        $total = MathsHelper::round_up($total, 2);

        return $total;
    }

    /**
     * Total of order including postage
     *
     * @return float
     */
    public function getTotal()
    {   
        $total = (($this->SubTotal + $this->Postage) - $this->DiscountAmount) + $this->TaxTotal;
        
        $this->extend("updateTotal", $total);
        
        return $total;
    }

    /**
     * Factory method to convert this estimate to an
     * order.
     *
     * At the moment this only changes the classname, but
     * using a factory allows us to add more complex
     * functionality in the future.
     *
     */
    public function convertToOrder()
    {
        $this->ClassName = Invoice::class;
    }

    /**
     * Return a list string summarising each item in this order
     *
     * @return string
     */
    public function getItemSummary()
    {
        $return = '';

        foreach ($this->Items() as $item) {
            $return .= "{$item->Quantity} x {$item->Title};\n";
        }

        $this->extend("updateItemSummary", $return);

        return $return;
    }

    /**
     * Return a list string summarising each item in this order
     *
     * @return HTMLText
     */
    public function getItemSummaryHTML()
    {
        $html = new HTMLText("ItemSummary");
        $html->setValue(nl2br($this->ItemSummary));
        
        $this->extend("updateItemSummaryHTML", $html);

        return $html;
    }

    /**
     * Has this order got a discount applied?
     *
     * @return boolean
     */
    public function hasDiscount()
    {
        return (ceil($this->DiscountAmount)) ? true : false;
    }
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $siteconfig = SiteConfig::current_site_config();

        $fields->removeByName("OrderNumber");
        $fields->removeByName("DiscountID");
        $fields->removeByName("DiscountType");
        $fields->removeByName("DiscountAmount");
        $fields->removeByName("PostageType");
        $fields->removeByName("PostageCost");
        $fields->removeByName("PostageTax");
        $fields->removeByName("Items");
        
        $fields->addFieldsToTab(
            "Root.Main",
            [
                // Items field
                GridField::create(
                    "Items",
                    "",
                    $this->Items(),
                    $config = GridFieldConfig::create()
                        ->addComponents(
                            new GridFieldButtonRow('before'),
                            new GridFieldTitleHeader(),
                            new GridFieldEditableColumns(),
                            new GridFieldEditButton(),
                            new GridFieldDetailForm(),
                            new GridFieldDeleteAction(),
                            new AddLineItem()
                        )
                ),

                LiteralField::create(
                    "ItemsDivider",
                    '<div class="field form-group"></div>'
                ),
                
                // Discount
                HeaderField::create(
                    "DiscountDetailsHeader",
                    _t("Orders.DiscountDetails", "Discount")
                ),
                DropdownField::create(
                    "DiscountID",
                    $this->fieldLabel("Discount"),
                    $siteconfig->Discounts()->map()
                )->setEmptyString(_t(
                    "OrdersAdmin.ApplyADiscount",
                    "Apply a discount"
                )),
                ReadonlyField::create("DiscountDetails"),
                
                // Sidebar
                FieldGroup::create(
                    ReadonlyField::create("OrderNumber", "#"),
                    ReadonlyField::create("SubTotalValue",_t("Orders.SubTotal", "Sub Total"))
                        ->setValue($this->obj("SubTotal")->Nice()),
                    ReadonlyField::create("DiscountValue",_t("Orders.Discount", "Discount"))
                        ->setValue($this->dbObject("DiscountAmount")->Nice()),
                    ReadonlyField::create("PostageValue",_t("Orders.Postage", "Postage"))
                        ->setValue($this->dbObject("PostageCost")->Nice()),
                    ReadonlyField::create("TaxValue",_t("Orders.Tax", "Tax"))
                        ->setValue($this->obj("TaxTotal")->Nice()),
                    ReadonlyField::create("TotalValue",_t("Orders.Total", "Total"))
                        ->setValue($this->obj("Total")->Nice())
                )->setName("OrdersSidebar")
                ->setTitle(_t("Orders.EstimateDetails", "Estimate Details"))
                ->addExtraClass("order-admin-sidebar")
            ]
        );

        $fields->addFieldsToTab(
            "Root.Customer",
            [
                DropdownField::create(
                    'CustomerID',
                    _t('OrdersAdmin.ExistingCustomer', 'Existing Customer'),
                    Contact::get()->map()
                )->setEmptyString(_t(
                    "OrdersAdmin.SelectACustomer",
                    "Select existing customer"
                )),
                TextField::create("Company"),
                TextField::create("FirstName"),
                TextField::create("Surname"),
                TextField::create("Address1"),
                TextField::create("Address2"),
                TextField::create("City"),
                TextField::create("PostCode"),
                DropdownField::create(
                    'Country',
                    _t('OrdersAdmin.Country', 'Country'),
                    i18n::getData()->getCountries()
                )->setEmptyString(""),
                TextField::create("Email"),
                TextField::create("PhoneNumber")
            ]
        );

        $fields->addFieldsToTab(
            "Root.Delivery",
            [
                HeaderField::create(
                    "DeliveryDetailsHeader",
                    _t("Orders.DeliveryDetails", "Delivery Details")
                ),
                TextField::create("DeliveryCompany"),
                TextField::create("DeliveryFirstName"),
                TextField::create("DeliverySurname"),
                TextField::create("DeliveryAddress1"),
                TextField::create("DeliveryAddress2"),
                TextField::create("DeliveryCity"),
                TextField::create("DeliveryPostCode"),
                DropdownField::create(
                    'DeliveryCountry',
                    _t('OrdersAdmin.Country', 'Country'),
                    i18n::getData()->getCountries()
                )->setEmptyString(""),

                // Postage
                HeaderField::create(
                    "PostageDetailsHeader",
                    _t("Orders.PostageDetails", "Postage Details")
                ),
                DropdownField::create(
                    "PostageID",
                    $this->fieldLabel("PostageID"),
                    $siteconfig->PostageAreas()->map()
                )->setEmptyString(_t(
                    "OrdersAdmin.SelectPostage",
                    "Select Postage"
                )),
                ReadonlyField::create("PostageDetails")
            ]
        );
        
        $root = $fields->findOrMakeTab("Root");

        if ($root) {
            $root->addextraClass('orders-root');
        }

        $this->extend("updateCMSFields", $fields);
        
        return $fields;
    }

        /**
     * Find the total discount based on discount items added.
     *
     * @return Float
     */
    protected function get_discount_amount()
    {
        $discount = $this->Discount();
        $total = 0;
        $discount_amount = 0;
        $items = $this->TotalItems;
        
        foreach ($this->Items() as $item) {
            if ($item->Price) {
                $total += ($item->Price * $item->Quantity);
            }
            
            if ($item->Price && $discount && $discount->Amount) {
                if ($discount->Type == "Fixed") {
                    $discount_amount = $discount_amount + ($discount->Amount / $items) * $item->Quantity;
                } elseif ($discount->Type == "Percentage") {
                    $discount_amount = $discount_amount + (($item->Price / 100) * $discount->Amount) * $item->Quantity;
                }
            }
        }

        if ($discount_amount > $total) {
            $discount_amount = $total;
        }

        $this->extend("augmentDiscountCalculation", $discount_amount);
        
        return $discount_amount;
    }

    /**
     * Retrieve an order prefix from siteconfig
     * for an Estimate
     *
     * @return string
     */
    protected function get_prefix()
    {
        $config = SiteConfig::current_site_config();
        return $config->EstimateNumberPrefix;
    }

    /**
     * Generate a randomised order number for this order.
     * 
     * The order number is generated based on the current order
     * ID and is padded to a multiple of 4 and we add "-" every
     * 4 characters.
     * 
     * We then add an order prefix (if one is set) or the current
     * year.
     * 
     * This keeps a consistent order number structure that allows
     * for a large number of orders before changing.
     *
     * @return string
     */
    protected function generate_order_number()
    {
        $length = strlen($this->ID);
        $i = $length;
        $prefix = $this->get_prefix();

        // Determine what the next multiple of 4 is
        while ($i % 4 != 0) {
            $i++;
        }

        $pad_amount = ($i >= 8) ? $i : 8;
        $id_base = str_pad($this->ID, $pad_amount, "0", STR_PAD_LEFT);
        $id_base = wordwrap($id_base, 4, "-", true);

        $current_date = new DateTime();

        // Work out if an order prefix string has been set
        if ($prefix) {
            $order_num = $prefix . '-' . $id_base;
        } else {
            $order_num = $current_date->format("Y") . "-" . $id_base;
        }

        return $order_num;
    }

    protected function generate_random_string($length = 20)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Check if the 
     *
     * @return boolean
     */
    protected function validOrderNumber()
    {
        $existing = Estimate::get()
            ->filterAny("OrderNumber", $this->OrderNumber)
            ->first();
        
        return !($existing);
    }

    /**
     * Create a duplicate of this order/estimate as well as duplicating
     * associated items
     *
     * @param $doWrite Perform a write() operation before returning the object.  If this is true, it will create the
     *                 duplicate in the database.
     * @return DataObject A duplicate of this node. The exact type will be the type of this node.
     */
    public function duplicate($doWrite = true, $manyMany = 'many_many')
    {
        $clone = parent::duplicate($doWrite, $manyMany);
        
        // Set up items
        if ($doWrite) {
            foreach ($this->Items() as $item) {
                $item_class = $item->class;
                $clone_item = new $item_class($item->toMap(), false, $this->model);
                $clone_item->ID = 0;
                $clone_item->ParentID = $clone->ID;
                $clone_item->write();
            }
        }
        
        $clone->invokeWithExtensions('onAfterDuplicate', $this, $doWrite);
        
        return $clone;
    }
    

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Is delivery address set, if not, set it here
        if (!$this->DeliveryAddress1 && !$this->DeliveryPostCode) {
            $this->DeliveryCompany = $this->Company;
            $this->DeliveryFirstnames = $this->FirstName;
            $this->DeliverySurname = $this->Surname;
            $this->DeliveryAddress1 = $this->Address1;
            $this->DeliveryAddress2 = $this->Address2;
            $this->DeliveryCity = $this->City;
            $this->DeliveryPostCode = $this->PostCode;
            $this->DeliveryCountry = $this->Country;
        }

        // Assign discount info if needed
        if ($this->Discount()->exists()) {
            $this->DiscountAmount = $this->get_discount_amount();
            $this->DiscountType = $this->Discount()->Title;
        }

        // Assign postage info if set
        if ($this->Postage()->exists()) {
            $postage = $this->Postage();
            $this->PostageType = $postage->Title;
            $this->PostageCost = $postage->Cost;
            $this->PostageTax = $postage->TaxAmount;
        }
    }

    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        // Check if an order number has been generated, if not, add it and save again
        if (!$this->OrderNumber) {
            $this->OrderNumber = $this->generate_order_number();
            
            while (!$this->validOrderNumber()) {
                $this->OrderNumber = $this->generate_order_number();
            }
            $this->write();
        }
    }

    /**
     * API Callback before this object is removed from to the DB
     *
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        
        // Delete all items attached to this order
        foreach ($this->Items() as $item) {
            $item->delete();
        }
    }

    public function providePermissions()
    {
        return [
            "ORDERS_VIEW_ESTIMATES" => [
                'name' => 'View any estimate',
                'help' => 'Allow user to view any estimate',
                'category' => 'Orders',
                'sort' => 89
            ],
            "ORDERS_CREATE_ESTIMATES" => [
                'name' => 'Create estimates',
                'help' => 'Allow user to create new estimates',
                'category' => 'Orders',
                'sort' => 88
            ],
            "ORDERS_EDIT_ESTIMATES" => [
                'name' => 'Edit any estimate',
                'help' => 'Allow user to edit any estimate',
                'category' => 'Orders',
                'sort' => 87
            ],
            "ORDERS_DELETE_ESTIMATES" => [
                'name' => 'Delete any estimate',
                'help' => 'Allow user to delete any estimate',
                'category' => 'Orders',
                'sort' => 86
            ]
        ];
    }

    /**
     * Only order creators or users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        
        if ($extended !== null) {
            return $extended;
        }

        if (!$member) {
            $member = Member::currentUser();
        }

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_VIEW_ESTIMATES"])) {
            return true;
        }

        return false;
    }

    /**
     * Anyone can create orders, even guest users
     *
     * @return Boolean
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        
        if ($extended !== null) {
            return $extended;
        }

        if (!$member) {
            $member = Member::currentUser();
        }
        
        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_CREATE_ESTIMATES"])) {
            return true;
        }

        return false;
    }

    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        
        if ($extended !== null) {
            return $extended;
        }

        if (!$member) {
            $member = Member::currentUser();
        }

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_EDIT_ESTIMATES"])) {
            return true;
        }

        return false;
    }

    /**
     * No one should be able to delete an order once it has been created
     *
     * @return Boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        
        if ($extended !== null) {
            return $extended;
        }

        if (!$member) {
            $member = Member::currentUser();
        }

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_DELETE_ESTIMATES"])) {
            return true;
        }

        return false;
    }
}
