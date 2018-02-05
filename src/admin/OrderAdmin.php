<?php

namespace SilverCommerce\OrdersAdmin\Admin;

use SilverStripe\Admin\ModelAdmin;
use Colymba\BulkManager\BulkManager;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverCommerce\OrdersAdmin\Forms\GridField\OrdersDetailForm;
use SilverCommerce\OrdersAdmin\Forms\GridField\BulkActions as OrdersBulkActions;

use SilverStripe\Dev\Debug;
 /**
  * Add interface to manage orders through the CMS
  *
  * @package Commerce
  */
class OrdersAdmin extends ModelAdmin
{

    private static $url_segment = 'sales';

    private static $menu_title = 'Sales';

    private static $menu_priority = 4;

    private static $menu_icon_class = 'font-icon-book-open';
    
    private static $managed_models = [
        Invoice::class,
        Estimate::class
    ];

    private static $model_importers = [];

    public $showImportForm = [];

    /**
     * For an order, export all fields by default
     * 
     */
    public function getExportFields()
    {
        if ($this->modelClass == Invoice::class) {
            $return = array(
                "OrderNumber"       => "#",
                "Status"            => "Status",
                "Created"           => "Created",
                "Company"           => "Company Name",
                "FirstName"         => "First Name(s)",
                "Surname"           => "Surname",
                "Email"             => "Email",
                "PhoneNumber"       => "Phone Number",
                "ItemSummary"       => "Items Ordered",
                "SubTotal"          => "SubTotal",
                "Postage"           => "Postage",
                "TaxTotal"          => "TaxTotal",
                "Total"             => "Total",
                "Address1"          => "Billing Address 1",
                "Address2"          => "Billing Address 2",
                "City"              => "Billing City",
                "PostCode"          => "Billing Post Code",
                "CountryFull"       => "Billing Country",
                "DeliveryFirstnames"=> "Delivery First Name(s)",
                "DeliverySurname"   => "Delivery Surname",
                "DeliveryAddress1"  => "Delivery Address 1",
                "DeliveryAddress2"  => "Delivery Address 2",
                "DeliveryCity"      => "Delivery City",
                "DeliveryPostCode"  => "Delivery Post Code",
                "DeliveryCountryFull"=> "Delivery Country",
                "DiscountAmount"    => "Discount Amount",
                "PostageType"       => "Postage Type",
                "PostageCost"       => "Postage Cost",
                "PostageTax"        => "Postage Tax",
            );
        } else {
            $return = singleton($this->modelClass)->summaryFields();
        }

        $this->extend("updateExportFields", $return);

        return $return;
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        $gridfield = $fields
            ->fieldByName($this->sanitiseClassName($this->modelClass));
        $config = null;
        
        // Bulk manager
        $manager = new BulkManager();
        $manager->removeBulkAction("bulkEdit");
        $manager->removeBulkAction("unLink");

        // Manage orders
        if ($this->modelClass == Invoice::class && $gridfield) {
            $config = $gridfield->getConfig();

            $manager->addBulkAction(
                'cancelled',
                'Mark Cancelled',
                OrdersBulkActions::class
            );
            
            $manager->addBulkAction(
                'refunded',
                'Mark Refunded',
                OrdersBulkActions::class
            );

            $manager->addBulkAction(
                'pending',
                'Mark Pending',
                OrdersBulkActions::class
            );

            $manager->addBulkAction(
                'partpaid',
                'Mark Part Paid',
                OrdersBulkActions::class
            );
            
            $manager->addBulkAction(
                'paid',
                'Mark Paid',
                OrdersBulkActions::class
            );

            $manager->addBulkAction(
                'processing',
                'Mark Processing',
                OrdersBulkActions::class
            );

            $manager->addBulkAction(
                'dispatched',
                'Mark Dispatched',
                OrdersBulkActions::class
            );
        }
        
        // Manage Estimates
        if ($this->modelClass == Estimate::class) {
            $config = $gridfield->getConfig();
        }
        
        // Set our default detailform and bulk manager
        if ($config) {
            $config
                ->removeComponentsByType(GridFieldDetailForm::class)
                ->addComponent($manager)
                ->addComponent(new OrdersDetailForm());
        }

        $this->extend("updateEditForm", $form);

        return $form;
    }
    
    public function getList()
    {
        $list = parent::getList();
        
        // Ensure that we only show Order objects in the order tab
        if ($this->modelClass == Estimate::class) {
            $list = $list
                ->addFilter(array("ClassName" => Estimate::class));
        }
                
        $this->extend("updateList", $list);

        return $list;
    }
}
