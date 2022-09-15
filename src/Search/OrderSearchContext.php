<?php

namespace SilverCommerce\OrdersAdmin\Search;

use DateTime;
use SilverStripe\Forms\DateField;
use SilverStripe\Versioned\Versioned;
use ilateral\SilverStripe\ModelAdminPlus\SearchContext;

class OrderSearchContext extends SearchContext
{
    public function getSearchFields()
    {
        $fields = parent::getSearchFields();

        // Add date filter fields
        $fields->insertAfter(
            'Ref',
            DateField::create(
                "StartDate",
                _t('OrdersAdmin.PlacedAfter', 'Placed After')
            )
        );

        $fields->insertAfter(
            "StartDate",
            DateField::create(
                "EndDate",
                _t('OrdersAdmin.PlacedBefore', 'Placed Before')
            )
        );

        return $fields;
    }

    /**
     * Overwrite the default query to perform custom filters
     */
    public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null)
    {
        if (!$existingQuery && is_array($searchParams) && array_key_exists("ShowDeleted", $searchParams)) {
            $existingQuery = Versioned::get_including_deleted($this->modelClass);
        }

        $list = parent::getQuery($searchParams, $sort, $limit, $existingQuery);

        $start = null;
        $end = null;

        if (array_key_exists("StartDate", $searchParams)) {
            $start = new DateTime($searchParams["StartDate"]);
            $start->modify('today');
        }

        if (array_key_exists("EndDate", $searchParams)) {
            $end = new DateTime($searchParams["EndDate"]);
            $end
                ->modify('tomorrow')
                ->modify('-1 second');
        }

        if ($start && $end) {
            $list = $list->filter([
                'StartDate:GreaterThanOrEqual' => $start->format('Y-m-d'),
                'StartDate:LessThanOrEqual' => $end->format('Y-m-d')
            ]);
        }

        return $list;
    }
}
