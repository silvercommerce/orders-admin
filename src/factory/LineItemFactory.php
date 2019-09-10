<?php

namespace SilverCommerce\OrdersAdmin\Factory;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Model\LineItemCustomisation;

/**
 * Factory that handles setting up line items based on submitted data
 */
class LineItemFactory
{
    use Injectable, Configurable;

    const ITEM_CLASS = LineItem::class;

    const CUSTOM_CLASS = LineItemCustomisation::class;

    /**
     * Data that will be added to a customisation
     *
     * @var array
     */
    private static $custom_map = [
        "Title",
        "Value",
        "BasePrice"
    ];

    /**
     * Should the stock stock levels be globally checked on items added?
     * Using this setting will ignore individual product "Stocked" settings.
     *
     * @var string
     */
    private static $force_check_stock = false;

    /**
     * Current line item
     *
     * @var DataObject
     */
    protected $item;

    /**
     * DataObject that will act as the product
     *
     * @var \SilverStripe\ORM\DataObject
     */
    protected $product;

    /**
     * The number of product to add/update for this line item
     *
     * @var int
     */
    protected $quantity;

    /**
     * Should this item be locked (cannot be updated, only removed)?
     *
     * @var bool
     */
    protected $lock;

    /**
     * List of customisation data that will need to be setup
     *
     * @var array
     */
    protected $customisations = [];

    /**
     * The name of the param used on product to determin if stock level should
     * be checked.
     *
     * @var string
     */
    protected $product_stocked_param = "Stocked";

    /**
     * The name of the param used on product to track Stock Level.
     *
     * @var string
     */
    protected $product_stock_param = "StockLevel";

    /**
     * The name of the param used on product to determin if item is deliverable
     *
     * @var string
     */
    protected $product_deliverable_param = "Deliverable";

    /**
     * Either find an existing line item (based on the submitted data),
     * or return a new one.
     *
     * @return DataObject
     */
    public function makeItem()
    {
        $custom = $this->getCustomisations();
        $class = self::ITEM_CLASS;

        // Setup initial line item
        $item = $class::create($this->getItemArray());

        // Find any item customisation associations
        $custom_association = null;
        $associations = array_merge(
            $item->hasMany(),
            $item->manyMany()
        );

        // Define association of item to customisations
        foreach ($associations as $key => $value) {
            $class = $value::create();
            if (is_a($class, self::CUSTOM_CLASS)) {
                $custom_association = $key;
                break;
            }
        }

        // Map any customisations to the current item
        if (isset($custom_association)) {
            foreach ($custom as $custom_data) {
                $customisation = $this->createCustomisation($custom_data);
                $customisation->write();
                $item->{$custom_association}()->add($customisation);
            }
        }

        // Setup Key
        $item->Key = $item->generateKey();
        $this->setItem($item);
        
        return $this;
    }

    /**
     * Update the current line item
     *
     * @return self
     */
    public function update()
    {
        $item = $this->getItem();
        $item->update($this->getItemArray());
        $item->Key = $item->generateKey();
        $this->setItem($item);

        return $this;
    }

    /**
     * Get an array of data for the line item
     *
     * @return array
     */
    protected function getItemArray()
    {
        $product = $this->getProduct();
        $qty = $this->getQuantity();
        $lock = $this->getLock();

        if (empty($product)) {
            throw new ValidationException(
                _t(
                    __CLASS__ . "NoProductSet",
                    "No product set"
                )
            );
        }

        // ensure that object price is something we can work with
        if (empty($product->BasePrice)) {
            throw new ValidationException("Product needs a 'BasePrice' param");
        }

        // Check if deliverable and stocked
        $stocked_param = $this->getProductStockedParam();
        $deliver_param = $this->getProductDeliverableParam();

        if (isset($product->{$deliver_param})) {
            $deliverable = $product->{$deliver_param};
        } else {
            $deliverable = true;
        }

        if (isset($product->{$stocked_param})) {
            $stocked = $product->{$stocked_param};
        } else {
            $stocked = false;
        }

        // Setup initial line item
        return [
            "Title" => $product->Title,
            "BasePrice" => $product->BasePrice,
            "TaxRateID" => $product->getTaxRate()->ID,
            "StockID" => $product->StockID,
            "ProductClass" => $product->ClassName,
            "Quantity" => $qty,
            "Stocked" => $stocked,
            "Deliverable" => $deliverable,
            'Locked' => $lock
        ];
    }

    /**
     * Shortcut to get the item key from the item in this factory
     *
     * @return string
     */
    public function getKey()
    {
        $item = $this->getItem();
        if (!empty($item) && !empty($item->Key)) {
            return $item->Key;
        }

        return "";
    }

    /**
     * Create a customisation object to be added to the current order
     *
     * @param array $data An array of data to add to the customisation
     *
     * @return DataObject
     */
    protected function createCustomisation(array $data)
    {
        $mapped_data = [];
        $class = self::CUSTOM_CLASS;

        foreach ($data as $key => $value) {
            if (in_array($key, $this->config()->get('custom_map'))) {
                $mapped_data[$key] = $value;
            }
        }

        return $class::create($mapped_data);
    }

    /**
     * Check the available stock for the current line item. If stock checking
     * is disabled then returns true
     *
     * @return bool
     */
    public function checkStockLevel()
    {
        $qty = $this->getQuantity();
        $force = $this->config()->get('force_check_stock');
        $stock_item = $this->getItem()->findStockItem();
        $param = $this->getProductStockParam();
        $item = $this->getItem();

        // If we are checking stock and there is not enough, return false
        if (isset($stock_item)
            && ($force || isset($stock_item->{$param}) && $stock_item->{$param})
            && ($item->checkStockLevel($qty) < 0)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Write the current line item
     *
     * @return self
     */
    public function write()
    {
        $item = $this->getItem();
        if (!empty($item)) {
            $item->write();
        }
        return $this;
    }

    /**
     * Remove the current item from the DB
     *
     * @return self
     */
    public function delete()
    {
        $item = $this->getItem();
        if (!empty($item)) {
            $item->delete();
        }
        return $this;
    }

    /**
     * Get current line item
     *
     * @return  DataObject
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set current line item
     *
     * @param LineItem $item  Item to add
     * @param boolean  $setup Should we setup this factory based on the item?
     *
     * @return self
     */
    public function setItem(LineItem $item, $setup = true)
    {
        // If item has an assigned product, add it as well
        $this->item = $item;

        if (!$setup) {
            return $this;
        }

        $product = $item->FindStockItem();
        if (!empty($product)) {
            $this->setProduct($product);
        }

        $this->setQuantity($item->Quantity);
        $this->setLock($item->Locked);
        $this->setCustomisations($item->Customisations()->toArray());

        return $this;
    }

    /**
     * Get dataObject that will act as the product
     *
     * @return DataObject
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set dataObject that will act as the product
     *
     * @param DataObject $product product object
     *
     * @return self
     */
    public function setProduct(DataObject $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Get list of customisation data that will need to be setup
     *
     * @return array
     */
    public function getCustomisations()
    {
        return $this->customisations;
    }

    /**
     * Set list of customisation data that will need to be setup
     *
     * @param array $customisations customisation data
     *
     * @return self
     */
    public function setCustomisations(array $customisations)
    {
        $this->customisations = $customisations;
        return $this;
    }

    /**
     * Get the number of products to add/update for this line item
     *
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set the number of products to add/update for this line item
     *
     * @param int $quantity number of products
     *
     * @return self
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Get should this item be locked (cannot be updated, only removed)?
     *
     * @return bool
     */
    public function getLock()
    {
        $item = $this->getItem();
        if (empty($this->lock) && isset($item)) {
            return $item->Locked;
        }

        return $this->lock;
    }

    /**
     * Set should this item be locked (cannot be updated, only removed)?
     *
     * @param bool $lock Is item locked?
     *
     * @return self
     */
    public function setLock(bool $lock)
    {
        $this->lock = $lock;
        return $this;
    }

    /**
     * Get name of stocked parameter
     *
     * @return string
     */
    public function getProductStockedParam()
    {
        return $this->product_stocked_param;
    }

    /**
     * Get name of stocked parameter
     *
     * @param string $param Param name.
     *
     * @return self
     */
    public function setProductStockedParam(string $param)
    {
        $this->product_stocked_param = $param;
        return $this;
    }

    /**
     * Get the name of the param used on product to track Stock Level.
     *
     * @return string
     */
    public function getProductStockParam()
    {
        return $this->product_stock_param;
    }

    /**
     * Set the name of the param used on product to track Stock Level.
     *
     * @param string $param param name
     *
     * @return self
     */
    public function setProductStockParam(string $param)
    {
        $this->product_stock_param = $param;
        return $this;
    }

    /**
     * Get the name of the param used on product to determin if item is deliverable
     *
     * @return string
     */
    public function getProductDeliverableParam()
    {
        return $this->product_deliverable_param;
    }

    /**
     * Set the name of the param used on product to determin if item is deliverable
     *
     * @param string $param The param name
     *
     * @return self
     */
    public function setProductDeliverableParam(string $param)
    {
        $this->product_deliverable_param = $param;
        return $this;
    }
}
