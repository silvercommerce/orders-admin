<?php

namespace SilverCommerce\OrdersAdmin\Notifications;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Core\Injector\Injector;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use ilateral\SilverStripe\Notifier\Model\Notification;

class InvoiceNotification extends Notification
{
    private static $table_name = "Notifications_InvoiceNotification";

    public function compileSuitableBaseClasses(): array
    {
        $base_classes = ClassInfo::subclassesFor(Invoice::class, true);
        $return = [];

        foreach (array_values($base_classes) as $classname) {
            /** @var DataObject */
            $obj = Injector::inst()->get($classname, true);
            $return[$classname] = $obj->i18n_singular_name();
        }

        return $return;
    }

    public function getCMSFields()
    {
        $self = $this;

        $this->beforeUpdateCMSFields(
            function ($fields) use ($self) {
                /** @var FieldList $fields */
                $fields->replaceField(
                    'BaseClassName',
                    DropdownField::create(
                        'BaseClassName',
                        $self->fieldLabel('BaseClassName'),
                        $self->compileSuitableBaseClasses()
                    )
                );
            }
        );

        return parent::getCMSFields();
    }
}
