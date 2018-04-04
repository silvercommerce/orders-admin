<?php

namespace SilverCommerce\CatalogueAdmin\BulkManager;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use Colymba\BulkManager\BulkAction\Handler as GridFieldBulkActionHandler;

/**
 * A {@link GridFieldBulkActionHandler} for bulk cancelling records
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package orders-admin
 */
class PartPaidHandler extends GridFieldBulkActionHandler
{
    private static $url_segment = 'partpaid';

    private static $allowed_actions = [
        'partpaid'
    ];

    private static $url_handlers = [
        "" => "partpaid"
    ];

    /**
     * Front-end label for this handler's action
     *
     * @var string
     */
    protected $label = 'Part Paid';

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
    protected $xhr = true;
    
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
        return _t('OrdersAdmin.PartPaid', $this->getLabel());
    }

    public function partpaid(HTTPRequest $request)
    {
        $ids = [];

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);
            $record->markPartPaid();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }
}
