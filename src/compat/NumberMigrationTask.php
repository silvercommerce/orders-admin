<?php

namespace SilverCommerce\OrdersAdmin\Compat;

use SilverStripe\ORM\DB;
use SilverStripe\Control\Director;
use SilverStripe\Dev\MigrationTask;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\SiteConfig\SiteConfig;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverCommerce\OrdersAdmin\Model\Estimate;
use SilverStripe\Subsites\Model\Subsite;

class NumberMigrationTask extends MigrationTask
{

    private static $segment = 'OrderNumberMigrationTask';

    protected $title = "Migrate order numbers to seperate ref/prefix";
    
    protected $description = "Provide atomic database changes (not implemented yet)";

    /**
     * Should this task be invoked automatically via dev/build?
     *
     * @config
     *
     * @var bool
     */
    private static $run_during_dev_build = true;

    public function run($request)
    {
        if ($request->param('Direction') == 'down') {
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
        $this->message('- Migrating estimate/invoice numbers');
        $total = 0;

        if (class_exists(Subsite::class)) {
            $items = Subsite::get_from_all_subsites(Estimate::class);
        } else {
            $items = Estimate::get();
        }
        
        $count = false;
        if ($items) {
            $this->message('- '.$items->count().' items to convert.');
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
            $this->message('- '.$i.'/'.$count.' items migrated.', true);
        }
    }

    /**
     * @param string $text
     */
    protected function message($text, $linestart = false)
    {
        if (Director::is_cli()) {
            $end = ($linestart) ? "\r" : "\n";
            echo $text . $end;
        } else {
            echo $text . "<br/>";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->message('NumberMigrationTask::down() not implemented');
    }
}
