<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Tests\Model\TestProduct;
use SilverCommerce\OrdersAdmin\Model\Invoice;

class EstimateTest extends SapphireTest
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
     * Test that an estimate converts to invoice correctly
     */
    public function testConvertToInvoice()
    {
        $estimate = $this->objFromFixture(Estimate::class, 'addressdetails');
        $invoice = $estimate->convertToInvoice();

        $this->assertTrue($invoice instanceof Invoice);
    }

    /**
     * Test that an estimate returns the correct deliverable status
     *
     * @return void
     */
    public function testIsDeliverable()
    {
        $deliverable = $this->objFromFixture(Estimate::class, 'deliverable');
        $not_deliverable = $this->objFromFixture(Estimate::class, 'notdeliverable');

        $this->assertTrue($deliverable->isDeliverable());
        $this->assertFalse($not_deliverable->isDeliverable());
    }

    /**
     * Test that the country is retrieved correctly and
     * that billing and delivery addresses return as
     * expected
     *
     * @return void
     */
    public function testLocationDetails()
    {
        $estimate = $this->objFromFixture(Estimate::class, 'addressdetails');

        $bil_country = "United Kingdom";
        $del_country = "United Kingdom";
        $exp_billing = "123 Street Name,\nA Place,\nA City,\nAB12 3AB,\nGB";
        $exp_delivery = "321 Street Name,\nDelivery City,\nZX98 9XZ,\nGB";

        $this->assertEquals($bil_country, $estimate->getCountryFull());
        $this->assertEquals($del_country, $estimate->getDeliveryCountryFull());
        $this->assertEquals($exp_billing, $estimate->getBillingAddress());
        $this->assertEquals($exp_delivery, $estimate->getDeliveryAddress());
    }

    /**
     * test that generation of the summary and summary HTML
     * work as expected.
     *
     * @return void
     */
    public function testItemSummary()
    {
        $estimate = $this->objFromFixture(Estimate::class, 'complextax');

        $text = "2 x An item with reduced tax\n2 x Another tax item";
        $html = "2 x An item with reduced tax<br />\n2 x Another tax item";

        $this->assertEquals($text, $estimate->ItemSummary);
        $this->assertEquals($html, $estimate->ItemSummaryHTML);
    }

    /**
     * test that functions for calculating total amounts (such as)
     * total items, total weight, etc.
     *
     * @return void
     */
    public function testTotalCalculations()
    {
        $no_tax_order = $this->objFromFixture(Estimate::class, 'standardnotax');
        $tax_order = $this->objFromFixture(Estimate::class, 'standardtax');

        $this->assertEquals(2, $no_tax_order->TotalItems);
        $this->assertEquals(1, $no_tax_order->TotalWeight);
        $this->assertEquals(2, $tax_order->TotalItems);
        $this->assertEquals(1.5, $tax_order->TotalWeight);
    }

    /**
     * test that functions for calculating tax monitary info on
     * an order are correct
     *
     * @return void
     */
    public function testTaxCalculations()
    {
        $no_tax_order = $this->objFromFixture(Estimate::class, 'standardnotax');
        $tax_order_one = $this->objFromFixture(Estimate::class, 'standardtax');
        $tax_order_two = $this->objFromFixture(Estimate::class, 'complextax');

        $this->assertEquals(0, $no_tax_order->TaxTotal);
        $this->assertEquals(2.39, $tax_order_one->TaxTotal);
        $this->assertEquals(2.99, $tax_order_two->TaxTotal);
    }

    /**
     * test that functions for calculating monitary info on
     * an order are correct (such as tax, total, etc)
     *
     * @return void
     */
    public function testCurrencyCalculations()
    {
        $no_tax_order = $this->objFromFixture(Estimate::class, 'standardnotax');
        $tax_order_one = $this->objFromFixture(Estimate::class, 'standardtax');
        $tax_order_two = $this->objFromFixture(Estimate::class, 'complextax');

        $this->assertEquals(11.98, $no_tax_order->SubTotal);
        $this->assertEquals(11.98, $no_tax_order->Total);
        $this->assertEquals(11.98, $tax_order_one->SubTotal);
        $this->assertEquals(14.37, $tax_order_one->Total);
        $this->assertEquals(23.96, $tax_order_two->SubTotal);
        $this->assertEquals(26.95, $tax_order_two->Total);
    }
}
