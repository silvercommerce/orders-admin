<?php

namespace SilverCommerce\OrdersAdmin\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Omnipay\Extensions\Payable;

class TestProduct extends DataObject implements TestOnly
{
    private static $db = [
        "Title" => "Varchar",
        "StockID" => "Varchar",
        "Price" => "Currency",
        "StockLevel" => "Int"
    ];
}
