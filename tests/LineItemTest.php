<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Tests\Model\TestProduct;

class LineItemTest extends SapphireTest
{
    /**
     * Add some scaffold order records
     *
     * @var string
     */
    protected static $fixture_file = 'OrdersScaffold.yml';

    /**
     * Setup test only objects
     *
     * @var array
     */
    protected static $extra_dataobjects = [
        TestProduct::class
    ];

    /**notaxitem
     * Add some extra functionality on construction
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Clean up after tear down
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }
    
    /**
     * Test that a line item recieves the correct tax rate
     *
     * @return void
     */
    public function testGetTaxRate()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(0, $item_none->TaxRate);
        $this->assertEquals(5, $item_reduced->TaxRate);
        $this->assertEquals(20, $item_vat->TaxRate);
    }

    /**
     * Test that a line item tracks a single unit price
     *
     * @return void
     */
    public function testGetUnitPrice()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(5.99, $item_none->UnitPrice);
        $this->assertEquals(5.99, $item_reduced->UnitPrice);
        $this->assertEquals(5.99, $item_vat->UnitPrice);
    }

    /**
     * Test that a line item tracks tax for a single item
     *
     * @return void
     */
    public function testGetUnitTax()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(0, $item_none->UnitTax);
        $this->assertEquals(0.2995, $item_reduced->UnitTax);
        $this->assertEquals(1.198, $item_vat->UnitTax);
    }

    /**
     * Test that a line item tracks the total amount for a single item
     *
     * @return void
     */
    public function testGetUnitTotal()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(5.99, $item_none->UnitTotal);
        $this->assertEquals(6.2895, $item_reduced->UnitTotal);
        $this->assertEquals(7.188, $item_vat->UnitTotal);
    }

    /**
     * Test that a line item tracks the total amount (without tax)
     *
     * @return void
     */
    public function testGetSubTotal()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(11.98, $item_none->SubTotal);
        $this->assertEquals(11.98, $item_reduced->SubTotal);
        $this->assertEquals(11.98, $item_vat->SubTotal);
    }

    /**
     * Test that a line item tracks the total amount of tax
     *
     * @return void
     */
    public function testGetTaxTotal()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(0, $item_none->TaxTotal);
        $this->assertEquals(0.599, $item_reduced->TaxTotal);
        $this->assertEquals(2.396, $item_vat->TaxTotal);
    }

    /**
     * Test that a line item outputs the correct total
     *
     * @return void
     */
    public function testGetTotal()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEquals(11.98, $item_none->Total);
        $this->assertEquals(12.579, $item_reduced->Total);
        $this->assertEquals(14.376, $item_vat->Total);
    }

    /**
     * Test that a line item returns the correct customisation summary
     *
     * @return void
     */
    public function testCustomisationList()
    {
        $line_item = $this->objFromFixture(LineItem::class, 'customitem');
        $expected = "Customisation: Free, Customisation: Expensive";

        $this->assertEquals($expected, $line_item->CustomisationList);
    }

    /**
     * Test that a line item returns the correct customisation summary
     *
     * @return void
     */
    public function testCustomisationAndPriceList()
    {
        $line_item = $this->objFromFixture(LineItem::class, 'customitem');
        $expected = "Customisation: Free (£0.00), Customisation: Expensive (£100.00)";

        $this->assertEquals($expected, $line_item->CustomisationAndPriceList);
    }

    /**
     * Test that a line item matches a product correctly
     *
     * @return void
     */
    public function testMatch()
    {
        $line_item = $this->objFromFixture(LineItem::class, 'sockitem');
        $product = $line_item->Match();

        $this->assertTrue(is_object($product));
        $this->assertEquals("Socks", $product->Title);
        $this->assertEquals(5.99, $product->Price);
    }

    /**
     * Test that a line item returns the correct amount of stock
     *
     * @return void
     */
    public function testCheckStockLevel()
    {
        $line_item = $this->objFromFixture(LineItem::class, 'sockitem');

        $this->assertEquals(8, $line_item->checkStockLevel(2));
    }
}
