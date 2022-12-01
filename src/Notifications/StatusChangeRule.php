<?php

use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use ilateral\SilverStripe\Notifier\Model\NotificationRule;

/**
 * Specific notification rule dedicated to only monitoring the status of
 * an object
 */
class StatusChangeRule extends NotificationRule
{
    private static $table_name = 'Notifications_StatusChangeRule';

    /**
     * Overwrite list of valid field names
     *
     * @return array
     */
    public function getValidFields(): array
    {
        $fields = [];
        $class = $this->Notification()->BaseClassName;

        if (!empty($class) && class_exists($class)) {
            $obj = singleton($class);
            $fields = ['Status' => $obj->fieldLabel('Status')];
        }

        return $fields;
    }

    public function getPossibleStatuses()
    {
        $class = $this->Notification()->BaseClassName;
        return Config::inst()->get($class, 'statuses');
    }

    public function getCMSFields()
    {
        $self = $this;
        $this->beforeUpdateCMSFields(
            function (FieldList $fields) use ($self) {
                $fields->removeByName('WasChanged');

                $fields->replaceField(
                    'Value',
                    DropdownField::create(
                        'Value',
                        $this->fieldLabel('Value'),
                        $this->getPossibleStatuses()
                    )
                );
            }
        );

        return parent::getCMSFields();
    }
}
