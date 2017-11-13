<?php

namespace ilateral\SilverStripe\Orders\Model;

use SilverStripe\ORM\DataObject;

/**
 * A single customisation that can be applied to an OrderItem.
 * 
 * A customisation by default allows the following details:
 *  - Title: The name of the customisation (eg. "Colour")
 *  - Value: The data associated with thie customisation (eg. "Red")
 *  - Price: Does this customisation change the OrderItem's price?
 */
class OrderItemCustomisation extends DataObject
{
    private static $table_name = 'OrderItemCustomisation';

    /**
     * Standard database columns
     *
     * @var array
     * @config
     */
    private static $db = [
        "Title" => "Varchar",
        "Value" => "Text",
        "Price" => "Currency"
    ];

    /**
     * DB foreign key associations
     *
     * @var array
     * @config
     */
    private static $has_one = [
        "OrderItem" => OrderItem::class
    ];

    /**
     * Fields to display in gridfields
     *
     * @var array
     * @config
     */
    private static $summary_fields = [
        "Title",
        "Value",
        "Price"
    ];
}