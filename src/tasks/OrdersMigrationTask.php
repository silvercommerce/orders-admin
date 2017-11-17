<?php

namespace ilateral\SilverStripe\Orders\Tasks;

use SilverStripe\Dev\MigrationTask;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;
use ilateral\SilverStripe\Orders\Model\OrderItem;
use ilateral\SilverStripe\Orders\Model\OrderItemCustomisation;

use SilverStripe\Dev\Debug;

/**
 * Migration task to handle the grunt work of moving from the
 * old Silverstripe Orders Module to the new one 
 * 
 * @package orders
 * @subpackage tasks
 */
class OrdersMigrationTask extends MigrationTask {

    /**
     * Run this task during dev/build
     *
     * @var boolean
     * @config
     */
    private static $run_during_dev_build = true;

    private static $segment = 'OrdersMigrationTask';
    
    protected $title = 'Update Silverstripe Orders Module';

    protected $description = 'Fields that need updating after upgrading the silverstripe orders module';

    public function up() {
        $this->log("Updating Settings");

        $config_count = 0;

        // Get RAW siteconfig data
        $query = new SQLSelect();
        $query->setFrom('SiteConfig');
        $query->selectField('*');
        $result = $query->execute();

        // Get a list of siteconifg objects
        $configs = SiteConfig::get();

        // Iterate over siteconfigs and migrate any
        // altered columns
        foreach ($configs as $site_config) {
            foreach ($result as $row) {
                if (isset($row["ID"]) && $row["ID"] && $row["ID"] == $site_config->ID) {
                    if (isset($row["PaymentNumberPrefix"]) && !$site_config->OrderNumberPrefix) {
                        $site_config->OrderNumberPrefix = $row["PaymentNumberPrefix"];
                        $config_count++;
                    }

                    if (isset($row["OrdersHeader"]) && !$site_config->OrdersHeaderContent) {
                        $site_config->OrdersHeaderContent = $row["OrdersHeader"];
                        $config_count++;
                    }

                    if (isset($row["QuoteFooter"]) && !$site_config->QuoteFooterContent) {
                        $site_config->QuoteFooterContent = $row["QuoteFooter"];
                        $config_count++;
                    }
                    
                    if (isset($row["InvoiceFooter"]) && !$site_config->InvoiceFooterContent) {
                        $site_config->InvoiceFooterContent = $row["InvoiceFooter"];
                        $config_count++;
                    }

                    $site_config->write();
                }
            }
        }

        $this->log("Updated {$config_count} site config settings");
    }

    public function run($request)
    {
        $this->up();
    }

    public function log($message)
    {
        if (Director::is_cli()) {
            echo "{$message}\n";
        } else {
            echo "{$message}<br />";
        }
    }

}
