<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\Forms\NumericField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Assets\Image;
use SilverCommerce\OrdersAdmin\Model\Notification as InvoiceNotification;

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
        "InvoiceNotifications"=> InvoiceNotification::class
    ];

    private static $defaults = [
        "OrderNumberLength" => 4
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Order Notifications
        $notification_fields = ToggleCompositeField::create(
            'InvoiceNotificationSettings',
            _t("Orders.InvoiceNotificationSettings", "Invoice Notification Settings"),
            [
                LiteralField::create("NotificationPadding", "<br/>"),
                GridField::create(
                    "InvoiceNotifications",
                    $this->owner->fieldLabel("InvoiceNotifications"),
                    $this->owner->InvoiceNotifications()
                )->setConfig(GridFieldConfig_RecordEditor::create())
            ]
        );

        // Invoice Customisation
        $invoice_customisation_fields = ToggleCompositeField::create(
            'InvoiceEstimateCustomSettings',
            _t("Orders.InvoiceEstimateCustomisation", "Invoice and Estimate Customisation"),
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
