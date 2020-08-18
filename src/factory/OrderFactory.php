<?php

namespace SilverCommerce\OrdersAdmin\Factory;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\OrdersAdmin\Model\Estimate;

class OrderFactory
{
    use Injectable, Configurable;

    /**
     * The class this factory uses to create an estimate
     *
     * @var string
     */
    private static $estimate_class = Estimate::class;

    /**
     * The class this factory uses to create an order
     *
     * @var string
     */
    private static $invoice_class = Invoice::class;

    /**
     * Parameter on estimates/invoices that is used as a "Reference number"
     *
     * @var string
     */
    private static $order_ref_param = "Ref";

    /**
     * Are we working with an invoice or an estimate?
     *
     * @var bool
     */
    protected $is_invoice;

    /**
     * An instance of an Invoice/Estimate
     *
     * @var \SilverCommerce\OrdersAdmin\Model\Estimate
     */
    protected $order;

    /**
     * The reference number for the invoice (if null, a new invoice is created)
     *
     * @var int
     */
    protected $ref;

    /**
     * The estimate/invoice ID (if null, a new estimate/invoice is created)
     *
     * @var int
     */
    protected $id;

    /**
     * Create a new instance of the factory and setup the estimate/invoice
     *
     * @param bool $invoice Is this an invoice? If not create estimate.
     * @param int  $id      Provide order id if we want existing estimate/invoice
     * @param int  $ref     Provide reference if we want existing estimate/invoice
     *
     * @return self
     */
    public function __construct($invoice = false, $id = null, $ref = null)
    {
        $this->setIsInvoice($invoice);

        if (isset($id)) {
            $this->setId($id);
        }

        if (isset($ref)) {
            $this->setRef($ref);
        }

        $this->findOrMake();
    }

    /**
     * Attempt to either find an existing order, or make a new one.
     * (based on submitted ID/Ref)
     *
     * @return self
     */
    public function findOrMake()
    {
        $ref_param = $this->config()->order_ref_param;
        $invoice = $this->getIsInvoice();
        $id = $this->getId();
        $ref = $this->getRef();
        $order = null;

        if ($invoice) {
            $class = $this->config()->invoice_class;
        } else {
            $class = $this->config()->estimate_class;
        }

        if (!empty($id)) {
            $order = DataObject::get($class)->byID($id);
        }

        if (!empty($ref)) {
            $order = DataObject::get($class)
                ->filter(
                    [
                        $ref_param => $ref,
                        'ClassName' => $class
                    ]
                )->first();
        }

        // If we have not found an order, create a new one
        if (empty($order)) {
            $order = $class::create();
        }

        $this->order = $order;

        return $this;
    }

    /**
     * Add a line item to the current order based on the provided product
     *
     * @param DataObject $product Instance of the product we want to add
     * @param int        $qty     Quanty of items to add
     * @param bool       $lock    Should this item be locked (cannot change quantity)
     * @param array      $custom  List of customisations to add
     *
     * @return self
     */
    public function addItem(
        DataObject $product,
        int $qty = 1,
        bool $lock = false,
        array $custom = []
    ) {
        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity($qty)
            ->setLock($lock)
            ->setCustomisations($custom)
            ->makeItem()
            ->write();

        $this->addFromLineItemFactory($factory);

        return $this;
    }

    /**
     * Add a new item to the current Estimate/Invoice from a pre-created
     * line item factory
     *
     * *NOTE* this method expects a LineItemFactory ro be pre-written
     *
     * @param LineItemFactory
     *
     * @return self
     */
    public function addFromLineItemFactory(LineItemFactory $factory)
    {
        // First check if this item exists
        $items = $this->getItems();
        $existing = null;

        if ($items->count() > 0) {
            $existing = $items->find("Key", $factory->getKey());
        }

        // If object already in the cart, update quantity and delete new item
        // else add as usual
        if (isset($existing)) {
            $this->updateItem($existing->Key, $factory->getQuantity());
            $factory->delete();
        } else {
            if (!$factory->checkStockLevel()) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . ".NotEnoughStock",
                        "Not enough of '{title}' available",
                        ['title' => $factory->getItem()->Title]
                    )
                );
            } else {
                $this->getItems()->add($factory->getItem());
            }
        }

        return $this;
    }

    /**
     * Update the quantity of a line item from the current order based on the
     * provided key
     *
     * @param string $key       The key of the item to remove
     * @param int    $qty       The amount to increment the item by
     * @param bool   $increment Should the quantity increase or change?
     *
     * @return self
     */
    public function updateItem(string $key, int $qty, bool $increment = true)
    {
        $item = $this->getItems()->find("Key", $key);

        if (!empty($item)) {
            $factory = LineItemFactory::create()->setItem($item)->update();
            $new_qty = ($increment) ? $factory->getQuantity() + $qty : $qty;

            $factory
                ->setQuantity($new_qty)
                ->update();

            if (!$factory->checkStockLevel()) {
                throw new ValidationException(
                    _t(
                        __CLASS__ . ".NotEnoughStock",
                        "Not enough of '{title}' available",
                        ['title' => $factory->getItem()->Title]
                    )
                );
            }

            $factory->write();
        }

        return $this;
    }

    /**
     * Remove a line item from the current order based on the provided key
     *
     * @param string $key The key of the item to remove
     *
     * @return self
     */
    public function removeItem(string $key)
    {
        $item = $this->getItems()->find("Key", $key);

        if (!empty($item)) {
            $item->delete();
        }

        return $this;
    }

    /**
     * Add the provided customer to the Invoice/Estimate
     *
     * @param \SilverCommerce\ContactAdmin\Model\Contact $contact
     *
     * @return self
     */
    public function setCustomer(Contact $contact)
    {
        $this->order->CustomerID = $contact->ID;
    }

    /**
     * Write the currently selected order
     *
     * @return self
     */
    public function write()
    {
        if (!empty($this->order)) {
            $this->order->write();
        }

        return $this;
    }

    /**
     * Delete the current Estimate/Invoice from the DB
     *
     * @return self
     */
    public function delete()
    {
        $order = $this->order;
        
        if (isset($this->order)) {
            $order->delete();
        }

        return $this;
    }

    /**
     * Get an instance of an Invoice/Estimate
     *
     * @return  \SilverCommerce\OrdersAdmin\Model\Estimate
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Get the current Invoice/Estimate items list
     *
     * @throws \SilverStripe\ORM\ValidationException
     *
     * @return \SilverStripe\ORM\SS_List
     */
    protected function getItems()
    {
        $order = $this->getOrder();
        $association = null;
        $associations = array_merge(
            $order->hasMany(),
            $order->manyMany()
        );

        // Find an applicable association
        foreach ($associations as $key => $value) {
            $class = $value::create();
            if (is_a($class, LineItemFactory::ITEM_CLASS)) {
                $association = $key;
                break;
            }
        }

        if (empty($association)) {
            throw new ValidationException(_t(
                __CLASS__ . ".NoItems",
                "The class '{class}' has no item association",
                ['class' => $order->ClassName]
            ));
        }
        
        return $order->{$association}();
    }

    /**
     * Get are we working with an invoice or an estimate?
     *
     * @return boolean
     */
    public function getIsInvoice()
    {
        return $this->is_invoice;
    }

    /**
     * Set are we working with an invoice or an estimate?
     *
     * @param bool $invoice Are we working with an invoice or an estimate?
     *
     * @return self
     */
    public function setIsInvoice(bool $invoice)
    {
        $this->is_invoice = $invoice;
        return $this;
    }

    /**
     * Get the reference number for the invoice (if null, a new invoice is created)
     *
     * @return int
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set the reference number for the invoice (if null, a new invoice is created)
     *
     * @param int $ref reference number
     *
     * @return self
     */
    public function setRef(int $ref)
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * Get the estimate/invoice ID (if null, a new estimate/invoice is created)
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the estimate/invoice ID (if null, a new estimate/invoice is created)
     *
     * @param int $id estimate/invoice ID
     *
     * @return self
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }
}
