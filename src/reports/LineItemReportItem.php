<?php

namespace SilverCommerce\OrdersAdmin\Reports;

use SilverStripe\View\ViewableData;

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
