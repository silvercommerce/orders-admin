<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Factory\OrderFactory;
use SilverCommerce\TaxAdmin\Tests\Model\TestProduct;

class OrderFactoryTest extends SapphireTest
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

    public function testFindOrMake()
    {
        $existing = $this->objFromFixture(Estimate::class, 'addressdetails');
        $new_estimate = OrderFactory::create();
        $new_invoice = OrderFactory::create(true);
        $id = OrderFactory::create(false, $existing->ID);
        $ref = OrderFactory::create(false, null, '1232');

        $this->assertNotEmpty($new_estimate->getOrder());
        $this->assertEquals(Estimate::class, $new_estimate->getOrder()->ClassName);
        $this->assertFalse($new_estimate->getOrder()->exists());
        $this->assertEquals(0, $new_estimate->getOrder()->ID);

        $this->assertNotEmpty($new_invoice->getOrder());
        $this->assertEquals(Invoice::class, $new_invoice->getOrder()->ClassName);
        $this->assertFalse($new_invoice->getOrder()->exists());
        $this->assertEquals(0, $new_invoice->getOrder()->ID);

        $this->assertNotEmpty($id->getOrder());
        $this->assertTrue($id->getOrder()->exists());
        $this->assertEquals($existing->ID, $id->getOrder()->ID);

        $this->assertNotEmpty($ref->getOrder());
        $this->assertTrue($ref->getOrder()->exists());
        $this->assertEquals('1232', $ref->getOrder()->Ref);
    }
}
