<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\View\Requirements;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Versioned\DataDifferencer;
use SilverStripe\Core\Convert;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\PjaxResponseNegotiator;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Versioned\VersionedGridFieldItemRequest;

class OrdersDetailForm_ItemRequest extends VersionedGridFieldItemRequest
{

    private static $allowed_actions = [
        'edit',
        'view',
        'ItemEditForm'
    ];
    
    public function edit($request)
    {
        $controller = $this->getToplevelController();
        $form = $this->ItemEditForm();

        // If this is a new record, we need to save it first
        if ($this->record->ID == 0) {
            // ensure we populate any foreign keys first
            $list = $this->gridField->getList();
            if ($list instanceof HasManyList && !$this->record->isInDB()) {
                $key = $list->getForeignKey();
                $id = $list->getForeignID();
                $this->record->$key = $id;
            }

            $this->record->write();
            
            $controller
                ->getRequest()
                ->addHeader('X-Pjax', 'Content');
            
            return $controller->redirect($this->Link("edit"));
        }

        $return = $this->customise(array(
            'Backlink' => $controller->hasMethod('Backlink') ? $controller->Backlink() : $controller->Link(),
            'ItemEditForm' => $form,
        ))->renderWith($this->getTemplates());
        
        if ($request->isAjax()) {
            return $return;
        } else {
            // If not requested by ajax, we need to render it within the controller context+template
            return $controller->customise(array(
                // TODO CMS coupling
                'Content' => $return,
            ));
        }
    }

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $fields = $form->Fields();
        $actions = $form->Actions();
        $record = $this->record;
        $member = Member::currentUser();

        $can_view = $this->record->canView();
        $can_edit = $this->record->canEdit();
        $can_delete = $this->record->canDelete();
        $can_create = $this->record->canCreate();

        // First cache and remove the delete button
        $delete_action = $actions->dataFieldByName("action_doDelete");
        $actions->removeByName("action_doDelete");

        // Deal with Estimate objects
        if ($record->ClassName == Estimate::class) {            
            if ($record->ID && $can_edit) {
                $actions->insertAfter(
                    FormAction::create(
                        'doConvert',
                        _t('OrdersAdmin.ConvertToInvoice', 'Convert To Invoice')
                    )->setUseButtonTag(true)
                    ->addExtraClass('btn-outline-primary btn-hide-outline font-icon-sync'),
                    "action_doSave"
                );
            }
        }

        // Deal with Order objects
        if ($record->ClassName == Invoice::class) {
            $can_change_status = $this->record->canChangeStatus();
            
            // Set our status field as a dropdown (has to be here to
            // ignore canedit)
            // Allow users to change status (as long as they have permission)
            if ($can_edit || $can_change_status) {
                $status_field = DropdownField::create(
                    'Status',
                    null,
                    $record->config()->statuses
                );
                
                // Set default status if we can
                if (!$record->Status && !$record->config()->default_status) {
                    $status_field
                        ->setValue($record->config()->default_status);
                } else {
                    $status_field
                        ->setValue($record->Status);
                }
                
                $fields->replaceField("Status", $status_field);
            }
            
            // Is user cannot edit, but can change status, add change
            // status button
            if ($record->ID && !$can_edit && $can_change_status) {
                $actions->push(
                    FormAction::create('doChangeStatus', _t('OrdersAdmin.Save', 'Save'))
                        ->setUseButtonTag(true)
                        ->addExtraClass('btn-primary font-icon-save')
                );
            }
        }

        if ($record->ID) {
            // Setup order history
            if (Permission::check(array('ORDERS_EDIT_INVOICES', 'ADMIN'), 'any', $member)) {
                $versions = $record->AllVersions();
                $first_version = $versions->First();
                $curr_version = ($first_version) ? $versions->First() : null;
                $message = "";

                foreach ($versions as $version) {
                    $i = $version->Version;
                    $name = "History_{$i}";

                    if ($i > 1) {
                        $frm = Versioned::get_version($record->ClassName, $record->ID, $i - 1);
                        $to = Versioned::get_version($record->ClassName, $record->ID, $i);
                        $diff = new DataDifferencer($frm, $to);

                        if ($version->Author()) {
                            $message = "<p>{$version->Author()->FirstName} ({$version->LastEdited})</p>";
                        } else {
                            $message = "<p>Unknown ({$version->LastEdited})</p>";
                        }

                        if ($diff->ChangedFields()->exists()) {
                            $message .= "<ul>";

                            // Now loop through all changed fields and track as message
                            foreach ($diff->ChangedFields() as $change) {
                                if ($change->Name != "LastEdited") {
                                    $message .= "<li>{$change->Title}: {$change->Diff}</li>";
                                }
                            }

                            $message .= "</ul>";
                        }
                        
                        $fields->addFieldToTab("Root.History", LiteralField::create(
                            $name,
                            "<div class=\"field\">{$message}</div>"
                        ));
                    }
                }
            }

            // Add a duplicate button, either after the save button or
            // the change status "save" button.
            $duplicate_button = FormAction::create(
                'doDuplicate',
                _t('OrdersAdmin.Duplicate', 'Duplicate')
            )->setUseButtonTag(true)
            ->addExtraClass('btn-outline-primary  btn-hide-outline font-icon-switch');
            
            if ($actions->find("Name", "action_doSave")) {
                $actions->insertAfter($duplicate_button, "action_doSave");
            }
            
            if ($actions->find("Name", "action_doChangeStatus")) {
                $actions->insertAfter($duplicate_button, "action_doChangeStatus");
            }

            $html = '<a href="' . $record->DisplayLink() . '" ';
            $html .= 'target="_blank" class="btn btn-outline-primary  btn-hide-outline font-icon-eye"';
            $html .= '>' . _t('OrdersAdmin.View', 'View') . '</a>';
            
            $view_field = LiteralField::create('ViewButton', $html);

            $html = '<a href="' . $record->PDFLink() . '" ';
            $html .= 'target="_blank" class="btn btn-outline-primary  btn-hide-outline font-icon-down-circled"';
            $html .= '>' . _t('OrdersAdmin.Download', 'Download') . '</a>';
            
            $download_field = LiteralField::create('DownloadButton', $html);
            
            $actions->push($view_field, "action_doSave");
            $actions->push($download_field, "action_doSave");
        }

        // Finally, if allowed, re-add the delete button (so it is last)
        if ($record->ID && $can_delete) {
            $actions->push($delete_action);
        }

        $this->extend("updateItemEditForm", $form);

        return $form;
    }
    
    public function doDuplicate($data, $form)
    {
        $record = $this->record;

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record->write();
        
        $new_record = $record->duplicate();
        
        $this->gridField->getList()->add($new_record);

        $message = sprintf(
            _t('OrdersAdmin.Duplicated', 'Duplicated %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm = $toplevelController->getEditForm();
            $backForm->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
        } else {
            $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
        }
        
        $toplevelController = $this->getToplevelController();
        $toplevelController->getRequest()->addHeader('X-Pjax', 'Content');

        return $toplevelController->redirect($this->getBacklink(), 302);
    }
    
    public function doConvert($data, $form)
    {
        $record = $this->record;

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        $form->saveInto($record);
        
        $record = $record->convertToInvoice();
        $this->record = $record;
        
        $this->gridField->getList()->add($record);

        $message = sprintf(
            _t('OrdersAdmin.ConvertedToOrder', 'Converted %s %s'),
            $this->record->singular_name(),
            '"'.Convert::raw2xml($this->record->Title).'"'
        );
        
        $toplevelController = $this->getToplevelController();
        if ($toplevelController && $toplevelController instanceof LeftAndMain) {
            $backForm = $toplevelController->getEditForm();
            $backForm->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
        } else {
            $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);
        }
        
        $toplevelController = $this->getToplevelController();
        $toplevelController->getRequest()->addHeader('X-Pjax', 'Content');

        return $toplevelController->redirect($this->getBacklink(), 302);
    }
    
    public function doChangeStatus($data, $form)
    {
        $new_record = $this->record->ID == 0;
        $controller = $this->getToplevelController();
        $list = $this->gridField->getList();

        try {
            $this->record->Status = $data["Status"];
            $this->record->write();
        } catch (ValidationException $e) {
            $form->sessionMessage($e->getResult()->message(), 'bad', ValidationResult::CAST_HTML);
            
            $responseNegotiator = new PjaxResponseNegotiator(array(
                'CurrentForm' => function () use (&$form) {
                    return $form->forTemplate();
                },
                'default' => function () use (&$controller) {
                    return $controller->redirectBack();
                }
            ));
            
            if ($controller->getRequest()->isAjax()) {
                $controller->getRequest()->addHeader('X-Pjax', 'CurrentForm');
            }
            
            return $responseNegotiator->respond($controller->getRequest());
        }

        $link = '<a href="' . $this->Link('edit') . '">"'
            . htmlspecialchars($this->record->Title, ENT_QUOTES)
            . '"</a>';

        $message = _t(
            'OrdersAdmin.StatusChanged',
            'Status Changed {name} {link}',
            array(
                'name' => $this->record->i18n_singular_name(),
                'link' => $link
            )
        );
        
        $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            // Return new view, as we can't do a "virtual redirect" via the CMS Ajax
            // to the same URL (it assumes that its content is already current, and doesn't reload)
            return $this->edit($controller->getRequest());
        } else {
            // Changes to the record properties might've excluded the record from
            // a filtered list, so return back to the main view if it can't be found
            $noActionURL = $controller->removeAction($data['url']);
            $controller->getRequest()->addHeader('X-Pjax', 'Content');
            return $controller->redirect($noActionURL, 302);
        }
    }
}
