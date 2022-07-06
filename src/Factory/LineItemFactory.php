<?php

namespace SilverCommerce\OrdersAdmin\Factory;

use LogicException;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\ValidationException;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Model\PriceModifier;
use SilverCommerce\OrdersAdmin\Model\LineItemCustomisation;

/**
 * Factory that handles setting up line items based on submitted data
 */
class LineItemFactory
{
    use Injectable, Configurable;

    const ITEM_CLASS = LineItem::class;

    const CUSTOM_CLASS = LineItemCustomisation::class;

    const PRICE_CLASS = PriceModifier::class;

    /**
     * Data that will be added to a customisation. If you
     * want additional data to be added to the
     * customisation, ensure it is mapped here
     *
     * @var array
     */
    private static $custom_map = [
        "Title",
        "Value"
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
     * Parent estimate/invoice
     *
     * @var Estimate
     */
    protected $parent;

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
     * (defaults to false)
     *
     * @var bool
     */
    protected $lock = false;

    /**
     * Is this item deliverable (a physical item that is shipped)?
     * (defaults to true)
     *
     * @var bool
     */
    protected $deliverable = true;

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
     * List of customisation data that will need to be setup
     *
     * Depreciated as of v2
     * 
     * @var array
     */
    protected $customisations = [];

    /**
     * Either find an existing line item (based on the submitted data),
     * or return a new one.
     *
     * @return self
     */
    public function makeItem(): self
    {
        $class = self::ITEM_CLASS;

        // Setup initial line item
        $item = $class::create($this->getItemArray());

        // Setup Key
        $item->Key = $item->generateKey();
        $this->setItem($item);
        
        return $this;
    }

    /**
     * Customise the current line item and then return the
     * generated customisation
     *
     * @param string $name The name of this customisation (eg: size)
     * @param string $value The value of this customisation (eg: small)
     * @param array  $additional_data Any additional data to save (ensure you also)
     *
     * @return LineItemCustomisation
     */
    public function customise(
        string $name,
        string $value,
        array $additional_data = []
    ): LineItemCustomisation {
        $item = $this->getItem();
        $mapped_data = [];
        $class = self::CUSTOM_CLASS;

        /** @var LineItemCustomisation */
        $customisation =  $class::create();
        $customisation->Name = $name;
        $customisation->Value = $value;
        $customisation->ParentID = $item->ID;

        if (count($additional_data) > 0) {
            $customisation->write();
            return $this;
        }

        foreach ($additional_data as $key => $value) {
            if (in_array($key, $this->config()->get('custom_map'))) {
                $mapped_data[$key] = $value;
            }
        }

        $customisation->write();
        return $customisation;
    }

    /**
     * Generate a customisation and/or price
     * modification for the current item
     *
     * @param string $name The name of this modification (eg: size)
     * @param float  $amount The amount to modify the price by (either positive or negative)
     * @param int $customisation_id Optionally link this to a customisation
     *
     * @return PriceModifier
     */
    public function modifyPrice(
        string $name,
        float $amount,
        int $customisation_id = 0
    ): PriceModifier  {
        $item = $this->getItem();
        $class = self::PRICE_CLASS;

        /** @var PriceModifier */
        $modifier =  $class::create();
        $modifier->Name = $name;
        $modifier->ModifyPrice = $amount;
        $modifier->LineItemID = $item->ID;

        if ($customisation_id > 0) {
            $customisation = $item
                ->Customisations()
                ->byID($customisation_id);
            
            if (empty($customisation)) {
                throw new LogicException("Invalid customisation passed to modifier");
            }

            $modifier->CustomisatioID = $customisation_id;
        }

        $modifier->write();
        return $modifier;
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
     * Find the best possible tax rate for a line item. If the item is
     * linked to an invoice/estimate, then see if there is a Country
     * and Region set, else use product default
     *
     * @return TaxRate
     */
    public function findBestTaxRate()
    {
        $rate = null;
        $item = $this->getItem();
        $product = $this->getProduct();
        $default = TaxRate::create();
        $default->Rate = 0;
        $default->ID = -1;

        // If no product available, return an empty rate
        if (empty($product)) {
            return $default;
        }

        if (empty($item)) {
            return $product->getTaxRate();
        }

        /** @var \SilverCommerce\TaxAdmin\Model\TaxCategory */
        $category = $product->TaxCategory();

        // If no tax category, return product default
        if (!$category->exists()) {
            return $product->getTaxRate();
        }

        $parent = $this->getParent();

        // If order available, try to gt delivery location
        if (!empty($parent)) {
            $country = $parent->DeliveryCountry;
            $region = $parent->DeliveryCounty;

            if (strlen($country) >= 2 && strlen($region) >= 2) {
                $rate = $category->ValidTax($country, $region);
            }
        }

        if (!empty($rate)) {
            return $rate;
        }

        return $default;
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
        $deliverable = $this->getDeliverable();

        if (empty($product)) {
            throw new ValidationException(
                _t(
                    __CLASS__ . "NoProductSet",
                    "No product set"
                )
            );
        }

        // ensure that object price is something we can work with
        if (!isset($product->BasePrice)) {
            throw new ValidationException("Product needs a 'BasePrice' param");
        }

        // Check if deliverable and stocked
        $stocked_param = $this->getProductStockedParam();

        if (isset($product->{$stocked_param})) {
            $stocked = (bool) $product->{$stocked_param};
        } else {
            $stocked = false;
        }

        $tax_rate = $this->findBestTaxRate();

        // Setup initial line item
        return [
            'Title' => $product->Title,
            'TaxRateID' => $tax_rate->ID,
            'Quantity' => $qty,
            'Stocked' => $stocked,
            'Deliverable' => $deliverable,
            'Locked' => $lock,
            'ProductClass' => $product->ClassName,
            'ProductID' => $product->ID,
            'ProductVersion' => $product->Version
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

        // Only lock a line item if we have explicitly asked to
        if (property_exists($item, 'Locked')) {
            $this->setLock($item->Locked);
        }

        $this->setParent($item->Parent());

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
     * Get list of customisations from the current item
     *
     * @return SS_List
     */
    public function getCustomisations()
    {
        return $this->getItem()->Customisations();
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
     * Get current parent estimate
     *
     * @return Estimate
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set current parent estimate
     *
     * @param Estimate $parent
     *
     * @return self
     */
    public function setParent(Estimate $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get if this item is deliverable
     *
     * @return  bool
     */ 
    public function getDeliverable()
    {
        return $this->deliverable;
    }

    /**
     * Set if this item is deliverable
     *
     * @param bool $deliverable
     *
     * @return self
     */ 
    public function setDeliverable(bool $deliverable)
    {
        $this->deliverable = $deliverable;
        return $this;
    }


    /********** LEGACY METHODS *********/

    /**
     * Set list of customisation data that will need to be setup
     *
     * @param array $customisations customisation data
     *
     * @return self
     */
    public function setCustomisations(array $customisations)
    {
        Deprecation::notice('2.0', "Customisations need to be set via `customise` or `modifyPrice` methods");

        $this->customisations = $customisations;
        return $this;
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
        Deprecation::notice('2.0', "Customisations need to be set via `customise` or `modifyPrice` methods");

        $mapped_data = [];
        $class = self::CUSTOM_CLASS;

        foreach ($data as $key => $value) {
            if (in_array($key, $this->config()->get('custom_map'))) {
                $mapped_data[$key] = $value;
            }
        }

        return $class::create($mapped_data);
    }
}
