<?php

namespace SilverCommerce\OrdersAdmin\Tasks;

use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
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
use SilverCommerce\OrdersAdmin\Model\LineItemCustomisation;
use SilverCommerce\OrdersAdmin\Model\PriceModifier;

/**
 * Task to handle migrating orders/items to newer versions
 */
class OrdersMigrationTask extends MigrationTask
{
    const CHUNK_SIZE = 200;

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
        $chunk_size = self::CHUNK_SIZE;
        $curr_chunk = 0;
        $migrated = 0;
        $start_time = time();

        if (class_exists(Subsite::class)) {
            $estimates = Subsite::get_from_all_subsites(Estimate::class);
        } else {
            $estimates = Estimate::get();
        }

        $items = LineItem::get();
        $customisations = LineItemCustomisation::get();

        $this->processChunkedList($estimates, $start_time);
        $this->processChunkedList($items, $start_time);
        $this->processChunkedList($customisations, $start_time);

        // purge current var
        $estimates = null;
        $items = null;
        $customisations = null;
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->log('Downgrade Not Possible');
    }

    protected function processChunkedList(
        DataList $list,
        int $start_time
    ) {
        $chunk_size = self::CHUNK_SIZE;
        $curr_chunk = 0;
        $migrated = 0;
        $items_count = $list->count();
        $total_chunks = 1;
        $class = $list->dataClass();

        // If we have a usable list, calculate total chunks
        if ($items_count > 0) {
            $this->log("- {$items_count} {$class} to migrate.");

            // Round up the total chunks, so stragglers are caught
            $total_chunks = ceil(($items_count / $chunk_size));
        }

        /**
         * Break line items list into chunks to save memory
         *
         * @var DataList $estimates
         */
        while ($curr_chunk < $total_chunks) {
            $chunked_list =  $list
                ->limit($chunk_size, $curr_chunk * $chunk_size);

            foreach ($chunked_list as $item) {
                $result = 0;

                if ($item instanceof Estimate) {
                    $result = $this->convertEstimate($item);
                } elseif ($item instanceof LineItem) {
                    $result = $this->convertLineItem($item);
                } elseif ($item instanceof LineItemCustomisation) {
                    $result = $this->convertCustomisation($item);
                }

                if ($result === $item->ID) {
                    $migrated++;
                }
            }

            $chunked_time = time() - $start_time;

            $this->log(
                "- Migrated {$migrated} of {$items_count} in {$chunked_time}s",
                true
            );

            $curr_chunk++;
        }

        // purge current var
        $chunked_list = null;
    }

    protected function convertEstimate(Estimate $estimate): int
    {
        if ($estimate->Number !== null) {
            $config = SiteConfig::current_site_config();
            $inv_prefix = $config->InvoiceNumberPrefix;
            $est_prefix = $config->EstimateNumberPrefix;
            $number = $estimate->Number;

            // Strip off current prefix and convert to a ref
            if ($estimate instanceof Invoice) {
                $ref = str_replace($inv_prefix . "-", "", $number);
                $ref = str_replace('-', '', $ref);
                $estimate->Ref = (int)$ref;
                $estimate->Prefix = $inv_prefix;
            } else {
                $ref = str_replace($est_prefix . "-", "", $number);
                $ref = str_replace('-', '', $ref);
                $estimate->Ref = (int)$ref;
                $estimate->Prefix = $est_prefix;
            }

            $estimate->Number = null;
            return $estimate->write();
        }

        return 0;
    }

    protected function convertLineItem(LineItem $item): int
    {
        $write = false;

        if (intval($item->Price) > 0 && intval($item->BasePrice) == 0) {
            $item->UnmodifiedPrice = $item->Price;
            $write = true;
        }

        if (intval($item->BasePrice) > 0) {
            $item->UnmodifiedPrice = $item->BasePrice;
            $write = true;
        }

        if ($write) {
            return $item->write();
        }

        return 0;
    }

    protected function convertCustomisation(LineItemCustomisation $item): int
    {
        $write = false;

        // If legacy Price field is set, migrate it
        if ($item->Price > 0 && intval($item->BasePrice) === 0) {
            $item->BasePrice = $item->Price;
            $item->Price = 0;
            $write = true;
        }

        // If legacy Price field is set, migrate it
        if (!empty($item->Title) && empty($item->Name)) {
            $item->Name = $item->Title;
            $item->Title = null;
            $write = true;
        }

        // If customisation modifies price, generate modification
        //  and link to this customisation
        if ($item->BasePrice > 0) {
            $modifier = PriceModifier::create();
            $modifier->Name = $item->Name;
            $modifier->ModifyPrice = $item->BasePrice;
            $modifier->LineItemID = $item->ParentID;
            $modifier->CustomisationID = $item->ID;
            $modifier->write();

            $item->BasePrice = 0;
            $write = true;
        }

        if ($write) {
            return $item->write();
        }

        return 0;
    }

    /**
     * Log a message to the terminal/browser
     * 
     * @param string $message   Message to log
     * @param bool   $linestart Set cursor to start of line (instead of return)
     * 
     * @return null
     */
    protected function log($message, $linestart = false)
    {
        if (Director::is_cli()) {
            $end = ($linestart) ? "\r" : "\n";
            print_r($message . $end);
        } else {
            print_r($message . "<br/>");
        }
    }
}
