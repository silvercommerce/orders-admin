<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use LogicException;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataList;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPResponse;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use Doctrine\Instantiator\Exception\UnexpectedValueException;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * A specific gridfield field designed to allow the creation of a new
 * order item and that auto completes all fields from a pre-defined
 * object (default Product).
 *
 * @package orders-admin
 *
 * @author ilateral <info@ilateral.co.uk>
 * @author Michael Strong <github@michaelstrong.co.uk>
**/
class AddLineItem extends GridFieldAddExistingAutocompleter
{

    /**
     * Default field to create the dataobject by should be Title.
     *
     * @var string
     **/
    protected $dataObjectField = "Title";
    
    /**
     * Default field to create the dataobject from.
     *
     * @var string
     **/
    protected $source_class = "Product";

    /**
     * When we check for existing items, should we check based on all
     * filters or any of the chosen (setting this to true uses
     * $list->filter() where as false uses $list->filterAny())
     *
     * @var boolean
     */
    protected $strict_filter = true;

    /**
     * What filter should the initial search use?
     *
     * @var string
     */
    protected $search_filter = 'PartialMatch';

    /**
     * Fields that we try and find our source object based on
     *
     * @var array
     **/
    protected $filter_fields = [
        "Title",
        "StockID"
    ];

    /**
     * Fields that we use to filter items for our autocomplete
     *
     * @var array
     **/
    protected $autocomplete_fields = [
        "Title",
        "StockID"
    ];

    /**
     * If filter fails, set this field when creating
     *
     * @var String
     **/
    protected $create_field = "Title";

    /**
     * Fields that we are mapping from the source object to our item
     *
     * @var array
     **/
    protected $source_fields = [
        "Title" => "Title",
        "StockID" => "StockID",
        "BasePrice" => "BasePrice"
    ];

    /**
     * This is the field that we attempt to match a TAX rate to
     * when setting up an order item
     *
     * @var string
     **/
    protected $source_tax_field = "TaxPercentage";

    public function getSourceClass()
    {
        return $this->source_class;
    }

    public function setSourceClass($class)
    {
        $this->source_class = $class;
        return $this;
    }

    public function getStrictFilter()
    {
        return $this->strict_filter;
    }

    public function setStrictFilter($bool)
    {
        $this->strict_filter = $bool;
        return $this;
    }

    public function getFilterFields()
    {
        return $this->filter_fields;
    }

    public function setFilterFields($fields)
    {
        $this->filter_fields = $fields;
        return $this;
    }

    public function getAutocompleteFields()
    {
        return $this->autocomplete_fields;
    }

    public function setAutocompleteFields($fields)
    {
        $this->autocomplete_fields = $fields;
        return $this;
    }

    public function getCreateField()
    {
        return $this->create_field;
    }

    public function setCreateField($field)
    {
        $this->create_field = $field;
        return $this;
    }

    public function getSourceFields()
    {
        return $this->source_fields;
    }

    public function setSourceFields($fields)
    {
        $this->source_fields = $fields;
        return $this;
    }

    public function getSourceTaxField()
    {
        return $this->source_tax_field;
    }

    public function setSourceTaxField($field)
    {
        $this->source_tax_field = $field;
        return $this;
    }

    public function getSearchFilter()
    {
        return $this->search_filter;
    }

    public function setSearchFilter(string $search_filter)
    {
        $this->search_filter = $search_filter;
        return $this;
    }


    /**
     * Handles the add action for the given DataObject
     *
     * @param $grid GridFIeld
     * @param $actionName string
     * @param $arguments mixed
     * @param $data array
     **/
    public function handleAction(GridField $grid, $actionName, $arguments, $data)
    {
        if ($actionName == "addto") {
            // Get our submitted fields and object class
            $dbField = $this->getDataObjectField();
            $objClass = $grid->getModelClass();
            $source_class = $this->getSourceClass();
            $source_item = null;
            $list = $grid->getList();
            $filter = [];

            // Has the user used autocomplete
            if (isset($data['relationID']) && $data['relationID']) {
                $string = $data['relationID'];
            } else {
                $string = null;
            }

            foreach ($this->getFilterFields() as $filter_field) {
                $filter[$filter_field] = $string;
            }
            
            // First check if we already have an object or if we need to
            // create one
            if ($this->getStrictFilter()) {
                $existing_obj = $list
                    ->filter($filter)
                    ->first();
            } else {
                $existing_obj = $list
                    ->filterAny($filter)
                    ->first();
            }
            
            if ($existing_obj) {
                $obj = $existing_obj;
            } else {
                $obj = $objClass::create();
            }

            // Is this a valid field
            if (!$obj->hasField($dbField)) {
                throw new UnexpectedValueException("Invalid field (" . $dbField . ") on  " . $obj->ClassName . ".");
            }
        
            if ($obj->ID && $obj->canEdit()) {
                // An existing record and can edit, update quantity
                $curr_qty = ($obj->Quantity) ? $obj->Quantity : 0;
                
                $obj->setCastedField(
                    "Quantity",
                    $curr_qty + 1
                );
                
                $list->add($obj);
            }
            
            if (!$obj->ID && $obj->canCreate()) {
                // If source item not set, try and get one or get a
                // an existing record
                if (!$source_item && class_exists($source_class)) {
                    $source_item = $source_class::get()
                        ->filterAny($filter)
                        ->first();
                }
                    
                if ($source_item) {
                    foreach ($this->getSourceFields() as $obj_field => $source_field) {
                        $obj->setCastedField(
                            $obj_field,
                            $source_item->$source_field
                        );
                    }

                    // Setup the tax
                    $tax_field = $this->getSourceTaxField();
                    $tax = TaxRate::get()->find("Rate", $source_item->$tax_field);
                    if ($tax) {
                        $obj->TaxRateID = $tax->ID;
                    }
                } else {
                    $obj->setCastedField($this->getCreateField(), $string);
                }

                $obj->setCastedField("Quantity", 1);
                $list->add($obj, []);
            }

            // Finally, issue a redirect to update totals
            $controller = Controller::curr();

            $response = $controller->response;
            $response->addHeader('X-Pjax', 'Content');
            $response->addHeader('X-Reload', true);

            return $controller->redirect($grid->getForm()->controller->Link(), 302);
        }
    }

    /**
     * Renders the TextField and add button to the GridField.
     *
     * @param $girdField GridField
     *
     * @return string HTML
     **/
    public function getHTMLFragments($gridField)
    {
        $forTemplate = ArrayData::create([
            'Fields' => FieldList::create()
        ]);

        $searchField = TextField::create(
            'gridfield_relationsearch',
            _t('SilverStripe\\Forms\\GridField\\GridField.RelationSearch', "Relation search")
        )->setAttribute('data-search-url', Controller::join_links($gridField->Link('search')))
        ->setAttribute(
            'placeholder',
            _t(
                __CLASS__ . ".TypeToAdd",
                "Type to add by {Filters} or {Title}",
                "Inform the user what to add based on",
                [
                    "Filters" => implode(", ", $this->getFilterFields()),
                    "Title" => $this->getCreateField()
                ]
            )
        )->addExtraClass('relation-search no-change-track action_gridfield_relationsearch');

        $findAction = GridField_FormAction::create(
            $gridField,
            'gridfield_relationfind',
            _t('SilverStripe\\Forms\\GridField\\GridField.Find', "Find"),
            'find',
            'find'
        )->setAttribute('data-icon', 'relationfind')
        ->addExtraClass('action_gridfield_relationfind');

        $addAction = GridField_FormAction::create(
            $gridField,
            'gridfield_relationadd',
            _t(__CLASS__ . 'Add', "Add"),
            'addto',
            'addto'
        )->setAttribute('data-icon', 'plus-circled')
        ->addExtraClass('btn btn-primary font-icon-plus-circled action_gridfield_relationadd');

        // If an object is not found, disable the action
        if (!is_int($gridField->State->GridFieldAddRelation(null))) {
            $addAction->setReadonly(true);
        }

        $forTemplate->Fields->push($searchField);
        $forTemplate->Fields->push($findAction);
        $forTemplate->Fields->push($addAction);

        if ($form = $gridField->getForm()) {
            $forTemplate->Fields->setForm($form);
        }

        $template = SSViewer::get_templates_by_class($this, '', __CLASS__);
        return [
            $this->targetFragment => $forTemplate->renderWith($template)
        ];
    }
    
    /**
     * Returns a json array of a search results that can be used by for
     * example Jquery.ui.autosuggestion
     *
     * @param GridField $gridField
     * @param SS_HTTPRequest $request
     */
    public function doSearch($gridField, $request)
    {
        $source_class = $this->getSourceClass();
        $search_filter = $this->getSearchFilter();
        $params = [];
        
        // Do we have filter fields setup?
        if ($this->getAutocompleteFields()) {
            $search_fields = $this->getAutocompleteFields();
        } else {
            $search_fields = $this->scaffoldSearchFields($source_class);
        }
        
        if (!$search_fields) {
            throw new LogicException(
                sprintf(
                    'GridFieldAddExistingAutocompleter: No searchable fields could be found for class "%s"',
                    $source_class
                )
            );
        }
        
        foreach ($search_fields as $search_field) {
            $name = (strpos($search_field, ':') !== false) ? $search_field : $search_field . ":" . $search_filter;
            $params[$name] = $request->getVar('gridfield_relationsearch');
        }

        $json = [];
        Config::nest();

        if (class_exists($source_class)) {
            $results = DataList::create($source_class)
                ->filterAny($params)
                ->sort(strtok($search_fields[0], ':'), 'ASC')
                ->limit($this->getResultsLimit());

            $originalSourceFileComments = SSViewer::config()->get('source_file_comments');
            
            SSViewer::config()->update('source_file_comments', false);
            $viewer = SSViewer::fromString($this->resultsFormat);

            foreach ($results as $result) {
                $title = Convert::html2raw($viewer->process($result));
                $json[] = [
                    'label' => $title,
                    'value' => $title,
                    'id' => $title,
                ];
            }

            SSViewer::config()->update('source_file_comments', $originalSourceFileComments);
        }

        Config::unnest();
        $response = new HTTPResponse(json_encode($json));
        $response->addHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Returns the database field for which we'll add the new data object.
     *
     * @return string
     **/
    public function getDataObjectField()
    {
        return $this->dataObjectField;
    }

    /**
     * Set the database field.
     *
     * @param $field string
     **/
    public function setDataObjectField($field)
    {
        $this->dataObjectField = (string) $field;
    }
}
