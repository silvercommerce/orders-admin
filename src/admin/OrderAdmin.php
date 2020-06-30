<?php

namespace SilverCommerce\OrdersAdmin\Admin;

use DateTime;
use SilverStripe\ORM\DB;
use SilverStripe\Forms\DateField;
use Colymba\BulkManager\BulkManager;
use SilverStripe\Core\Config\Config;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use Colymba\BulkManager\BulkAction\EditHandler;
use Colymba\BulkManager\BulkAction\UnlinkHandler;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use ilateral\SilverStripe\ModelAdminPlus\ModelAdminPlus;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverCommerce\CatalogueAdmin\BulkManager\PaidHandler;
use SilverCommerce\CatalogueAdmin\BulkManager\CancelHandler;
use SilverCommerce\CatalogueAdmin\BulkManager\RefundHandler;
use SilverCommerce\CatalogueAdmin\BulkManager\PendingHandler;
use SilverCommerce\CatalogueAdmin\BulkManager\PartPaidHandler;
use SilverCommerce\CatalogueAdmin\BulkManager\DispatchedHandler;
use SilverCommerce\CatalogueAdmin\BulkManager\ProcessingHandler;
use SilverCommerce\OrdersAdmin\Forms\GridField\OrdersDetailForm;

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

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();
        $gridfield = $fields
            ->fieldByName($this->sanitiseClassName($this->modelClass));
        $config = $gridfield->getConfig();
        
        // Adding custom sorting for support of FullRef
        $headers = $config->getComponentByType(GridFieldSortableHeader::class);
        if ($headers) {
            $sorting = $headers->getFieldSorting();
            $sorting['FullRef'] = 'Ref';
            $headers->setFieldSorting($sorting);
        }

        // Bulk manager
        $manager = $config->getComponentByType(BulkManager::class);

        // Manage orders
        if ($this->modelClass == Invoice::class && $gridfield) {
            $manager->addBulkAction(CancelHandler::class);
            $manager->addBulkAction(RefundHandler::class);
            $manager->addBulkAction(PendingHandler::class);
            $manager->addBulkAction(PartPaidHandler::class);
            $manager->addBulkAction(PaidHandler::class);
            $manager->addBulkAction(ProcessingHandler::class);
            $manager->addBulkAction(DispatchedHandler::class);
        }
        
        // Set our default detailform and bulk manager
        if ($config) {
            $config
                ->removeComponentsByType(GridFieldDetailForm::class)
                ->addComponent(new OrdersDetailForm());
        }

        $this->extend("updateEditForm", $form);

        return $form;
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

    public function SearchForm()
    {
        $form = parent::SearchForm();
        $fields = $form->Fields();
        $data = $this->getSearchData();
        $singleton = Estimate::singleton();

        // Replace the start field
        $fields->replaceField(
            "StartDate",
            DateField::create(
                "Start",
                $singleton->fieldLabel("StartDate")
            )
        );

        // Replace the start field
        $fields->replaceField(
            "EndDate",
            DateField::create(
                "End",
                $singleton->fieldLabel("EndDate")
            )
        );

        $form->loadDataFrom($data);

        return $form;
    }
}
