<?php

namespace SilverCommerce\OrdersAdmin\Control;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\View\Requirements;

/**
 * Controller responsible for displaying either an rendered order or a
 * rendered quote that can be emailed or printed.
 * 
 * @package Orders
 */
class DisplayController extends Controller
{
    /**
     * ClassName of Order object 
     *
     * @var string
     * @config
     */
    private static $url_segment = "ordersadmin/display";
    
    
    private static $allowed_actions = array(
        "invoice",
        "estimate"
    );

    protected function init()
    {
        parent::init();

        Requirements::css('silverstripe/admin: client/dist/styles/bundle.css');
        Requirements::css('silvercommerce/orders-admin: client/dist/css/display.css');
    }
    
    /**
     * Get a relative link to anorder or invoice
     * 
     * NOTE: this controller will always require an ID of an order and
     * access key to be passed (as well as an action). 
     * 
     * @param $action Action we would like to view.
     * @param $id ID or the order we want to view.
     * @param $key Access key of the order (for security).
     * @return string
     */
    public function Link($action = "invoice") {
        return Controller::join_links(
            $this->config()->url_segment,
            $action
        );
    }
    
    /**
     * Get an absolute link to an order or invoice
     * 
     * NOTE: this controller will always require an ID of an order and
     * access key to be passed (as well as an action). 
     * 
     * @param $action Action we would like to view.
     * @param $id ID or the order we want to view.
     * @param $key Access key of the order (for security).
     * @return string
     */
    public function AbsoluteLink($action = "invoice") {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $this->Link($action)
        );
    }

    public function invoice()
    {
        $member = Member::currentUser();
        $object = Invoice::get()
            ->byID($this->getrequest()->param("ID"));

        if ($object && (
            ($member && $object->canView($member)) ||
            ($object->AccessKey && $object->AccessKey == $this->request->param("OtherID"))
        )) {
            return $this
                ->customise(array(
                    "Type" => "Invoice",
                    "SiteConfig" => SiteConfig::current_site_config(),
                    "MetaTitle" => _t("Orders.InvoiceTitle", "Invoice"),
                    "Object" => $object
                ))->renderWith(["\\Orders\\Control\\DisplayController"]);
        } else {
            return $this->httpError(404);
        }
    }
    
    public function estimate()
    {
        $member = Member::currentUser();
        $object = Estimate::get()
            ->byID($this->request->param("ID"));

        if ($object && (
            ($member && $object->canView($member)) ||
            ($object->AccessKey && $object->AccessKey == $this->request->param("OtherID"))
        )) {
            return $this
                ->customise(array(
                    "Type" => "Estimate",
                    "SiteConfig" => SiteConfig::current_site_config(),
                    "MetaTitle" => _t("Orders.QuoteTitle", "Quote"),
                    "Object" => $object
                ))->renderWith(["\\Orders\\Control\\DisplayController"]);
        } else {
            return $this->httpError(404);
        }
    }
}
