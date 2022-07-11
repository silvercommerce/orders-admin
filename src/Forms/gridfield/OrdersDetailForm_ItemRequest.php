<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Security\Security;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Control\PjaxResponseNegotiator;
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
        $record = $this->getRecord();

        // If this is a new record, we need to save it first
        if (!$record->exists()) {
            // ensure we populate any foreign keys first
            $list = $this->gridField->getList();

            if ($list instanceof HasManyList) {
                $key = $list->getForeignKey();
                $id = $list->getForeignID();
                $record->$key = $id;
            }

            $record->write();
            
            $controller
                ->getRequest()
                ->addHeader('X-Pjax', 'Content');
            
            return $controller->redirect($this->Link("edit"));
        }

        $return = $this->customise([
            'Backlink' => $controller->hasMethod('Backlink') ? $controller->Backlink() : $controller->Link(),
            'ItemEditForm' => $form,
        ])->renderWith($this->getTemplates());
        
        if ($request->isAjax()) {
            return $return;
        } else {
            // If not requested by ajax, we need to render it within the controller context+template
            return $controller->customise([
                // TODO CMS coupling
                'Content' => $return,
            ]);
        }
    }

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $fields = $form->Fields();
        $actions = $form->Actions();
        $record = $this->record;
        $can_create = $record->canCreate();
        $can_edit = $record->canEdit();
        $can_change_status = $record->canChangeStatus();

        // Deal with Order objects
        if ($record instanceof Invoice) {
            // replace HasOneButtonField with ReadOnly field
            // This is to prevent an error where the Button's object attempts to render
            if (!$can_edit) {
                $name = null;
                if ($record->Customer()->exists()) {
                    $name = $record->Customer()->getTitle();
                }

                $fields->replaceField(
                    'Customer',
                    ReadonlyField::create(
                        'Customer'
                    )->setValue($name)
                );
            }

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
        }

        /**
         * If change status action is present, allow
         *
         * @var FormAction $change_status
         */
        $change_status = $actions->fieldByName('MajorActions.action_doChangeStatus');

        if (!empty($change_status)) {
            $change_status
                ->setDisabled(false)
                ->setReadonly(false);
        }

        $duplicate_action = $actions->fieldByName('MajorActions.action_doDuplicate');

        if (!empty($duplicate_action) && $can_create) {
            $duplicate_action
                ->setDisabled(false)
                ->setReadonly(false);
        }

        $this->extend("updateItemEditForm", $form);

        return $form;
    }

    protected function getRightGroupField()
    {
        $right_group = parent::getRightGroupField();
        $record = $this->record;

        // Add view and download buttons
        if ($record->exists()) {
            $html = '<a href="' . $record->DisplayLink() . '" ';
            $html .= 'target="_blank" class="btn btn-outline-primary  btn-hide-outline font-icon-eye"';
            $html .= '>' . _t('OrdersAdmin.View', 'View') . '</a>';

            $view_field = LiteralField::create('ViewButton', $html);

            $html = '<a href="' . $record->PDFLink() . '" ';
            $html .= 'target="_blank" class="btn btn-outline-primary  btn-hide-outline font-icon-down-circled"';
            $html .= '>' . _t('OrdersAdmin.Download', 'Download') . '</a>';

            $download_field = LiteralField::create('DownloadButton', $html);

            $right_group->insertBefore(
                'PreviousAndNextGroup',
                $view_field
            );
            $right_group->insertBefore(
                'PreviousAndNextGroup',
                $download_field
            );
        }

        return $right_group;
    }

    public function getFormActions()
    {
        $actions = parent::getFormActions();
        $major_actions = $actions->fieldByName('MajorActions');
        $record = $this->record;
        $can_create = $record->canCreate();
        $can_edit = $record->canEdit();
        $can_change_status = $record->canChangeStatus();

        // Deal with Estimate objects
        if ($record->ClassName == Estimate::class
            && $record->exists() && $can_edit
        ) {
            $major_actions->insertAfter(
                "action_doSave",
                FormAction::create(
                    'doConvert',
                    _t('OrdersAdmin.ConvertToInvoice', 'Convert To Invoice')
                )->setUseButtonTag(true)
                ->addExtraClass('btn-outline-primary btn-hide-outline font-icon-sync'),
            );
        }

        // Add a duplicate button, either after the save button or
        // the change status "save" button.
        if ($record->exists() && $can_create) {
            $duplicate_button = FormAction::create(
                'doDuplicate',
                _t('OrdersAdmin.Duplicate', 'Duplicate')
            )->setUseButtonTag(true)
            ->addExtraClass('btn-outline-primary  btn-hide-outline font-icon-switch');
            
            $major_actions->push($duplicate_button);
        }

        // If user cannot edit, but can change status
        // add change status button
        if ($record instanceof Invoice && $record->exists()
            && !$can_edit && $can_change_status
        ) {
            $major_actions->unshift(
                FormAction::create('doChangeStatus', _t('OrdersAdmin.Save', 'Save'))
                    ->setUseButtonTag(true)
                    ->addExtraClass('btn-primary font-icon-save')
            );
        }

        return $actions;
    }
    
    public function doDuplicate($data, $form)
    {
        $record = $this->getRecord();
        $can_create = $record->canCreate();
        $can_edit = $record->canEdit();

        if (!$can_create) {
            return Security::permissionFailure($this);
        }

        if ($can_edit) {
            $form->saveInto($record);
            $record->write();
        }
        
        $new_record = $record->duplicate();
        
        $this
            ->getGridField()
            ->getList()
            ->add($new_record);

        $message = sprintf(
            _t('OrdersAdmin.Duplicated', 'Duplicated %s %s'),
            $record->singular_name(),
            '"'.Convert::raw2xml($record->Title).'"'
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
        $record = $this->getRecord();
        $controller = $this->getToplevelController();
        $grid_field = $this->getGridField();
        $list = $grid_field->getList();

        try {
            $record->Status = $data["Status"];
            $record->write();
        } catch (ValidationException $e) {
            $form->sessionMessage($e->getMessage(), 'bad', ValidationResult::CAST_HTML);

            $responseNegotiator = new PjaxResponseNegotiator([
                'CurrentForm' => function () use (&$form) {
                    return $form->forTemplate();
                },
                'default' => function () use (&$controller) {
                    return $controller->redirectBack();
                }
            ]);

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
            [
                'name' => $this->record->i18n_singular_name(),
                'link' => $link
            ]
        );

        $form->sessionMessage($message, 'good', ValidationResult::CAST_HTML);

        return $this->redirectAfterSave(false);
    }
}
