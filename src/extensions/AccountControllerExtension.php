<?php

namespace SilverCommerce\OrdersAdmin\Extensions;

use SilverStripe\i18n\i18n;
use SilverStripe\Forms\Form;
use SilverStripe\Core\Extension;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\Security;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationResult;
use SilverCommerce\ContactAdmin\Model\ContactLocation;
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
        "outstanding",
        "addresses",
        "addaddress",
        "editaddress",
        "removeaddress",
        "AddressForm"
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
     * Form used for adding or editing addresses
     * 
     * @return Form
     */
    public function AddressForm()
    {
        $form = Form::create(
            $this->owner,
            "AddressForm",
            FieldList::create(
                HiddenField::create("ID"),
                HiddenField::create("ContactID"),
                TextField::create(
                    'Address1',
                    _t('ContactAdmin.Address1', 'Address Line 1')
                ),
                TextField::create(
                    'Address2',
                    _t('ContactAdmin.Address2', 'Address Line 2')
                )->setRightTitle(_t('ContactAdmin.Optional', 'Optional')),
                TextField::create(
                    'City',
                    _t('ContactAdmin.City', 'City')
                ),
                TextField::create(
                    'County',
                    _t('ContactAdmin.StateCounty', 'State/County')
                ),
                TextField::create(
                    'PostCode',
                    _t('ContactAdmin.PostCode', 'Post Code')
                ),
                DropdownField::create(
                    'Country',
                    _t('ContactAdmin.Country', 'Country'),
                    i18n::getData()->getCountries()
                )->setEmptyString("")
            ),
            FieldList::create(
                LiteralField::create(
                    'BackButton',
                    '<a href="' . $this->owner->Link('addresses') . '" class="btn btn-link">' . _t('ContactAdmin.Cancel', 'Cancel') . '</a>'
                ),
                FormAction::create(
                    'doSaveAddress',
                    _t('ContactAdmin.Add', 'Add')
                )->addExtraClass('btn btn-success')
            ),
            RequiredFields::create([
                'FirstName',
                'Surname',
                'Address1',
                'City',
                'PostCode',
                'Country',
            ])
        );

        $this->owner->extend("updateAddressForm", $form);

        return $form;
    }

        /**
     * Display all addresses associated with the current user
     * 
     * @return HTMLText
     */
    public function addresses()
    {
        $member = Security::getCurrentUser();

        $this
            ->owner
            ->customise([
                "Title" => _t("ContactAdmin.YourAddresses", "Your Addresses"),
                "MetaTitle" => _t("ContactAdmin.YourAddresses", "Your Addresses"),
                "Content" => $this->owner->renderWith(
                    "SilverCommerce\\OrdersAdmin\\Includes\\Addresses",
                    ["Contact" => $member->Contact()]
                )
            ]);

        $this->owner->extend("updateAddresses");

        return $this
            ->owner
            ->renderWith([
                'AccountController_addresses',
                AccountController::class . '_addresses',
                'AccountController',
                AccountController::class,
                'Page'
            ]);
    }

    /**
     * Display all addresses associated with the current user
     * 
     * @return HTMLText
     */
    public function addaddress()
    {
        $form = $this->AddressForm();
        $member = Security::getCurrentUser();

        $form
            ->Fields()
            ->dataFieldByName("ContactID")
            ->setValue($member->Contact()->ID);

        $this
            ->owner
            ->customise([
                "Title" => _t("ContactAdmin.AddAddress", "Add Address"),
                "MetaTitle" => _t("ContactAdmin.AddAddress", "Add Address"),
                "Form" => $form
            ]);

        $this->owner->extend("updateAddAddress");

        return $this
            ->owner
            ->renderWith([
                'AccountController_addaddress',
                AccountController::class . '_addaddress',
                'AccountController',
                AccountController::class,
                'Page'
            ]);
    }

    /**
     * Display all addresses associated with the current user
     * 
     * @return HTMLText
     */
    public function editaddress()
    {
        $member = Security::getCurrentUser();
        $id = $this->owner->request->param("ID");
        $address = ContactLocation::get()->byID($id);

        if ($address && $address->canEdit($member)) {
            $form = $this->AddressForm();
            $form->loadDataFrom($address);
            $form
                ->Actions()
                ->dataFieldByName("action_doSaveAddress")
                ->setTitle(_t("ContactAdmin.Save", "Save"));

                
            $this
                ->owner
                ->customise(array(
                    "Title" => _t("ContactAdmin.EditAddress", "Edit Address"),
                    "MenuTitle" => _t("ContactAdmin.EditAddress", "Edit Address"),
                    "Form" => $form
                ));

            $this->owner->extend("updateEditAddress");
            
            return $this
                ->owner
                ->renderWith([
                    'AccountController_editaddress',
                    AccountController::class . '_editaddress',
                    'AccountController',
                    AccountController::class,
                    'Page'
                ]);
        } else {
            return $this->owner->httpError(404);
        }
    }

    /**
     * Remove an addresses by the given ID (if allowed)
     * 
     * @return HTMLText
     */
    public function removeaddress()
    {
        $member = Security::getCurrentUser();
        $id = $this->owner->request->param("ID");
        $address = ContactLocation::get()->byID($id);

        if ($address && $address->canDelete($member)) {
            $address->delete();
            $this
                ->owner
                ->customise([
                    "Title" => _t("ContactAdmin.AddressRemoved", "Address Removed"),
                    "MenuTitle" => _t("ContactAdmin.AddressRemoved", "Address Removed")
                ]);

            $this->owner->extend("updateEditAddress");

            return $this
                ->owner
                ->renderWith([
                    'AccountController_removeaddress',
                    AccountController::class . '_removeaddress',
                    'AccountController',
                    AccountController::class,
                    'Page'
                ]);
        } else {
            return $this->owner->httpError(404);
        }
    }

    /**
     * Method responsible for saving (or adding) an address.
     * If the ID field is set, the method assums we are saving
     * an address.
     *
     * If the ID field is not set, we assume a new address is being
     * created.
     *
     */
    public function doSaveAddress($data, $form)
    {
        if (!$data["ID"]) {
            $address = ContactLocation::create();
        } else {
            $address = ContactLocation::get()->byID($data["ID"]);
        }

        if ($address) {
            $form->saveInto($address);
            $address->write();
            $form->sessionMessage(
                _t("ContactAdmin.AddressSaved", "Address Saved"),
                ValidationResult::TYPE_GOOD
            );
        } else {
            $form->sessionMessage(
                _t("ContactAdmin.Error", "There was an error"),
                ValidationResult::TYPE_ERROR
            );
        }
        return $this->owner->redirect($this->owner->Link("addresses"));
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

        $menu->add(ArrayData::create([
            "ID"    => 11,
            "Title" => _t('ContactAdmin.Addresses', 'Addresses'),
            "Link"  => $this->owner->Link("addresses"),
            "LinkingMode" => ($curr_action == "addresses") ? "current" : "link"
        ]));
    }
}