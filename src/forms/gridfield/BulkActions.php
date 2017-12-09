<?php

namespace SilverCommerce\OrdersAdmin\Forms\GridField;

use Colymba\BulkManager\BulkAction\Handler as GridFieldBulkActionHandler;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;

/**
 * A {@link BulkManager} for bulk marking orders as dispatched
 *
 * @package orders-admin
 * @subpackage forms
 */
class BulkActions extends GridFieldBulkActionHandler
{

    private static $allowed_actions = [
        'cancelled',
        'refunded',
        'pending',
        'paid',
        'partpaid',
        'processing',
        'dispatched',
        'collected'
    ];

    private static $url_handlers = [
        'cancelled' => 'cancelled',
        'refunded'  => 'refunded',
        'pending'   => 'pending',
        'paid'      => 'paid',
        'partpaid'  => 'partpaid',
        'processing'=> 'processing',
        'dispatched'=> 'dispatched',
        'collected' => 'collected'
    ];

    public function cancelled(HTTPRequest $request)
    {
        $ids = [];

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markCanceled();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function refunded(HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markRefunded();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }
    
    public function pending(HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markPending();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function paid(HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markPaid();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function partpaid(HTTPRequest $request)
    {
        $ids = array();

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
    
    public function processing(HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->Status = 'processing';
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function dispatched(HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markDispatched();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json([
            'done' => true,
            'records' => $ids
        ]));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function collected(HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markCollected();
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
