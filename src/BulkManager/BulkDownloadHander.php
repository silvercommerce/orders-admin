<?php

namespace SilverCommerce\OrdersAdmin\BulkManager;

use Dompdf\Dompdf;
use Dompdf\Options;
use SilverStripe\Core\Path;
use SilverStripe\Control\Director;
use SilverStripe\View\Requirements;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use Colymba\BulkManager\BulkAction\Handler as GridFieldBulkActionHandler;
use DateTime;
use SilverCommerce\OrdersAdmin\Control\DisplayController;

/**
 * A {@link GridFieldBulkActionHandler} for viewing a
 * bulk list of records via the DisplayController
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders-admin
 */
class BulkDownloadHandler extends GridFieldBulkActionHandler
{
    private static $url_segment = 'bulkdownload';

    private static $allowed_actions = [
        'index'
    ];

    private static $url_handlers = [
        "" => "index"
    ];

    /**
     * Front-end label for this handler's action
     *
     * @var string
     */
    protected $label = 'Bulk Download';

    /**
     * Front-end icon path for this handler's action.
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Whether this handler should be called via an XHR from the front-end
     *
     * @var boolean
     */
    protected $xhr = false;
    
    /**
     * Set to true is this handler will destroy any data.
     * A warning and confirmation will be shown on the front-end.
     *
     * @var boolean
     */
    protected $destructive = false;

    /**
     * Return i18n localized front-end label
     *
     * @return array
     */
    public function getI18nLabel()
    {
        return _t('OrdersAdmin.BulkView', $this->getLabel());
    }

    public function index(HTTPRequest $request)
    {
        $date = new DateTime();
        $loader = ModuleResourceLoader::singleton();
        $path = $loader->resolvePath(Config::inst()->get(
            DisplayController::class,
            'pdf_css'
        ));

        // If using a public dir, then apend it's location
        if (Director::publicDir() && defined('RESOURCES_DIR')) {
            // All resources mapped directly to _resources/
            $path = Path::join(RESOURCES_DIR, $path);
        }

        $style = file_get_contents($path);

        Requirements::clear();
        Requirements::customCSS(<<<CSS
        $style
CSS
        );

        $html = $this->renderWith(
            BulkViewHandler::class,
            ['List' => $this->getRecords()]
        );
        $options = new Options([
            "compressed" => true,
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("{$date->getTimestamp()}.pdf");
        exit();
    }
}
