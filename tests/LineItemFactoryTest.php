<?php

namespace SilverCommerce\OrdersAdmin\Tests;

use SilverStripe\i18n\i18n;
use SilverStripe\Dev\SapphireTest;
use SilverCommerce\GeoZones\Model\Zone;
use SilverStripe\ORM\ValidationException;
use SilverCommerce\TaxAdmin\Model\TaxRate;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use SilverCommerce\OrdersAdmin\Model\PriceModifier;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;
use SilverCommerce\OrdersAdmin\Model\LineItemCustomisation;

class LineItemFactoryTest extends SapphireTest
{
    protected static $fixture_file = 'OrdersScaffold.yml';

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

        /** Test item creation using new versioned products */
        $versioned_product_one = CatalogueProduct::create();
        $versioned_product_one->Title = "Simple Versioned Product";
        $versioned_product_one->StockID = "SVP-1";
        $versioned_product_one->BasePrice = 6.75;
        $versioned_product_one->write();

        $versioned_item = LineItemFactory::create()
            ->setProduct($versioned_product_one)
            ->setQuantity(1)
            ->makeItem()
            ->getItem();

        $item_id = $versioned_item->write();
        $versioned_product_one->BasePrice = 7.50;
        $versioned_product_one->write();

        $versioned_item = LineItem::get()->byID($item_id);
        $this->assertNotEmpty($versioned_item);

        $versioned_product_one = $versioned_item->findStockItem();
        $this->assertNotEmpty($versioned_product_one);
        $this->assertEquals(1, $versioned_item->ProductVersion);
        $this->assertEquals(1, $versioned_product_one->Version);

        $this->assertEquals("Simple Versioned Product", $versioned_item->Title);
        $this->assertEquals(1, $versioned_item->Quantity);
        $this->assertEquals(6.75, $versioned_item->BasePrice);
        $this->assertEquals(6.75, $versioned_item->Total);
        $this->assertEquals(CatalogueProduct::class, $versioned_item->ProductClass);

        /** Test item creation using new versioned product between price changes */
        $versioned_product_two = CatalogueProduct::create();
        $versioned_product_two->Title = "Another Versioned Product";
        $versioned_product_two->StockID = "AVP-1";
        $versioned_product_two->BasePrice = 5.25;
        $versioned_product_two->write();

        $versioned_product_two->BasePrice = 7.50;
        $versioned_product_two->write();

        $versioned_item = LineItemFactory::create()
            ->setProduct($versioned_product_two)
            ->setQuantity(3)
            ->makeItem()
            ->getItem();
        $item_id = $versioned_item->write();

        $versioned_product_two->BasePrice = 6.99;
        $versioned_product_two->write();

        $versioned_item = LineItem::get()->byID($item_id);
        $this->assertNotEmpty($versioned_item);

        $this->assertEquals(3, $versioned_product_two->Version);
        $versioned_product_two = $versioned_item->findStockItem();
        $this->assertNotEmpty($versioned_product_two);
        $this->assertEquals(2, $versioned_item->ProductVersion);
        $this->assertEquals(2, $versioned_product_two->Version);

        $this->assertEquals("Another Versioned Product", $versioned_item->Title);
        $this->assertEquals(3, $versioned_item->Quantity);
        $this->assertEquals(7.50, $versioned_item->BasePrice);
        $this->assertEquals(22.5, $versioned_item->Total);
        $this->assertEquals(CatalogueProduct::class, $versioned_item->ProductClass);

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

    public function testCustomise()
    {
        $item = /** Test item creation using new versioned products */
        $product = CatalogueProduct::create();
        $product->Title = "A-Nother Product";
        $product->StockID = "ANP-222";
        $product->BasePrice = 2.50;
        $product->write();

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->makeItem()
            ->write();

        $customisation = $factory->customise('Colour', "Blue");

        $this->assertNotEmpty($customisation);
        $this->assertInstanceOf(LineItemCustomisation::class, $customisation);
        $this->assertEquals("Colour", $customisation->Title);
        $this->assertEquals("Blue", $customisation->Value);

        $this->assertCount(1, $factory->getItem()->Customisations());
    }

    public function testModifyPrice()
    {
        $item = /** Test item creation using new versioned products */
        $product = CatalogueProduct::create();
        $product->Title = "A-Nother Product";
        $product->StockID = "ANP-333";
        $product->BasePrice = 2.50;
        $product->write();

        $factory = LineItemFactory::create()
            ->setProduct($product)
            ->setQuantity(1)
            ->makeItem()
            ->write();

        $modification = $factory->modifyPrice('Large', 1.50);
        $item = $factory->getItem();

        $this->assertNotEmpty($modification);
        $this->assertInstanceOf(PriceModifier::class, $modification);
        $this->assertEquals("Large", $modification->Name);
        $this->assertEquals(1.5, $modification->ModifyPrice);
        $this->assertEquals(2.5, $item->BasePrice);
        $this->assertEquals(4, $item->NoTaxPrice);

        $this->assertCount(1, $item->PriceModifications());
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
        
        $rate = $factory->findBestTaxRate();

        $this->assertEquals(0, $item->TaxPercentage);
        $this->assertEquals(20, $rate->Rate);

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
