<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Inject extra requirements into the CMS
 * 
 * @package orders-admin
 */
class AdminExtension extends Extension
{
    public function init()
    {
        Requirements::css('silvercommerce/orders-admin: client/dist/css/admin.css');
    }
}
