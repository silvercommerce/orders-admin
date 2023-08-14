<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use LogicException;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataList;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\GridField\GridField;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverCommerce\OrdersAdmin\Factory\OrderFactory;
use SilverCommerce\OrdersAdmin\Model\Invoice;
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
    protected $strict_filter = false;

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
    public function handleAction(
        GridField $grid,
        $actionName,
        $arguments,
        $data
    ) {
        if ($actionName == "addto") {
            $order = $grid
                ->getForm()
                ->getController()
                ->getRecord();
            $obj = null;
            $source_class = $this->getSourceClass();
            $source_item = null;
            /** @var HasManyList */
            $list = $grid->getList();
            $filter = [];
            $string = "";

            if (!class_exists($source_class)) {
                throw new LogicException('No source class set');
            }

            // Has the user used autocomplete
            if (isset($data['gridfield_productsearch']) && !empty($data['gridfield_productsearch'])) {
                $string = $data['gridfield_productsearch'];
            }

            foreach ($this->getFilterFields() as $filter_field) {
                $filter[$filter_field] = $string;
            }

            if ($this->getStrictFilter()) {
                $source_item = $source_class::get()
                    ->filter($filter)
                    ->first();
            } else {
                $source_item = $source_class::get()
                    ->filterAny($filter)
                    ->first();
            }

            // If no product found, then create new line item
            // from an empty product
            if (empty($source_item)) {
                $obj = $this->createNewItem($grid, $string);
                $list->add($obj);
                return;
            }

            if (is_a($order, Invoice::class)) {
                $is_invoice = true;
            } else {
                $is_invoice = false;
            }

            $factory = OrderFactory::create(
                $is_invoice,
                $order->ID
            );

            $item_factory = LineItemFactory::create()
                ->setParent($order)
                ->setProduct($source_item)
                ->setQuantity(1)
                ->makeItem();

            $factory->addFromLineItemFactory($item_factory);
            $factory->write();

            return;
        }
    }

    /**
     * Create a new line item and return
     *
     * @param GridField $grid_field
     * @param string $title
     * @param DataObject $source
     *
     * @return LineItem
     */
    protected function createNewItem(
        GridField $grid,
        string $title,
        DataObject $source = null
    ): LineItem {
        $field = $this->getCreateField();
        $product_class = $this->getSourceClass();

        // Slightly hacky way to bypas the product class
        // requirements in the factory
        if (empty($source)) {
            $source = $product_class::create([
                $field => $title
            ]);
        }

        $factory = LineItemFactory::create()
            ->setQuantity(1)
            ->setProduct($source)
            ->makeItem()
            ->write();

        return $factory->getItem();
    }

    /**
     * Renders the TextField and add button to the GridField.
     *
     * @param $girdField GridField
     *
     * @return string HTML
     **/
    public function getHTMLFragments($grid_field)
    {
        $for_template = ArrayData::create([
            'Fields' => FieldList::create()
        ]);

        $search_field = TextField::create(
            'gridfield_productsearch',
            _t(__CLASS__ . ".ProductSearch", "Product search")
        )->setAttribute(
            'data-search-url',
            Controller::join_links($grid_field->Link('search'))
        )->setAttribute(
            'placeholder',
            _t(
                __CLASS__ . ".TypeToAdd",
                "Add new item, or type {Filters} or {Title} to find existing",
                "Inform the user what to add based on",
                [
                    "Filters" => implode(", ", $this->getFilterFields()),
                    "Title" => $this->getCreateField()
                ]
            )
        )->addExtraClass('orders-lineitem-search')
        ->addExtraClass('form-control')
        ->addExtraClass('no-change-track');

        $find_action = GridField_FormAction::create(
            $grid_field,
            'gridfield_relationfind',
            _t('SilverStripe\\Forms\\GridField\\GridField.Find', "Find"),
            'find',
            'find'
        )->setAttribute('data-icon', 'relationfind')
        ->addExtraClass('action_gridfield_lineitemfind');

        $add_action = GridField_FormAction::create(
            $grid_field,
            'gridfield_relationadd',
            _t(
                __CLASS__ . 'AddItem',
                "Add Item"
            ),
            'addto',
            'addto'
        )->setAttribute('data-icon', 'plus-circled')
        ->addExtraClass('btn btn-primary')
        ->addExtraClass('font-icon-plus-circled')
        ->addExtraClass('action_gridfield_lineitemadd');

        // If an object is not found, disable the action
        if (!is_int($grid_field->State->GridFieldAddRelation(null))) {
            $add_action->setReadonly(true);
        }

        $for_template->Fields->push($search_field);
        $for_template->Fields->push($find_action);
        $for_template->Fields->push($add_action);

        if ($form = $grid_field->getForm()) {
            $for_template->Fields->setForm($form);
        }

        $template = SSViewer::get_templates_by_class(
            $this,
            '',
            __CLASS__
        );

        return [
            $this->targetFragment => $for_template->renderWith($template)
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
            $params[$name] = $request->getVar('gridfield_productsearch');
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
}
