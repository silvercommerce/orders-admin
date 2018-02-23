<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\ArrayData;
use ilateral\SilverStripe\Users\Control\AccountController;

/**
 * Add extra fields to a user account (if the users module is
 * installed) to allow logged in users to see their invoices.
 * 
 * @package orders
 */
class AccountControllerExtension extends Extension
{
    /**
     * Add extra URL endpoints
     *
     * @var array
     */
    private static $allowed_actions = [
        "history",
        "outstanding"
    ];

    public function updateIndexSections($sections)
    {
        $member = Security::getCurrentUser();

        $outstanding = $member->OutstandingInvoices()->limit(5);

        $sections->push(ArrayData::create([
            "Title" => _t('Orders.OutstandingOrders', 'Outstanding Orders'),
            "Content" => $this->owner->renderWith(
                "SilverCommerce\\OrdersAdmin\\Includes\\OrdersList",
                ["List" => $outstanding]
            )
        ]));

        $historic = $member->HistoricInvoices()->limit(5);

        $sections->push(ArrayData::create([
            "Title" => _t('Orders.OrderHistory', 'Order History'),
            "Content" => $this->owner->renderWith(
                "SilverCommerce\\OrdersAdmin\\Includes\\OrdersList",
                ["List" => $historic]
            )
        ]));
    }
    
    /**
     * Display all historic orders for the current user
     *
     * @return HTMLText
     */
    public function history()
    {
        $member = Security::getCurrentUser();
        $list = PaginatedList::create(
            $member->HistoricInvoices(),
            $this->owner->getRequest()
        );

        $this
            ->owner
            ->customise([
                "Title" => _t('Orders.OrderHistory', 'Order History'),
                "MenuTitle" => _t('Orders.OrderHistory', 'Order History'),
                "Content" => $this->owner->renderWith(
                    "SilverCommerce\\OrdersAdmin\\Includes\\OrdersList",
                    ["List" => $list]
                )
            ]);

        $this->owner->extend("updateHistoricOrders", $orders);

        return $this
            ->owner
            ->renderWith([
                'AccountController_history',
                AccountController::class . '_history',
                'AccountController',
                AccountController::class,
                'Page'
            ]);
    }

    /**
     * Display all outstanding orders for the current user
     *
     * @return HTMLText
     */
    public function outstanding()
    {
        $member = Security::getCurrentUser();
        $list = PaginatedList::create(
            $member->OutstandingInvoices(),
            $this->owner->getRequest()
        );

        $this->owner->customise([
            "Title" => _t('Orders.OutstandingOrders', 'Outstanding Orders'),
            "MenuTitle" => _t('Orders.OutstandingOrders', 'Outstanding Orders'),
            "Content" => $this->owner->renderWith(
                "SilverCommerce\\OrdersAdmin\\Includes\\OrdersList",
                ["List" => $list]
            )
        ]);

        $this->owner->extend("updateOutstandingOrders", $orders);

        return $this
            ->owner
            ->renderWith([
                'AccountController_outstanding',
                AccountController::class . '_outstanding',
                'AccountController',
                AccountController::class,
                'Page'
            ]);
    }

    /**
     * Add commerce specific links to account menu
     *
     * @param ArrayList $menu
     */
    public function updateAccountMenu($menu)
    {
        $curr_action = $this
            ->owner
            ->getRequest()
            ->param("Action");
        
        $menu->add(ArrayData::create([
            "ID"    => 1,
            "Title" => _t('Orders.OutstandingOrders', 'Outstanding Orders'),
            "Link"  => $this->owner->Link("outstanding"),
            "LinkingMode" => ($curr_action == "outstanding") ? "current" : "link"
        ]));

        $menu->add(ArrayData::create([
            "ID"    => 2,
            "Title" => _t('Orders.OrderHistory', "Order history"),
            "Link"  => $this->owner->Link("history"),
            "LinkingMode" => ($curr_action == "history") ? "current" : "link"
        ]));
    }
}