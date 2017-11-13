<?php

namespace ilateral\SilverStripe\Orders\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\ORM\DB;
use ilateral\SilverStripe\Orders\Model\Discount;

/**
 * Overwrite group object so we can setup default groups
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders
 */
class GroupExtension extends DataExtension
{
    private static $belongs_many_many = array(
        "Discounts" => Discount::class
    );

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // Add default author group if no other group exists
        $curr_group = Group::get()->filter("Code", "customers");

        if (!$curr_group->exists()) {
            $group = Group::create();
            $group->Code = 'customers';
            $group->Title = "Customers";
            $group->Sort = 1;
            $group->write();

            DB::alteration_message('Customers group created', 'created');
        }
    }
}
