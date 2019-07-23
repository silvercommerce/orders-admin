<?php

namespace SilverCommerce\OrdersAdmin\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestProduct extends DataObject implements TestOnly
{
    private static $db = [
        "Title" => "Varchar",
        "StockID" => "Varchar",
        "Price" => "Currency",
        "StockLevel" => "Int",
        "Weight" => "Decimal"
    ];
}
