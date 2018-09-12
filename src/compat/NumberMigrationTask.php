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
        $this->message('Migrating estimate/invoice numbers');
        $total = 0;

        if (class_exists(Subsite::class)) {
            $items = Subsite::get_from_all_subsites(Estimate::class);
        } else {
            $items = Estimate::get();
        }

        foreach ($items as $item) {
            if ($item->Number !== null) {
                $config = SiteConfig::current_site_config();
                $inv_prefix = $config->InvoiceNumberPrefix;
                $est_prefix = $config->EstimateNumberPrefix;
                $number = $item->Number;

                // Strip off current prefix and convert to a ref
                if ($item instanceof Invoice) {
                    $ref = str_replace($inv_prefix . "-", "", $number);
                    $item->Ref = (int)$ref;
                    $item->Prefix = $inv_prefix;
                } else {
                    $ref = str_replace($est_prefix . "-", "", $number);
                    $item->Ref = (int)$ref;
                    $item->Prefix = $est_prefix;
                }

                $item->Number = null;
                $item->write();
                $total++;
            }
        }

        $this->message("Migrated {$total} items");
    }

    /**
     * @param string $text
     */
    protected function message($text)
    {
        if (Controller::curr() instanceof DatabaseAdmin) {
            DB::alteration_message($text, 'obsolete');
        } elseif (Director::is_cli()) {
            echo $text . "\n";
        } else {
            echo $text . "<br/>";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->message('BlogMigrationTask::down() not implemented');
    }
}
