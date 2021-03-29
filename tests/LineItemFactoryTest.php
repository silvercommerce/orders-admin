<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ValidationException;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;

class LineItemFactoryTest extends SapphireTest
{
    protected static $fixture_file = 'OrdersScaffold.yml';

    public function testMakeItem()
    {
        $socks = $this->objFromFixture(CatalogueProduct::class, 'socks');
        $basic_item = LineItemFactory::create()
            ->setProduct($socks)
            ->setQuantity(3)
            ->makeItem()
            ->getItem();

        $this->assertNotEmpty($basic_item);
        $this->assertEquals("Socks", $basic_item->Title);
        $this->assertEquals(3, $basic_item->Quantity);
        $this->assertEquals(5.99, $basic_item->BasePrice);
        $this->assertEquals(17.97, $basic_item->Total);
        $this->assertEquals(CatalogueProduct::class, $basic_item->ProductClass);

        $notax = $this->objFromFixture(CatalogueProduct::class, 'notax');
        $notax_item = LineItemFactory::create()
            ->setProduct($notax)
            ->setQuantity(5)
            ->makeItem()
            ->getItem();

        $this->assertNotEmpty($notax_item);
        $this->assertEquals("No Tax Item", $notax_item->Title);
        $this->assertEquals(5, $notax_item->Quantity);
        $this->assertEquals(6.50, $notax_item->BasePrice);
        $this->assertEquals(32.50, $notax_item->Total);
        $this->assertEquals(CatalogueProduct::class, $notax_item->ProductClass);

        $this->expectException(ValidationException::class);
        LineItemFactory::create()->makeItem();
    }

    public function testUpdate()
    {
        $socks = $this->objFromFixture(CatalogueProduct::class, 'socks');
        $notax = $this->objFromFixture(CatalogueProduct::class, 'notax');

        $factory = LineItemFactory::create()
            ->setProduct($socks)
            ->setQuantity(1)
            ->makeItem();
    
        $this->assertEquals(1, $factory->getItem()->Quantity);
        $this->assertEquals(5.99, $factory->getItem()->BasePrice);

        $factory->setQuantity(3);
        $this->assertEquals(1, $factory->getItem()->Quantity);

        $factory->update();
        $this->assertEquals(3, $factory->getItem()->Quantity);

        $factory->setProduct($notax)->update();
        $this->assertEquals(3, $factory->getItem()->Quantity);
        $this->assertEquals(6.50, $factory->getItem()->BasePrice);
    }

    public function testFindBestTaxRate()
    {
        // First test a static rate
        $item = $this->objFromFixture(LineItem::class, 'taxitemone');
        $estimate = $item->Parent();
        $factory = LineItemFactory::create()
            ->setItem($item)
            ->setParent($estimate);

        $this->assertEquals(20, $item->TaxPercentage);
        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "NZ";
        $estimate->DeliveryCounty = "AUK";
        $factory->setParent($estimate);

        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "US";
        $estimate->DeliveryCounty = "AL";
        $factory->setParent($estimate);

        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "DE";
        $estimate->DeliveryCounty = "BE";
        $factory->setParent($estimate);

        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        // Now test a more flexible category
        $item = $this->objFromFixture(LineItem::class, 'taxtestableuk');
        $estimate = $item->Parent();
        $factory = LineItemFactory::create()
            ->setItem($item)
            ->setParent($estimate);

        $this->assertEquals(0, $item->TaxPercentage);
        $this->assertEquals(20, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "NZ";
        $estimate->DeliveryCounty = "AUK";
        $factory->setParent($estimate);

        $this->assertEquals(5, $factory->findBestTaxRate()->Rate);

        $estimate->DeliveryCountry = "US";
        $estimate->DeliveryCounty = "AL";
        $factory->setParent($estimate);

        $this->assertEquals(0, $factory->findBestTaxRate()->Rate);

        // Erronious result should return 0
        $estimate->DeliveryCountry = "DE";
        $estimate->DeliveryCounty = "BE";
        $factory->setParent($estimate);

        $this->assertEquals(0, $factory->findBestTaxRate()->Rate);
    }

    public function testCheckStockLevel()
    {
        $item = $this->objFromFixture(LineItem::class, 'sockitem');
        $factory = LineItemFactory::create()
            ->setItem($item);

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(5)
            ->update();

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(9)
            ->update();

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(10)
            ->update();

        $this->assertTrue($factory->checkStockLevel());

        $factory
            ->setQuantity(15)
            ->update();

        $this->assertFalse($factory->checkStockLevel());

        $factory
            ->setQuantity(20)
            ->update();

        $this->assertFalse($factory->checkStockLevel());
    }
}
