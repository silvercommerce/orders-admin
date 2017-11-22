<?php

namespace ilateral\SilverStripe\Orders\Model;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use ilateral\SilverStripe\Orders\Forms\GridField\AddLineItem;
use ilateral\SilverStripe\Orders\Forms\GridField\LineItemGridField;
use ilateral\SilverStripe\Orders\Forms\OrderSidebar;
use ilateral\SilverStripe\Orders\Forms\CustomerSidebar;
use ilateral\SilverStripe\Orders\Forms\GridField\MapExistingAction;

class Estimate extends Invoice
{
    private static $table_name = 'Estimate';

    /**
     * Standard DB columns
     *
     * @var array
     * @config
     */
    private static $db = array(
        "Cart"      => "Boolean"
    );

    /**
     * Fields to show in summary views
     *
     * @var array
     * @config
     */
    private static $summary_fields = array(
        "ID"        => "#",
        'Company'   => 'Company',
        'FirstName' => 'First Name',
        'Surname'   => 'Surname',
        "Total"     => "Total",
        "Created"   => "Created",
        "LastEdited"=> "Last Edited"
    );

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
    
    public function getCMSFields()
    {
        $existing_customer = $this->config()->existing_customer_class;
        
        $fields = new FieldList(
            $tab_root = new TabSet(
                "Root",
                
                // Main Tab Fields
                $tab_main = new Tab(
                    'Main',
                    
                    // Items field
                    new LineItemGridField(
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
                    
                    // Postage
                    new HeaderField(
                        "PostageDetailsHeader",
                        _t("Orders.PostageDetails", "Postage Details")
                    ),
                    TextField::create("PostageType"),
                    TextField::create("PostageCost"),
                    TextField::create("PostageTax"),
                    
                    // Discount
                    new HeaderField(
                        "DiscountDetailsHeader",
                        _t("Orders.DiscountDetails", "Discount")
                    ),
                    TextField::create("Discount"),
                    TextField::create("DiscountAmount"),
                    
                    // Sidebar
                    FieldGroup::create(
                        ReadonlyField::create("QuoteNumber", "#")
                            ->setValue($this->ID),
                        ReadonlyField::create("SubTotalValue",_t("Orders.SubTotal", "Sub Total"))
                            ->setValue($this->obj("SubTotal")->Nice()),
                        ReadonlyField::create("DiscountValue",_t("Orders.Discount", "Discount"))
                            ->setValue($this->dbObject("DiscountAmount")->Nice()),
                        ReadonlyField::create("PostageValue",_t("Orders.Postage", "Postage"))
                            ->setValue($this->obj("Postage")->Nice()),
                        ReadonlyField::create("TaxValue",_t("Orders.Tax", "Tax"))
                            ->setValue($this->obj("TaxTotal")->Nice()),
                        ReadonlyField::create("TotalValue",_t("Orders.Total", "Total"))
                            ->setValue($this->obj("Total")->Nice())
                    )->setTitle(_t("Orders.EstimateDetails", "Estimate Details"))
                    ->addExtraClass("order-admin-sidebar")
                ),
                
                // Main Tab Fields
                $tab_customer = new Tab(
                    'Customer',
                    TextField::create("Company"),
                    TextField::create("FirstName"),
                    TextField::create("Surname"),
                    TextField::create("Address1"),
                    TextField::create("Address2"),
                    TextField::create("City"),
                    TextField::create("PostCode"),
                    TextField::create("Country"),
                    TextField::create("Email"),
                    TextField::create("PhoneNumber")
                )
            )
        );
        
		$tab_root->addextraClass('orders-root');
        $tab_main->addExtraClass("order-admin-items");
        $tab_customer->addExtraClass("order-admin-customer");

        $this->extend("updateCMSFields", $fields);
        
        return $fields;
    }
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        $this->Status = $this->config()->get("default_status");
    }
}
