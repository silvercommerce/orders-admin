<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use SilverStripe\Forms\FormTransformation;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldViewButton;

/**
 * Enables relations on a non-editable object to be viewed/edited.
 */
class ReadOnlyGridField extends GridField
{

    /**
     * @param FormTransformation $transformation
     *
     * @return $this
     */
    public function transform(FormTransformation $transformation)
    {
        $config = $this->getConfig();
        
        $config
            ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
            ->removeComponentsByType(GridFieldAddNewButton::class)
            ->removeComponentsByType(AddLineItem::class)
            ->removeComponentsByType(GridFieldEditButton::class);

        $config
            ->addComponent(new GridFieldViewButton());

        return $this;
    }
}
