<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\i18n\i18n;
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

    /**
     * Add some extra functionality on construction
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        i18n::set_locale('en_GB');
    }
    
    /**
     * Test that a line item recieves the correct tax rate
     *
     * @return void
     */
    public function testGetTaxPercentage()
    {
        $item_none = $this->objFromFixture(LineItem::class, 'notaxitem');
        $item_reduced = $this->objFromFixture(LineItem::class, 'reducedtaxitem');
        $item_vat = $this->objFromFixture(LineItem::class, 'taxitemone');

        $this->assertEqualsWithDelta(0, $item_none->TaxPercentage, 0.001);
        $this->assertEqualsWithDelta(5, $item_reduced->TaxPercentage, 0.001);
        $this->assertEqualsWithDelta(20, $item_vat->TaxPercentage, 0.001);
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

        $this->assertEqualsWithDelta(6.50, $item_none->UnitPrice, 0.0001);
        $this->assertEqualsWithDelta(5.99, $item_reduced->UnitPrice, 0.0001);
        $this->assertEqualsWithDelta(5.99, $item_vat->UnitPrice, 0.0001);
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

        $this->assertEqualsWithDelta(0, $item_none->UnitTax, 0.01);
        $this->assertEqualsWithDelta(0.2995, $item_reduced->UnitTax, 0.00001);
        $this->assertEqualsWithDelta(1.198, $item_vat->UnitTax, 0.0001);
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

        $this->assertEqualsWithDelta(6.5, $item_none->UnitTotal, 0.001);
        $this->assertEqualsWithDelta(6.2895, $item_reduced->UnitTotal, 0.00001);
        $this->assertEqualsWithDelta(7.188, $item_vat->UnitTotal, 0.0001);
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

        $this->assertEqualsWithDelta(13.00, $item_none->SubTotal, 0.001);
        $this->assertEqualsWithDelta(11.98, $item_reduced->SubTotal, 0.001);
        $this->assertEqualsWithDelta(11.98, $item_vat->SubTotal, 0.001);
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

        $this->assertEqualsWithDelta(0, $item_none->TaxTotal, 0.01);
        $this->assertEqualsWithDelta(0.599, $item_reduced->TaxTotal, 0.0001);
        $this->assertEqualsWithDelta(2.396, $item_vat->TaxTotal, 0.0001);
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

        $this->assertEqualsWithDelta(13.00, $item_none->Total, 0.0001);
        $this->assertEqualsWithDelta(12.579, $item_reduced->Total, 0.0001);
        $this->assertEqualsWithDelta(14.376, $item_vat->Total, 0.0001);
    }

    /**
     * Test that a line item returns the correct customisation summary
     *
     * @return void
     */
    public function testCustomisationList()
    {
        $line_item = $this->objFromFixture(LineItem::class, 'customitem');
        $expected_one = "Customisation: Free";
        $expected_two = "Customisation: Expensive";
        $check = $line_item->CustomisationList;

        $this->assertTrue(strpos($check, $expected_one) !== false);
        $this->assertTrue(strpos($check, $expected_two) !== false);
    }

    /**
     * Test that a line item returns the correct customisation summary
     *
     * @return void
     */
    public function testCustomisationAndPriceList()
    {
        $line_item = $this->objFromFixture(LineItem::class, 'customitem');
        $expected_one = "Customisation: Free (£0.00)";
        $expected_two = "Customisation: Expensive (£100.00)";
        $check = $line_item->CustomisationAndPriceList;

        $this->assertTrue(strpos($check, $expected_one) !== false);
        $this->assertTrue(strpos($check, $expected_two) !== false);
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
        $this->assertEqualsWithDelta(5.99, $product->NoTaxPrice, 0.001);
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
