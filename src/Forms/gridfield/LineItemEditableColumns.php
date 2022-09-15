<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use Exception;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Forms\GridField\GridField;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;

/**
 * Custom component that deals with some of the complexity of
 * managing LineItems in a GridField (such as StockID and Price)
 */
class LineItemEditableColumns extends GridFieldEditableColumns
{

    public function getColumnContent($grid, $record, $col)
    {
        $product = $record->findStockItem();

        if ($product->exists() && $col === 'UnmodifiedPrice') {
            $col = 'BasePrice';
        }

        return parent::getColumnContent($grid, $record, $col);
    }

    /**
     * Overwrite parent field list and swap out price and stock
     * ID fields as needed
     *
     * @param GridField $grid
     * @param DataObjectInterface $record
     *
     * @throws Exception
     * @return FieldList
     */
    public function getFields(
        GridField $grid,
        DataObjectInterface $record
    ) {
        $fields = parent::getFields($grid, $record);

        if (!$record instanceof LineItem) {
            throw new Exception('This component can only be used to manage LineItems');
        }

        $product = $record->findStockItem();

        if (!$product->exists()) {
            return $fields;
        }

        // Disable Stock ID and Price fields if data is coming from product
        $stock_field = $fields->dataFieldByName('StockID');
        $price_field = $fields->dataFieldByName('UnmodifiedPrice');

        if (!empty($stock_field)) {
            $stock_field
                ->setDisabled(true)
                ->performDisabledTransformation();
        }

        if (!empty($price_field)) {
            $price_field
                ->setDisabled(true)
                ->performDisabledTransformation();
        }

        return $fields;
    }
}
