<?php

namespace SilverCommerce\OrdersAdmin\Admin;

use DateTime;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\DateField;
use Colymba\BulkManager\BulkManager;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverCommerce\OrdersAdmin\BulkManager\PaidHandler;
use ilateral\SilverStripe\ModelAdminPlus\ModelAdminPlus;
use SilverCommerce\OrdersAdmin\BulkManager\BulkDownloadHandler;
use SilverCommerce\OrdersAdmin\BulkManager\CancelHandler;
use SilverCommerce\OrdersAdmin\BulkManager\RefundHandler;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverCommerce\OrdersAdmin\BulkManager\PendingHandler;
use SilverCommerce\OrdersAdmin\BulkManager\BulkViewHandler;
use SilverCommerce\OrdersAdmin\BulkManager\PartPaidHandler;
use SilverCommerce\OrdersAdmin\BulkManager\DispatchedHandler;
use SilverCommerce\OrdersAdmin\BulkManager\ProcessingHandler;
use SilverCommerce\OrdersAdmin\Forms\GridField\OrdersDetailForm;
use SilverStripe\Forms\GridField\GridFieldConfig;

 /**
  * Add interface to manage orders through the CMS
  *
  * @package Commerce
  */
class OrderAdmin extends ModelAdminPlus
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

    private static $allowed_actions = [
        "SearchForm"
    ];

    /**
     * Listen for customised export fields on the currently managed object
     *
     * @return array
     */
    public function getExportFields()
    {
        $model = singleton($this->modelClass);
        if ($model->hasMethod('getExportFields')) {
            return $model->getExportFields();
        }

        return parent::getExportFields();
    }

    public function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        /**
         * Add custom sorting for support of FullRef
         *
         * @var GridFieldSortableHeader $headers
         */
        $headers = $config->getComponentByType(GridFieldSortableHeader::class);
        if ($headers) {
            $sorting = $headers->getFieldSorting();
            $sorting['FullRef'] = 'Ref';
            $headers->setFieldSorting($sorting);
        }

        /** @var BulkManager  */
        $manager = $config->getComponentByType(BulkManager::class);

        // Manage orders
        if ($this->modelClass == Invoice::class) {
            $manager->addBulkAction(CancelHandler::class);
            $manager->addBulkAction(RefundHandler::class);
            $manager->addBulkAction(PendingHandler::class);
            $manager->addBulkAction(PartPaidHandler::class);
            $manager->addBulkAction(PaidHandler::class);
            $manager->addBulkAction(ProcessingHandler::class);
            $manager->addBulkAction(DispatchedHandler::class);
        }

        $manager->addBulkAction(BulkViewHandler::class);
        $manager->addBulkAction(BulkDownloadHandler::class);

        // Overide default detailform
        $config
            ->removeComponentsByType(GridFieldDetailForm::class)
            ->addComponent(new OrdersDetailForm());

        return $config;
    }
    
    public function getList()
    {
        $list = parent::getList();
        $query = $this->getSearchData();
        $db = DB::get_conn();

        $start = null;
        $end = null;
        $date_filter = null;
        
        // Ensure that we only show Order objects in the order tab
        if ($this->modelClass == Estimate::class) {
            $list = $list
                ->addFilter(["ClassName" => Estimate::class]);
        }

        if ($query && array_key_exists("Start", $query)) {
            $start = new DateTime($query["Start"]);
        }

        if ($query && array_key_exists("End", $query)) {
            $end = new DateTime($query["End"]);
        }

        $format = "%Y-%m-%d";
        $start_field = $db->formattedDatetimeClause(
            '"Estimate"."StartDate"',
            $format
        );
        $end_field = $db->formattedDatetimeClause(
            '"Estimate"."EndDate"',
            $format
        );

        if ($start && $end) {
            $date_filter = [
                $start_field . ' <= ?' =>  $end->format("Y-m-d"),
                $start_field . ' >= ?' =>  $start->format("Y-m-d"),
                $end_field . ' >= ?' =>  $start->format("Y-m-d"),
                $end_field . ' <= ?' =>  $end->format("Y-m-d")
            ];
        } elseif ($start && !$end) {
            $date_filter = [
                $start_field . ' <= ?' =>  $start->format("Y-m-d"),
                $end_field . ' >= ?' =>  $start->format("Y-m-d")
            ];
        }

        if ($date_filter) {
            $list = $list->where($date_filter);
        }
                
        $this->extend("updateList", $list);

        return $list;
    }
}
