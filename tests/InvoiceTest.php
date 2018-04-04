<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Tests\Model\TestProduct;

class InvoiceTest extends SapphireTest
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
     * Test that mark paid flags the order as paid
     *
     * @return void
     */
    public function testMarkPaid()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markPaid();

        $this->assertEquals("paid", $invoice->Status);
        $this->assertTrue($invoice->isPaid());
    }
    
    public function testMarkPartPaid()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markPartPaid();

        $this->assertEquals("part-paid", $invoice->Status);
    }

    public function testMarkPending()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markPending();

        $this->assertEquals("pending", $invoice->Status);
    }

    public function testMarkProcessing()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markProcessing();

        $this->assertEquals("processing", $invoice->Status);
    }

    public function testMarkCanceled()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markCanceled();

        $this->assertEquals("canceled", $invoice->Status);
    }

    public function testMarkRefunded()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markRefunded();

        $this->assertEquals("refunded", $invoice->Status);
    }

    public function testMarkDispatched()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markDispatched();

        $this->assertEquals("dispatched", $invoice->Status);
    }

    public function testMarkCollected()
    {
        $invoice = $this->objFromFixture(Invoice::class, 'unpaid');
        $invoice->markCollected();

        $this->assertEquals("collected", $invoice->Status);
    }
}
