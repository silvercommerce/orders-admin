<?php

namespace SilverCommerce\OrdersAdmin\Model;

use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\i18n\i18n;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\OrdersAdmin\Forms\GridField\AddLineItem;
use SilverCommerce\OrdersAdmin\Forms\GridField\LineItemGridField;
use SilverCommerce\ContactAdmin\Model\Contact;

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
 * @author ilateral (http://www.ilateral.co.uk)
 */
class Invoice extends Estimate implements PermissionProvider
{

    private static $table_name = 'Invoice';

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
     * What statuses does an order need to be marked as "outstanding".
     * At the moment this is only used against an @Member.
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
     * What statuses does an order need to be marked as "historic".
     * At the moment this is only used against an @Member.
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
     * The status which an order is considered "complete".
     * 
     * @var string
     * @config
     */
    private static $completion_status = "paid";

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

    /**
     * Actions on an order are to determine what will happen on
     * completion (the defaults are post or collect).
     * 
     * @var array
     * @config
     */
    private static $actions = [
        "post" => "Post",
        "collect" => "Collect"
    ];

    /**
     * Set the default action on our order. If we were using this module
     * for a more POS type solution, we would probably change this to
     * collect.
     * 
     * @var string
     * @config
     */
    private static $default_action = "post";

    private static $db = [
        'Status'            => "Varchar",
        "Action"            => "Varchar"
    ];

    private static $casting = [
        'TranslatedStatus'  => 'Varchar'
    ];

    private static $summary_fields = [
        "Status"        => "Status",
        "Action"        => "Action",
    ];

    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->Status = $this->config()->get("default_status");
        $this->Action = $this->config()->get("default_action");
    }

    /**
     * Scaffold admin form feilds
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Go through a convoluted process to find out field group
        // as the default fieldlist doesn't seem to register it!
        $main = $fields->findOrMakeTab("Root.Main");
        $sidebar = null;

        foreach ($main->getChildren() as $field) {
            if ($field->getName() == "OrdersSidebar") {
                $sidebar = $field;
            }
        }

        if ($sidebar) {
            $sidebar->setTitle(_t(
                "OrdersAdmin.InvoiceDetails",
                "Invoice Details"
            ));
            $sidebar->insertBefore(
                "OrderNumber",
                DropdownField::create(
                    'Status',
                    null,
                    $this->config()->get("statuses")
                )
            );

            $sidebar->insertBefore(
                "OrderNumber",
                DropdownField::create(
                    'Action',
                    null,
                    $this->config()->get("actions")
                )
            );
        }

        
        $this->extend("updateCMSFields", $fields);

        return $fields;
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
     * Mark this order as "complete" which generally is intended
     * to mean "paid for, ready for processing".
     *
     * @return Order
     */
    public function markComplete()
    {
        $this->Status = $this->config()->get("completion_status");
        return $this;
    }

    /**
     * Mark this order as "paid"
     *
     * @param string $reference the unique reference from the gateway
     * @return Order
     */
    public function markPaid()
    {
        $this->Status = $this->config()->get("paid_status");
        return $this;
    }

    /**
     * Mark this order as "part paid".
     *
     * @return Order
     */
    public function markPartPaid()
    {
        $this->Status = $this->config()->get("part_paid_status");
        return $this;
    }

    /**
     * Mark this order as "pending" (awaiting payment to clear/reconcile).
     *
     * @return Order
     */
    public function markPending()
    {
        $this->Status = $this->config()->get("pending_status");
        return $this;
    }

    /**
     * Mark this order as "canceled".
     *
     * @return Order
     */
    public function markCanceled()
    {
        $this->Status = $this->config()->get("canceled_status");
        return $this;
    }

    /**
     * Mark this order as "refunded".
     *
     * @return Order
     */
    public function markRefunded()
    {
        $this->Status = $this->config()->get("refunded_status");
        return $this;
    }

    /**
     * Mark this order as "dispatched".
     *
     * @return Order
     */
    public function markDispatched()
    {
        $this->Status = $this->config()->get("dispatched_status");
        return $this;
    }

    /**
     * Mark this order as "collected".
     *
     * @return Order
     */
    public function markCollected()
    {
        $this->Status = $this->config()->get("collected_status");
        return $this;
    }

    protected function validOrderNumber()
    {
        $existing = Invoice::get()
            ->filterAny("OrderNumber", $this->OrderNumber)
            ->first();
        
        return !($existing);
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
        return $config->InvoiceNumberPrefix;
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

        if (!$this->Action) {
            $this->Action = $this->config()->get("default_action");
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
            $notifications = Notification::get()
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

        if (
            $member &&
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
