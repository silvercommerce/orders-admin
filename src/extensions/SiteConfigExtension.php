<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Assets\Image;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverCommerce\OrdersAdmin\Model\Notification as OrderNotification;
use SilverCommerce\OrdersAdmin\Model\PostageArea;
use SilverCommerce\OrdersAdmin\Model\Discount;

/**
 * Add additional settings to the default siteconfig 
 */
class SiteConfigExtension extends DataExtension
{
    
    private static $db = [
        "EstimateNumberPrefix" => "Varchar(10)",
        "InvoiceNumberPrefix" => "Varchar(10)",
        "OrderNumberLength" => "Int",
        "InvoiceHeaderContent" => "HTMLText",
        "InvoiceFooterContent" => "HTMLText",
        "EstimateHeaderContent" => "HTMLText",
        "EstimateFooterContent" => "HTMLText"
    ];
    
    private static $has_one = [
        "EstimateInvoiceLogo" => Image::class
    ];

    private static $has_many = [
        "OrderNotifications"=> OrderNotification::class,
        'PostageAreas'      => PostageArea::class,
        'Discounts'         => Discount::class
    ];

    private static $defaults = [
        "OrderNumberLength" => 4
    ];
    
    public function updateCMSFields(FieldList $fields)
    {
        // Postage Options
        $country_html = "<div class=\"field\">";
        $country_html .= "<p>First select valid countries using the 2 character ";
        $country_html .= "shortcode (see http://fasteri.com/list/2/short-names-of-countries-and-iso-3166-codes).</p>";
        $country_html .= "<p>You can add multiple countries seperating them with";
        $country_html .= "a comma or use a '*' for all countries.</p>";
        $country_html .= "</div><br/>";

        $country_html_field = LiteralField::create("CountryDescription", $country_html);
        
        $postage_fields = ToggleCompositeField::create(
            'PostageSettings',
            _t("Orders.PostageSettings", "Postage Settings"),
            [
                $country_html_field,
                GridField::create(
                    'PostageAreas',
                    '',
                    $this->owner->PostageAreas()
                )->setConfig(GridFieldConfig_RecordEditor::create())
            ]
        );

        // Discount options
        $discount_fields = ToggleCompositeField::create(
            'DiscountSettings',
            _t("Orders.DiscountSettings", "Discount Settings"),
            [
                LiteralField::create("DiscountPadding", "<br/>"),
                GridField::create(
                    'Discounts',
                    '',
                    $this->owner->Discounts()
                )->setConfig(GridFieldConfig_RecordEditor::create())
            ]
        );

        // Order Notifications
        $notification_fields = ToggleCompositeField::create(
            'OrderNotificationSettings',
            _t("Orders.OrderNotificationSettings", "Order Notification Settings"),
            [
                LiteralField::create("NotificationPadding", "<br/>"),
                GridField::create(
                    "OrderNotifications",
                    "Order status notifications",
                    $this->owner->OrderNotifications()
                )->setConfig(GridFieldConfig_RecordEditor::create())
            ]
        );

        // Invoice Customisation
        $invoice_customisation_fields = ToggleCompositeField::create(
            'InvoiceQuoteCustomSettings',
            _t("Orders.InvoiceQuoteCustomisation", "Invoice and Quote Customisation"),
            [
                UploadField::create('EstimateInvoiceLogo'),
                TextField::create(
                    'EstimateNumberPrefix',
                    _t("Orders.EstimatePrefix", "Add prefix to estimate numbers"),
                    null,
                    9
                )->setAttribute(
                    "placeholder",
                    _t("Orders.OrderPrefixPlaceholder", "EG 'uk-123'")
                ),
                TextField::create(
                    'InvoiceNumberPrefix',
                    _t("Orders.InvoicePrefix", "Add prefix to invoice numbers"),
                    null,
                    9
                )->setAttribute(
                    "placeholder",
                    _t("Orders.OrderPrefixPlaceholder", "EG 'es-123'")
                ),
                NumericField::create(
                    "OrderNumberLength",
                    $this->owner->fieldLabel("OrderNumberLength")
                )->setDescription(_t(
                    "Orders.OrderNumberLengthDescription",
                    "The default length invoice/estimate numbers are padded to"
                )),
                HTMLEditorField::create("InvoiceHeaderContent")
                    ->addExtraClass("stacked"),
                    HTMLEditorField::create("InvoiceFooterContent")
                    ->addExtraClass("stacked"),
                HTMLEditorField::create("EstimateHeaderContent")
                    ->addExtraClass("stacked"),
                HTMLEditorField::create("EstimateFooterContent")
                    ->addExtraClass("stacked")
            ]
        );

        // Add config sets
        $fields->addFieldsToTab(
            'Root.Shop',
            [
                $postage_fields,
                $discount_fields,
                $notification_fields,
                $invoice_customisation_fields
            ]
        );
    }

    public function onBeforeWrite()
    {
        if (!$this->owner->OrderNumberLength) {
            $this->owner->OrderNumberLength = 4;
        }
    }
}
