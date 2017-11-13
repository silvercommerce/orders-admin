<?php

namespace ilateral\SilverStripe\Orders\Forms\GridField;

use Colymba\BulkManager\BulkManager;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;

/**
 * A {@link BulkManager} for bulk marking orders as dispatched
 *
 * @package orders
 * @subpackage forms
 */
class BulkActions extends BulkManager
{

    private static $allowed_actions = array(
        'cancelled',
        'refunded',
        'pending',
        'paid',
        'partpaid',
        'processing',
        'dispatched',
        'collected'
    );

    private static $url_handlers = array(
        'cancelled' => 'cancelled',
        'refunded'  => 'refunded',
        'pending'   => 'pending',
        'paid'      => 'paid',
        'partpaid'  => 'partpaid',
        'processing'=> 'processing',
        'dispatched'=> 'dispatched',
        'collected' => 'collected'
    );

    public function cancelled(SS_HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markCanceled();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function refunded(SS_HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markRefunded();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

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

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

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

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

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

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

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

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function dispatched(SS_HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markDispatched();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }

    public function collected(SS_HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);

            $record->markCollected();
            $record->write();
        }

        $response = new HTTPResponse(Convert::raw2json(array(
            'done' => true,
            'records' => $ids
        )));

        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }
}
