<?php

namespace SilverCommerce\OrdersAdmin\BulkManager;

use SilverStripe\Control\HTTPRequest;
use Colymba\BulkManager\BulkAction\Handler as GridFieldBulkActionHandler;
use SilverStripe\View\Requirements;

/**
 * A {@link GridFieldBulkActionHandler} for viewing a
 * bulk list of records via the DisplayController
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders-admin
 */
class BulkViewHandler extends GridFieldBulkActionHandler
{
    private static $url_segment = 'bulkview';

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
    protected $label = 'Bulk View';

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
        Requirements::clear();

        return $this->renderWith(
            __CLASS__,
            ['List' => $this->getRecords()]
        );
    }
}
