<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\OrdersAdmin\Control\DisplayController;
use SilverStripe\Forms\TextField;

/**
 * Order objects track all the details of an order and if they were completed or
 * not.
 *
 * Makes use of permissions provider to lock out users who have not got the
 * relevent COMMERCE permissions for:
 *   VIEW
 *   EDIT
 *   DELETE
 *
 * You can define an order prefix by using the "order_prefix" config
 * variable
 *
 * Any user can create an order (this allows us to support "guest" users).
 *
 */
class Invoice extends Estimate implements PermissionProvider
{

    private static $table_name = 'Invoice';

    /**
     * The amount of days that by default that this estimate
     * will end (cease to be valid).
     *
     * @var integer
     */
    private static $default_end = 0;

    /**
     * List of possible statuses this order can have. Rather than using
     * an enum, we load this as a config variable that can be changed
     * more freely.
     *
     * @var array
     * @config
     */
    private static $statuses = [
        "incomplete" => "Incomplete",
        "failed" => "Failed",
        "cancelled" => "Cancelled",
        "pending" => "Pending",
        "part-paid" => "Part Paid",
        "paid" => "Paid",
        "processing" => "Processing",
        "ready" => "Ready",
        "dispatched" => "Dispatched",
        "collected" => "Collected",
        "refunded" => "Refunded"
    ];

    /**
     * What statuses does an invoice need to be considered
     * "outstanding" (meaning not dispatched or complete]).
     *
     * @var array
     * @config
     */
    private static $outstanding_statuses = [
        "part-paid",
        "paid",
        "processing"
    ];

    /**
     * What statuses does an order need to be considered
     * "historic" (meaning dispatched/completed, etc)
     *
     *
     * @var array
     * @config
     */
    private static $historic_statuses = [
        "dispatched",
        "collected",
        "canceled"
    ];

    /**
     * What statuses are considered "paid for". Meaning
     * they are complete, ready for processing, etc.
     *
     * @var array
     * @config
     */
    private static $paid_statuses = [
        "paid",
        "processing",
        "ready",
        "dispatched",
        "collected"
    ];

    /**
     * List of statuses that allow editing of an order. We can use this
     * to fix certain orders in the CMS
     *
     * @var array
     * @config
     */
    private static $editable_statuses = [
        "",
        "incomplete",
        "pending",
        "part-paid",
        "paid",
        "failed",
        "cancelled"
    ];

    /**
     * Set the default status for a new order, if this is set to null or
     * blank, it will not be used.
     *
     * @var string
     * @config
     */
    private static $default_status = "incomplete";

    /**
     * The status which an order has been marked pending
     * (meaning we are awaiting payment).
     *
     * @var string
     * @config
     */
    private static $pending_status = "pending";

    /**
     * The status which an order is considered "paid" (meaning
     * ready for processing, dispatch, etc).
     *
     * @var string
     * @config
     */
    private static $paid_status = "paid";

    /**
     * The status which an order is considered "part paid" (meaning
     * partially paid, possibly deposit paid).
     *
     * @var string
     * @config
     */
    private static $part_paid_status = "part-paid";

    /**
     * The status which an order has not been completed (meaning
     * it is not ready for processing, dispatch, etc).
     *
     * @var string
     * @config
     */
    private static $incomplete_status = "incomplete";

    /**
     * The status which an order is being processed
     *
     * @var string
     * @config
     */
    private static $processing_status = "processing";

    /**
     * The status which an order has been canceled.
     *
     * @var string
     * @config
     */
    private static $canceled_status = "canceled";

    /**
     * The status which an order has been refunded.
     *
     * @var string
     * @config
     */
    private static $refunded_status = "refunded";

    /**
     * The status which an order has been dispatched
     * (sent to customer).
     *
     * @var string
     * @config
     */
    private static $dispatched_status = "dispatched";
    
    /**
     * The status which an order has been marked collected
     * (meaning goods collected from store).
     *
     * @var string
     * @config
     */
    private static $collected_status = "collected";

    private static $db = [
        'Status'            => "Varchar"
    ];

    private static $casting = [
        'TranslatedStatus'  => 'Varchar'
    ];

    private static $summary_fields = [
        "Status"        => "Status"
    ];

    /**
     * Fields to search
     *
     * @var array
     * @config
     */
    private static $searchable_fields = [
        'Status'
    ];

    /**
     * Fields to use when exporting from `OrdersAdmin`
     *
     * @var array
     * @config
     */
    private static $export_fields = [
        "Status"
    ];

    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->Status = $this->config()->get("default_status");
    }

    /**
     * Generate a translated variant of the current status
     *
     * @return string
     */
    public function getTranslatedStatus()
    {
        return _t(self::class . "." . $this->Status, $this->Status);
    }

    /**
     * Overwrite default search fields to add a dropdown
     * for status
     *
     * Used by {@link SearchContext}.
     *
     * @param array $_params
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function scaffoldSearchFields($_params = null)
    {
        $fields = parent::scaffoldSearchFields($_params);

        // Update the classname field if set
        $status_field = $fields->dataFieldByName('Status');

        if (!empty($status_field) && is_a($status_field, TextField::class)) {
            $statuses = self::config()->get('statuses');
            $fields->replaceField(
                'Status',
                DropdownField::create(
                    'Status',
                    $this->fieldLabel('Status')
                )->setSource($statuses)
                ->setEmptyString('')
            );
        }

        return $fields;
    }


    /**
     * Generate a link to view the associated front end
     * display for this order
     *
     * @return string
     */
    public function DisplayLink()
    {
        return Controller::join_links(
            DisplayController::create()->AbsoluteLink(),
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
            DisplayController::create()->AbsoluteLink('pdf'),
            $this->ID,
            $this->AccessKey
        );
    }

    /**
     * Scaffold admin form feilds
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            // Go through a convoluted process to find out field group
            // as the default fieldlist doesn't seem to register it!
            $main = $fields->findOrMakeTab("Root.Main");
            $details = null;

            $enddate_field = $fields->dataFieldByName("EndDate");
            $enddate_field->setTitle(_t("OrdersAdmin.Due", "Due"));

            // Manually loop through fields to find info composite field, as
            // fieldByName cannot reliably find this.
            foreach ($main->getChildren() as $field) {
                if ($field->getName() == "OrdersDetails") {
                    foreach ($field->getChildren() as $field) {
                        if ($field->getName() == "OrdersDetailsInfo") {
                            $details = $field;
                        }
                    }
                }
            }

            if ($details) {
                $details->insertBefore(
                    "StartDate",
                    DropdownField::create(
                        'Status',
                        null,
                        $this->config()->get("statuses")
                    )
                );
            }
        });

        return parent::getCMSFields();
    }

    /**
     * Has this order been paid for? We determine this
     * by checking one of the pre-defined "paid_statuses"
     * in the config variable:
     *
     *   # Order.paid_statuses
     *
     * @return boolean
     */
    public function isPaid()
    {
        $statuses = $this->config()->get("paid_statuses");

        if (!is_array($statuses)) {
            return $this->Status == $statuses;
        } else {
            return in_array($this->Status, $statuses);
        }
    }

    /**
     * Mark this order as "paid"
     *
     * @param string $reference the unique reference from the gateway
     * @return self
     */
    public function markPaid()
    {
        $this->Status = $this->config()->get("paid_status");
        return $this;
    }

    /**
     * Mark this order as "part paid".
     *
     * @return self
     */
    public function markPartPaid()
    {
        $this->Status = $this->config()->get("part_paid_status");
        return $this;
    }

    /**
     * Mark this order as "pending" (awaiting payment to clear/reconcile).
     *
     * @return self
     */
    public function markPending()
    {
        $this->Status = $this->config()->get("pending_status");
        return $this;
    }

        /**
     * Mark this order as "pending" (awaiting payment to clear/reconcile).
     *
     * @return self
     */
    public function markProcessing()
    {
        $this->Status = $this->config()->get("processing_status");
        return $this;
    }

    /**
     * Mark this order as "canceled".
     *
     * @return self
     */
    public function markCanceled()
    {
        $this->Status = $this->config()->get("canceled_status");
        return $this;
    }

    /**
     * Mark this order as "refunded".
     *
     * @return self
     */
    public function markRefunded()
    {
        $this->Status = $this->config()->get("refunded_status");
        return $this;
    }

    /**
     * Mark this order as "dispatched".
     *
     * @return self
     */
    public function markDispatched()
    {
        $this->Status = $this->config()->get("dispatched_status");
        return $this;
    }

    /**
     * Mark this order as "collected".
     *
     * @return self
     */
    public function markCollected()
    {
        $this->Status = $this->config()->get("collected_status");
        return $this;
    }
    
    /**
     * @depreciated This method is depreciated
     *
     * @return string
     */
    protected function get_prefix()
    {
        return $this->getPrefix();
    }
    
    /**
     * Retrieve an order prefix from siteconfig
     * for an Invoice
     *
     * @return string
     */
    protected function getPrefix(): string
    {
        $config = SiteConfig::current_site_config();
        return (string)$config->InvoiceNumberPrefix;
    }

    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        if (!$this->Status) {
            $this->Status = $this->config()->get("default_status");
        }
    }

    /**
     * API Callback after this object is written to the DB
     *
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        // Deal with sending the status emails
        if ($this->isChanged('Status')) {
            $config = SiteConfig::current_site_config();
            $notifications = $config
                ->InvoiceNotifications()
                ->filter("Status", $this->Status);

            // Loop through available notifications and send
            foreach ($notifications as $notification) {
                $notification->sendNotification($this);
            }
        }
    }

    public function providePermissions()
    {
        return [
            "ORDERS_VIEW_INVOICES" => [
                'name' => 'View any invoice',
                'help' => 'Allow user to view any invoice',
                'category' => 'Orders',
                'sort' => 99
            ],
            "ORDERS_CREATE_INVOICES" => [
                'name' => 'Create invoices',
                'help' => 'Allow user to create new invoices',
                'category' => 'Orders',
                'sort' => 98
            ],
            "ORDERS_STATUS_INVOICES" => [
                'name' => 'Change status of any invoice',
                'help' => 'Allow user to change the status of any invoice',
                'category' => 'Orders',
                'sort' => 97
            ],
            "ORDERS_EDIT_INVOICES" => [
                'name' => 'Edit any invoice',
                'help' => 'Allow user to edit any invoice',
                'category' => 'Orders',
                'sort' => 96
            ],
            "ORDERS_DELETE_INVOICES" => [
                'name' => 'Delete any invoice',
                'help' => 'Allow user to delete any invoice',
                'category' => 'Orders',
                'sort' => 95
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

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_VIEW_INVOICES"])) {
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
        
        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_CREATE_INVOICES"])) {
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

        if ($member &&
            Permission::checkMember($member->ID, ["ADMIN", "ORDERS_EDIT_INVOICES"]) &&
            in_array($this->Status, $this->config()->editable_statuses)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canChangeStatus($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        
        if ($extended !== null) {
            return $extended;
        }

        if (!$member) {
            $member = Member::currentUser();
        }

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_STATUS_INVOICES"])) {
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

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "ORDERS_DELETE_INVOICES"])) {
            return true;
        }

        return false;
    }
}
