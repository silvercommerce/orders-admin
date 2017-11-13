<?php

namespace ilateral\SilverStripe\Orders\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Inject extra requirements into the CMS
 * 
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders
 */
class AdminExtension extends Extension
{
    public function init()
    {
        Requirements::css('i-lateral/silverstripe-orders: client/dist/css/admin.css');
    }
}
