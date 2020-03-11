<?php

namespace SilverCommerce\OrdersAdmin\Model;

use DateTime;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\FieldType\DBCurrency;
use SilverStripe\Security\PermissionProvider;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\TaxAdmin\Helpers\MathsHelper;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverCommerce\ContactAdmin\Model\ContactLocation;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverCommerce\OrdersAdmin\Control\DisplayController;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverCommerce\OrdersAdmin\Forms\GridField\AddLineItem;
use SilverCommerce\OrdersAdmin\Forms\GridField\ReadOnlyGridField;
use SilverCommerce\VersionHistoryField\Forms\VersionHistoryField;
use SilverStripe\Forms\CompositeField;
use SilverCommerce\OrdersAdmin\Compat\NumberMigrationTask;
use SilverCommerce\OrdersAdmin\Tasks\OrdersMigrationTask;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPRequest;
use SilverShop\HasOneField\HasOneButtonField;

/**
 * Represents an estimate (an unofficial quotation that has not yet been paid for)
 *
 */
class Estimate extends DataObject implements PermissionProvider
{
    private static $table_name = 'Estimate';

    /**
     * The amount of days that by default that this estimate
     * will end (cease to be valid).
     *
     * @var integer
     */
    private static $default_end = 30;

    /**
     * Standard DB columns
     *
     * @var array
     * @config
     */
    private static $db = [
        'Ref'               => 'Int',
        'Prefix'            => 'Varchar',
        'Number'            => 'Varchar',
        'StartDate'         => 'Date',
        'EndDate'           => 'Date',

        // Personal Details
        'Company'           => 'Varchar',
        'FirstName'         => 'Varchar',
        'Surname'           => 'Varchar',
        'Email'             => 'Varchar',
        'PhoneNumber'       => 'Varchar',
        
        // Billing Address
        'Address1'          => 'Varchar',
        'Address2'          => 'Varchar',
        'City'              => 'Varchar',
        'County'            => 'Varchar',
        'PostCode'          => 'Varchar',
        'Country'           => 'Varchar',
        
        // Delivery Details
        'DeliveryCompany'   => 'Varchar',
        'DeliveryFirstName' => 'Varchar',
        'DeliverySurname'   => 'Varchar',
        'DeliveryAddress1'  => 'Varchar',
        'DeliveryAddress2'  => 'Varchar',
        'DeliveryCity'      => 'Varchar',
        'DeliveryCounty'    => 'Varchar',
        'DeliveryPostCode'  => 'Varchar',
        'DeliveryCountry'   => 'Varchar',

        // Access key (for viewing via non logged in users)
        'AccessKey'         => "Varchar(40)"
    ];

    /**
     * Foreign key associations
     *
     * @var array
     * @config
     */
    private static $has_one = [
        'Customer'  => Contact::class
    ];

    /**
     * One to many assotiations
     *
     * @var array
     * @config
     */
    private static $has_many = [
        'Items'     => LineItem::class
    ];

    /**
     * Cast methods for templates
     *
     * @var array
     * @config
     */
    private static $casting = [
        'FullRef'           => 'Varchar(255)',
        "PersonalDetails"   => "Text",
        'BillingAddress'    => 'Text',
        'CountryFull'       => 'Varchar',
        'CountryUC'         => 'Varchar',
        'DeliveryAddress'   => 'Text',
        'DeliveryCountryFull'=> 'Varchar',
        'DeliveryCountryUC' => 'Varchar',
        'SubTotal'          => 'Currency(9,4)',
        'TaxTotal'          => 'Currency(9,4)',
        'Total'             => 'Currency(9,4)',
        'TotalItems'        => 'Int',
        'TotalWeight'       => 'Decimal',
        'ItemSummary'       => 'Text',
        'ItemSummaryHTML'   => 'HTMLText',
        'TranslatedStatus'  => 'Varchar'
    ];

    /**
     * Fields to show in summary views
     *
     * @var array
     * @config
     */
    private static $summary_fields = [
        'FullRef',
        'StartDate',
        'EndDate',
        'Company',
        'FirstName',
        'Surname',
        'Email',
        'PostCode',
        'Total',
        'LastEdited'
    ];

    /**
     * Fields to search
     *
     * @var array
     * @config
     */
    private static $searchable_fields = [
        'StartDate',
        'EndDate',
        'Company',
        'FirstName',
        'Surname',
        'Email',
        'PostCode',
        'LastEdited'
    ];

    /**
     * Human readable labels for fields
     *
     * @var array
     */
    private static $field_labels = [
        'FullRef'       => 'Ref',
        'StartDate'     => 'Date',
        'EndDate'       => 'Expires'
    ];

    /**
     * Fields to show in summary views
     *
     * @var array
     * @config
     */
    private static $export_fields = [
        "ID",
        "Prefix",
        "Ref",
        "Created",
        "StartDate",
        "EndDate",
        "ItemSummary",
        "SubTotal",
        "TaxTotal",
        "Total",
        "Company",
        "FirstName",
        "Surname",
        "Email",
        "PhoneNumber",
        "Address1",
        "Address2",
        "City",
        "PostCode",
        "Country",
        "County",
        "DeliveryCompany",
        "DeliveryFirstName",
        "DeliverySurname",
        "DeliveryAddress1",
        "DeliveryAddress2",
        "DeliveryCity",
        "DeliveryCountry",
        "DeliveryCounty",
        "DeliveryPostCode",
    ];

    /**
     * Add extension classes
     *
     * @var array
     * @config
     */
    private static $extensions = [
        Versioned::class . '.versioned',
    ];

    /**
     * Declare version history
     *
     * @var array
     * @config
     */
    private static $versioning = [
        "History"
    ];

    /**
     * Default sort order for ORM
     *
     * @var array
     * @config
     */
    private static $default_sort = [
        "Ref"       => "DESC",
        "StartDate" => "DESC"
    ];

    /**
     * Generate a link to view the associated front end
     * display for this order
     *
     * @return string
     */
    public function DisplayLink()
    {
        return Controller::join_links(
            DisplayController::create()->AbsoluteLink("estimate"),
            $this->ID,
            $this->AccessKey
        );
    }

    /**
     * Generate a link to view the associated front end
     * display for this order
     *
     * @return string
     */
    public function PDFLink()
    {
        return Controller::join_links(
            DisplayController::create()->AbsoluteLink("estimatepdf"),
            $this->ID,
            $this->AccessKey
        );
    }

    /**
     * Get the full reference number for this estimate/invoice.
     *
     * This is the stored prefix and ref
     *
     * @return string
     */
    public function getFullRef()
    {
        $config = SiteConfig::current_site_config();
        $length = $config->OrderNumberLength;
        $prefix = ($this->Prefix) ? $this->Prefix : "";
        $return = str_pad($this->Ref, $length, "0", STR_PAD_LEFT);

        // Work out if an order prefix string has been set
        if ($prefix) {
            $return = $prefix . '-' . $return;
        }

        return $return;
    }

    /**
     * Generate a string of the customer's personal details
     *
     * @return string
     */
    public function getPersonalDetails()
    {
        $return = [];

        if ($this->Company) {
            $return[] = $this->Company;
        }

        if ($this->FirstName) {
            $return[] = $this->FirstName;
        }

        if ($this->Surname) {
            $return[] = $this->Surname;
        }

        if ($this->Email) {
            $return[] = $this->Email;
        }

        if ($this->PhoneNumber) {
            $return[] = $this->PhoneNumber;
        }

        return implode(",\n", $return);
    }

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
        $country = strtolower($this->Country);
        return (array_key_exists($country, $list)) ? $list[$country] : $country;
    }

    /**
     * Get the uppercase name of this country
     *
     * @return string
     */
    public function getCountryUC()
    {
        return strtoupper($this->Country);
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
        $country = strtolower($this->DeliveryCountry);
        return (array_key_exists($country, $list)) ? $list[$country] : $country;
    }

    /**
     * Get the uppercase name of this country
     *
     * @return string
     */
    public function getDeliveryCountryUC()
    {
        return strtoupper($this->DeliveryCountry);
    }

    /**
     * Find the total quantity of items in the shopping cart
     *
     * @return int
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
            $product = $item->findStockItem();
            $weight = null;

            if (!empty($product) && isset($product->Weight)) {
                $weight = $product->Weight;
            }

            if ($weight && $item->Quantity) {
                $total = $total + ($weight * $item->Quantity);
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
        // We round here
        foreach ($items as $item) {
            $tax = $item->UnitTax;
            $total += $tax * $item->Quantity;
        }

        $this->extend("updateTaxTotal", $total);

        return $total;
    }

    /**
     * Get a list of all taxes used and an associated value
     *
     * @return ArrayList
     */
    public function getTaxList()
    {
        $taxes = ArrayList::create();

        foreach ($this->Items() as $item) {
            $existing = null;
            $rate = $item->TaxRate();

            if ($rate->exists()) {
                $existing = $taxes->find("ID", $rate->ID);
            }

            if (!$existing) {
                $currency = DBCurrency::create();
                $currency->setValue($item->getTaxTotal());
                $taxes->push(ArrayData::create([
                    "ID" => $rate->ID,
                    "Rate" => $rate,
                    "Total" => $currency
                ]));
            } elseif ($rate && $existing) {
                $existing->Total->setValue(
                    $existing->Total->getValue() + $item->getTaxTotal()
                );
            }
        }

        return $taxes;
    }

    /**
     * Total value of order
     *
     * @return float
     */
    public function getTotal()
    {
        $total = $this->SubTotal + $this->TaxTotal;

        $this->extend("updateTotal", $total);
        
        return $total;
    }

    /**
     * Factory method to convert this estimate to an
     * order.
     *
     * This method writes and reloads the object so
     * we are now working with the new object type
     *
     * @return Invoice
     */
    public function convertToInvoice()
    {
        $id = $this->ID;
        $this->ClassName = Invoice::class;
        $this->write();

        // Get our new Invoice
        $record = Invoice::get()->byID($id);
        $record->Ref = null;
        $record->Prefix = null;
        $record->StartDate = null;
        $record->EndDate = null;
        $record->write();

        return $record;
    }

    /**
     * Return a list string summarising each item in this order
     *
     * @return string
     */
    public function getItemSummary()
    {
        $return = [];

        foreach ($this->Items() as $item) {
            $return[] = "{$item->Quantity} x {$item->Title}";
        }

        $this->extend("updateItemSummary", $return);

        return implode("\n", $return);
    }

    /**
     * Return a list string summarising each item in this order
     *
     * @return string
     */
    public function getItemSummaryHTML()
    {
        $html = nl2br($this->ItemSummary);
        
        $this->extend("updateItemSummaryHTML", $html);

        return $html;
    }

    /**
     * Determine if the current estimate contains delivereable
     * items.
     *
     * @return boolean
     */
    public function isDeliverable()
    {
        foreach ($this->Items() as $item) {
            if ($item->Deliverable) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine if the current estimate contains only locked items.
     *
     * @return boolean
     */
    public function isLocked()
    {
        foreach ($this->getItems() as $item) {
            if (!$item->Locked) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Scaffold CMS form fields
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $self = $this;
   
        $this->beforeUpdateCMSFields(function ($fields) use ($self) {
            $fields->removeByName("StartDate");
            $fields->removeByName("CustomerID");
            $fields->removeByName("EndDate");
            $fields->removeByName("Number");
            $fields->removeByName("Ref");
            $fields->removeByName("AccessKey");
            $fields->removeByName("Items");
            $fields->removeByName("Prefix");
            
            $fields->addFieldsToTab(
                "Root.Main",
                [
                    // Items field
                    ReadOnlyGridField::create(
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
                    
                    // Totals and settings
                    CompositeField::create(
                        CompositeField::create(
                            DateField::create("StartDate", _t("OrdersAdmin.Date", "Date")),
                            DateField::create("EndDate", _t("OrdersAdmin.Expires", "Expires")),
                            ReadonlyField::create("FullRef", "#"),
                            TextField::create("Ref", $this->fieldLabel("Ref"))
                        )->setName("OrdersDetailsInfo")
                        ->addExtraClass("col"),
                        CompositeField::create([])
                            ->setName("OrdersDetailsMisc")
                        ->addExtraClass("col"),
                        CompositeField::create(
                            ReadonlyField::create("SubTotalValue", _t("OrdersAdmin.SubTotal", "Sub Total"))
                                ->setValue($this->obj("SubTotal")->Nice()),
                            ReadonlyField::create("TaxValue", _t("OrdersAdmin.Tax", "Tax"))
                                ->setValue($this->obj("TaxTotal")->Nice()),
                            ReadonlyField::create("TotalValue", _t("OrdersAdmin.Total", "Total"))
                                ->setValue($this->obj("Total")->Nice())
                        )->setName("OrdersDetailsTotals")
                        ->addExtraClass("col")
                    )->setName("OrdersDetails")
                    ->addExtraClass("orders-details-field")
                    ->setColumnCount(2)
                    ->setFieldHolderTemplate("SilverCommerce\\OrdersAdmin\\Forms\\OrderDetailsField_holder")
                ]
            );

            $fields->addFieldsToTab(
                "Root.Customer",
                [
                    HasOneButtonField::create(
                        $this,
                        'Customer'
                    ),
                    TextField::create("Company"),
                    TextField::create("FirstName"),
                    TextField::create("Surname"),
                    TextField::create("Address1"),
                    TextField::create("Address2"),
                    TextField::create("City"),
                    TextField::create("County"),
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
                    TextField::create("DeliveryCounty"),
                    TextField::create("DeliveryPostCode"),
                    DropdownField::create(
                        'DeliveryCountry',
                        _t('OrdersAdmin.Country', 'Country'),
                        i18n::getData()->getCountries()
                    )->setEmptyString("")
                ]
            );

            $fields->addFieldToTab(
                "Root.History",
                VersionHistoryField::create(
                    "History",
                    _t("SilverCommerce\VersionHistoryField.History", "History"),
                    $self
                )->addExtraClass("stacked")
            );
            
            $root = $fields->findOrMakeTab("Root");

            if ($root) {
                $root->addextraClass('orders-root');
            }
        });
        
        return parent::getCMSFields();
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        $run_migration = OrdersMigrationTask::config()->run_during_dev_build;

        if ($run_migration) {
            $request = Injector::inst()->get(HTTPRequest::class);
            OrdersMigrationTask::create()->run($request);
        }
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
     * Get a base ID for the last Estimate in the DataBase
     *
     * @return int
     */
    protected function getBaseNumber()
    {
        $base = 0;
        $prefix = $this->get_prefix();
        $classname = $this->ClassName;

        // Get the last instance of the current class
        $last = $classname::get()
            ->filter("ClassName", $classname)
            ->sort("Ref", "DESC")
            ->first();

        // If we have a last estimate/invoice, get the ID of the last invoice
        // so we can increment
        if (isset($last)) {
            $base = str_replace($prefix, "", $last->Ref);
            $base = (int)str_replace("-", "", $base);
        }

        // Increment base
        $base++;

        return $base;
    }

    /**
     * legacy method name - soon to be depreciated
     *
     */
    protected function generate_order_number()
    {
        return $this->generateOrderNumber();
    }

    /**
     * Generate an incremental estimate / invoice number.
     *
     * We then add an order prefix (if one is set).
     *
     * @return string
     */
    protected function generateOrderNumber()
    {
        $base_number = $this->getBaseNumber();

        while (!$this->validOrderNumber($base_number)) {
            $base_number++;
        }

        return $base_number;
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
     * Check if the currently generated order number
     * is valid (not duplicated)
     *
     * @return boolean
     */
    protected function validOrderNumber($number = null)
    {
        $number = (isset($number)) ? $number : $this->Ref;

        $existing = Estimate::get()
            ->filter(
                [
                    "ClassName" => self::class,
                    "Ref" => $number
                ]
            )->first();

        return !($existing);
    }

    /**
     * Check if the access key generated for this estimate is
     * valid (exists on another object)
     *
     * @return boolean
     */
    protected function validAccessKey()
    {
        $existing = Estimate::get()
            ->filter("AccessKey", $this->AccessKey)
            ->first();
        
        return !($existing);
    }

    /**
     * Create a duplicate of this order/estimate as well as duplicating
     * associated items
     *
     * @param bool $doWrite Perform a write() operation before returning the object.
     * @param array|null|false $relations List of relations to duplicate.
     * @return DataObject A duplicate of this node. The exact type will be the type of this node.
     */
    public function duplicate($doWrite = true, $relations = null)
    {
        $clone = parent::duplicate($doWrite, $relations);
        
        // Set up items
        if ($doWrite) {
            $clone->Ref = "";
            $clone->Prefix = "";
            $clone->write();

            foreach ($this->Items() as $item) {
                $item_class = $item->ClassName;
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

        // Ensure that this object has a non-conflicting Access Key
        if (!$this->AccessKey) {
            $this->AccessKey = $this->generate_random_string(40);
            
            while (!$this->validAccessKey()) {
                $this->AccessKey = $this->generate_random_string(40);
            }
        }


        // Set a prefix if required
        if (!$this->Prefix) {
            $this->Prefix = $this->get_prefix();
        }

        $contact = $this->Customer();

        // If a contact is assigned and no customer details set
        // then use contact details
        if (!$this->PersonalDetails && $contact->exists()) {
            foreach (Config::inst()->get(Contact::class, "db") as $param => $value) {
                $this->$param = $contact->$param;
            }
        }

        // if Billing Address is not set, use customer's default
        // location
        if (!$this->BillingAddress && $contact->exists() && $contact->DefaultLocation()) {
            $location = $contact->DefaultLocation();
            foreach (Config::inst()->get(ContactLocation::class, "db") as $param => $value) {
                $this->$param = $location->$param;
            }
        }


        // Is delivery address set, if not, set it here
        if (!$this->DeliveryAddress && $this->BillingAddress) {
            $this->DeliveryCompany = $this->Company;
            $this->DeliveryFirstName = $this->FirstName;
            $this->DeliverySurname = $this->Surname;
            $this->DeliveryAddress1 = $this->Address1;
            $this->DeliveryAddress2 = $this->Address2;
            $this->DeliveryCity = $this->City;
            $this->DeliveryPostCode = $this->PostCode;
            $this->DeliveryCountry = $this->Country;
        }

        // If date not set, make this equal the created date
        if (!$this->StartDate) {
            $this->StartDate = $this->LastEdited;
        }

        if (!$this->EndDate && $this->StartDate) {
            $start = new DateTime($this->StartDate);
            $start->modify("+ {$this->config()->default_end} days");
            $this->EndDate = $start->format("Y-m-d");
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
        if (!$this->Ref) {
            $this->Ref = $this->generateOrderNumber();
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
     * @return boolean
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
     * @return boolean
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
     * @return boolean
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
     * @return boolean
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
