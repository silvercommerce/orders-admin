<?php

namespace SilverCommerce\OrdersAdmin\Reports;

use SilverStripe\Reports\Report;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime as SSDateTime;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\View\ViewableData;
use SilverCommerce\OrdersAdmin\Model\Invoice;

// Only load this if reports are active
if (class_exists(Report::class)) {
    class LineItemReport extends Report
    {
        
        public function title()
        {
            return _t("Orders.LineItems", "Items ordered");
        }

        public function description()
        {
            return _t("Orders.LineItemReportDescription", "View all individual products ordered through this site");
        }

        public function columns()
        {
            return [
                "StockID" => "StockID",
                "Details" => "Details",
                "Price" => "Price",
                "Quantity" => "Quantity"
            ];
        }

        public function exportColumns()
        {
            // Loop through all colls and replace BR's with spaces
            $cols = [];

            foreach ($this->columns() as $key => $value) {
                $cols[$key] = $value;
            }

            return $cols;
        }

        public function sortColumns()
        {
            return [];
        }

        public function getReportField()
        {
            $gridField = parent::getReportField();

            // Edit CSV export button
            $export_button = $gridField->getConfig()->getComponentByType('GridFieldExportButton');
            $export_button->setExportColumns($this->exportColumns());

            return $gridField;
        }

        public function sourceRecords($params, $sort, $limit)
        {
            $return = ArrayList::create();

            // Check filters
            $where_filter = [];

            $where_filter[] = (isset($params['Filter_Year'])) ? "YEAR(\"Created\") = '{$params['Filter_Year']}'" : "YEAR(\"Created\") = '".date('Y')."'";
            if (!empty($params['Filter_Month'])) {
                $where_filter[] = "Month(\"Created\") = '{$params['Filter_Month']}'";
            }
            if (!empty($params['Filter_Status'])) {
                $where_filter[] = "Status = '{$params['Filter_Status']}'";
            }
            if (!empty($params['Filter_FirstName'])) {
                $where_filter[] = "FirstName = '{$params['Filter_FirstName']}'";
            }
            if (!empty($params['Filter_Surname'])) {
                $where_filter[] = "Surname = '{$params['Filter_Surname']}'";
            }

            $orders = Invoice::get()
                ->where(implode(' AND ', $where_filter));

            foreach ($orders as $order) {
                // Setup a filter for our order items
                $filter = [];

                if (!empty($params['Filter_ProductName'])) {
                    $filter["Title:PartialMatch"] = $params['Filter_ProductName'];
                }

                if (!empty($params['Filter_StockID'])) {
                    $filter["StockID"] = $params['Filter_StockID'];
                }

                $list = (count($filter)) ? $order->Items()->filter($filter) : $order->Items();

                foreach ($list as $order_item) {
                    if ($order_item->StockID) {
                        if ($list_item = $return->find("StockID", $order_item->StockID)) {
                            $list_item->Quantity = $list_item->Quantity + $order_item->Quantity;
                        } else {
                            $report_item = LineItemReportItem::create();
                            $report_item->ID = $order_item->StockID;
                            $report_item->StockID = $order_item->StockID;
                            $report_item->Details = $order_item->Title;
                            $report_item->Price = $order_item->Price;
                            $report_item->Quantity = $order_item->Quantity;

                            $return->add($report_item);
                        }
                    }
                }
            }

            return $return;
        }

        public function parameterFields()
        {
            $fields = new FieldList();

            $first_order = Invoice::get()
                ->sort('Created', 'ASC')
                ->first();

            // Check if any order exist
            if ($first_order) {
                // List all months
                $months = ['All'];
                for ($i = 1; $i <= 12; $i++) {
                    $months[] = date("F", mktime(0, 0, 0, $i + 1, 0, 0));
                }

                // Get the first order, then count down from current year to that
                $firstyear = new SSDatetime('FirstDate');
                $firstyear->setValue($first_order->Created);
                $years = [];
                for ($i = date('Y'); $i >= $firstyear->Year(); $i--) {
                    $years[$i] = $i;
                }

                // Order Status
                $statuses = Invoice::config()->statuses;
                array_unshift($statuses, 'All');

                $fields->push(TextField::create(
                    'Filter_FirstName',
                    'Customer First Name'
                ));
                
                $fields->push(TextField::create(
                    'Filter_Surname',
                    'Customer Surname'
                ));
                
                $fields->push(TextField::create(
                    'Filter_StockID',
                    'Stock ID'
                ));
                
                $fields->push(TextField::create(
                    'Filter_ProductName',
                    'Product Name'
                ));
                
                $fields->push(DropdownField::create(
                    'Filter_Month',
                    'Month',
                    $months
                ));
                
                $fields->push(DropdownField::create(
                    'Filter_Year',
                    'Year',
                    $years
                ));
                
                $fields->push(DropdownField::create(
                    'Filter_Status',
                    'Order Status',
                    $statuses
                ));
            }

            return $fields;
        }
    }
}

/**
 * Item that can be loaded into an LineItem report
 *
 */
class LineItemReportItem extends ViewableData
{
    public $ClassName = "LineItemReportItem";

    public $StockID;
    public $Details;
    public $Price;
    public $Quantity;

    public function canView($member = null)
    {
        return true;
    }
}
