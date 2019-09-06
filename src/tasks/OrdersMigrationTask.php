<?php

namespace SilverCommerce\OrdersAdmin\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\Control\Director;
use SilverStripe\Dev\MigrationTask;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Control\Controller;
use SilverCommerce\GeoZones\Model\Zone;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Subsites\Model\Subsite;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverCommerce\OrdersAdmin\Model\LineItem;

/**
 * Task to handle migrating orders/items to newer versions
 */
class OrdersMigrationTask extends MigrationTask
{

    /**
     * Should this task be invoked automatically via dev/build?
     *
     * @config
     *
     * @var bool
     */
    private static $run_during_dev_build = true;

    private static $segment = 'OrdersMigrationTask';

    protected $description = "Upgrade Orders/Items";

    /**
     * Run this task
     *
     * @param HTTPRequest $request The current request
     *
     * @return void
     */
    public function run($request)
    {
        if ($request->getVar('direction') == 'down') {
            $this->down();
        } else {
            $this->up();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        // Migrate estimate/invoice numbers to new ref field
        $this->log('- Migrating estimate/invoice numbers');
        $total = 0;

        if (class_exists(Subsite::class)) {
            $items = Subsite::get_from_all_subsites(Estimate::class);
        } else {
            $items = Estimate::get();
        }
        
        $count = false;
        if ($items) {
            $this->log('- '.$items->count().' items to convert.');
            $count = $items->count();
        }
        $i = 0;

        foreach ($items as $item) {
            if ($item->Number !== null) {
                $config = SiteConfig::current_site_config();
                $inv_prefix = $config->InvoiceNumberPrefix;
                $est_prefix = $config->EstimateNumberPrefix;
                $number = $item->Number;

                // Strip off current prefix and convert to a ref
                if ($item instanceof Invoice) {
                    $ref = str_replace($inv_prefix . "-", "", $number);
                    $ref = str_replace('-', '', $ref);
                    $item->Ref = (int)$ref;
                    $item->Prefix = $inv_prefix;
                } else {
                    $ref = str_replace($est_prefix . "-", "", $number);
                    $ref = str_replace('-', '', $ref);
                    $item->Ref = (int)$ref;
                    $item->Prefix = $est_prefix;
                }

                $item->Number = null;
                $item->write();
            }
            $i++;
            $this->log('- '.$i.'/'.$count.' items migrated.', true);
        }

        unset($items);

        // Change price/tax on line items to use new fields from extension
        $items = LineItem::get();
        $count = $items->count();
        $this->log("Migrating {$count} Line Items");
        $i = 0;

        foreach ($items as $item) {
            $write = false;

            if ((int)$item->Price && (int)$item->BasePrice == 0) {
                $item->BasePrice = $item->Price;
                $write = true;
            }

            if ($item->TaxID != 0 && $item->TaxRateID == 0) {
                $item->TaxRateID = $item->TaxID;
                $write = true;
            }

            if ($write) {
                $item->write();
                $i++;
            }
        }

        unset($items);
        
        $this->log("Migrated {$i} Line Items");
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $zones = Zone::get();

        $this->log('No Downgrade Required');
    }

    /**
     * @param string $text
     */
    protected function log($text)
    {
        if (Controller::curr() instanceof DatabaseAdmin) {
            DB::alteration_message($text, 'obsolete');
        } elseif (Director::is_cli()) {
            echo $text . "\n";
        } else {
            echo $text . "<br/>";
        }
    }
}
