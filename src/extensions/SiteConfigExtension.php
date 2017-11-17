<?php

namespace ilateral\SilverStripe\Orders\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;

class SiteConfigExtension extends DataExtension
{
    
    private static $db = [
        "OrdersHeader" => "HTMLText",
        "QuoteFooter" => "HTMLText",
        "InvoiceFooter" => "HTMLText",
        "PaymentNumberPrefix" => "Varchar(6)",
        'PaymentSuccessContent' => 'Text',
        'PaymentFailerContent'  => 'Text'
    ];
    
    private static $has_many = [
        "OrderNotifications"=> OrderNotification::class,
        'PostageAreas'      => PostageArea::class,
        'Discounts'         => Discount::class
    ];
    
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            "Root.Orders",
            GridField::create(
                "OrderNotifications",
                "Order status notifications",
                $this->owner->OrderNotifications(),
                GridFieldConfig_RecordEditor::create()
            )
        );
        
        $fields->addFieldToTab(
            "Root.Orders",
            HTMLEditorField::create("OrdersHeader", _t("Orders.QuoteInvoiceHeader", "Quote and Invoice Header"))
        );
        
        $fields->addFieldToTab(
            "Root.Orders",
            HTMLEditorField::create("QuoteFooter")
        );
        
        $fields->addFieldToTab(
            "Root.Orders",
            HTMLEditorField::create("InvoiceFooter")
        );

        // setup compressed payment options
        $payment_fields = ToggleCompositeField::create(
            'PaymentSettings',
            _t("CheckoutAdmin.Payments", "Payment Settings"),
            [
                TextField::create(
                    'PaymentNumberPrefix',
                    _t("CheckoutAdmin.OrderPrefix", "Add prefix to order numbers"),
                    null,
                    9
                )->setAttribute(
                    "placeholder",
                    _t("CheckoutAdmin.OrderPrefixPlaceholder", "EG 'abc'")
                ),
                
                TextareaField::create(
                    'PaymentSuccessContent',
                    _t("CheckoutAdmin.PaymentSuccessContent", "Payment successfull content")
                )->setRows(4)
                ->setColumns(30)
                ->addExtraClass('stacked'),
                
                TextareaField::create(
                    'PaymentFailerContent',
                    _t("CheckoutAdmin.PaymentFailerContent", "Payment failer content")
                )->setRows(4)
                ->setColumns(30)
                ->addExtraClass('stacked')
            ]
        );

        // Add html description of how to edit contries
        $country_html = "<div class=\"field\">";
        $country_html .= "<p>First select valid countries using the 2 character ";
        $country_html .= "shortcode (see http://fasteri.com/list/2/short-names-of-countries-and-iso-3166-codes).</p>";
        $country_html .= "<p>You can add multiple countries seperating them with";
        $country_html .= "a comma or use a '*' for all countries.</p>";
        $country_html .= "</div>";

        $country_html_field = LiteralField::create("CountryDescription", $country_html);

        // Deal with product features
        $postage_field = GridField::config(
            'PostageAreas',
            '',
            $this->owner->PostageAreas(),
            GridFieldConfig::create()
                ->addComponents(
                    new GridFieldButtonRow('before'),
                    new GridFieldToolbarHeader(),
                    new GridFieldTitleHeader(),
                    new GridFieldEditableColumns(),
                    new GridFieldDeleteAction(),
                    new GridFieldAddNewInlineButton('toolbar-header-left')
                )
        );

        // Add country dropdown to inline editing
        $postage_field
            ->getConfig()
            ->getComponentByType('GridFieldEditableColumns')
            ->setDisplayFields(array(
                'Title' => array(
                    'title' => 'Title',
                    'field' => 'TextField'
                ),
                'Country' => array(
                    'title' => 'ISO 3166 codes',
                    'field' => 'TextField'
                ),
                'ZipCode' => array(
                    'title' => 'Zip/Post Codes',
                    'field' => 'TextField'
                ),
                'Calculation'  => array(
                    'title' => 'Base unit',
                    'callback' => function ($record, $column, $grid) {
                        return DropdownField::create(
                            $column,
                            "Based on",
                            singleton('PostageArea')
                                ->dbObject('Calculation')
                                ->enumValues()
                        )->setValue("Weight");
                    }
                ),
                'Unit' => array(
                    'title' => 'Unit (equals or above)',
                    'field' => 'NumericField'
                ),
                'Cost' => array(
                    'title' => 'Cost',
                    'field' => 'NumericField'
                ),
                'Tax' => array(
                    'title' => 'Tax (percentage)',
                    'field' => 'NumericField'
                )
            ));

        // Setup compressed postage options
        $postage_fields = ToggleCompositeField::create(
            'PostageFields',
            'Postage Options',
            array(
                $country_html_field,
                $postage_field
            )
        );


        // Setup compressed postage options
        $discount_fields = ToggleCompositeField::create(
            'DiscountFields',
            'Discounts',
            array(
                GridField::create(
                    'Discounts',
                    '',
                    $this->owner->Discounts(),
                    GridFieldConfig_RecordEditor::create()
                )
            )
        );

        // Add config sets
        $fields->addFieldToTab('Root.Checkout', $payment_fields);
        $fields->addFieldToTab('Root.Checkout', $postage_fields);
        $fields->addFieldToTab('Root.Checkout', $discount_fields);
    }
}