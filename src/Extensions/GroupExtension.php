<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use SilverStripe\ORM\DB;

/**
 * Overwrite group object and setup default groups
 *
 * @package orders-admin
 */
class GroupExtension extends DataExtension
{

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
