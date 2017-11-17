<?php

namespace ilateral\SilverStripe\Orders\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\FieldType\DBCurrency as Currency;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Dev\DevBuildController;
use ilateral\SilverStripe\Orders\Control\ShoppingCart;
use ilateral\SilverStripe\Orders\Checkout;
use ilateral\SilverStripe\Orders\Tasks\OrdersMigrationTask;


/**
 * Extension for Content Controller that provide methods such as cart
 * link and category list to templates
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders
 */
class ControllerExtension extends Extension
{
    /**
     * Get the current shoppingcart
     * 
     * @return ShoppingCart
     */
    public function getShoppingCart()
    {
        return ShoppingCart::get();
    }

    /**
     * Get the checkout config
     * 
     * @return ShoppingCart
     */
    public function getCheckout()
    {
        return Checkout::create();
    }

    public function onBeforeInit()
    {
        // Set the default currency symbol for this site
        Currency::config()->currency_symbol = Checkout::config()->currency_symbol;
    }

    /**
     * Add migration task call after dev/build has completed
     *
     * @param $request
     * @param $action
     * @param $result
     * @return void
     */
    public function afterCallActionHandler($request, $action, $result)
    {
        $curr_controller = Controller::curr();
        $run_task = Config::inst()->get(OrdersMigrationTask::class, "run_during_dev_build");

        if ($curr_controller instanceof DevBuildController && $run_task == true) {
            $task = OrdersMigrationTask::create();
            $task->up();
        }
    }
}
