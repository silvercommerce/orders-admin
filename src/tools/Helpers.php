<?php

namespace SilverCommerce\OrdersAdmin\Tools;

use SilverStripe\View\ViewableData;


class Helpers extends ViewableData
{
    /**
     * Rounds up a float to a specified number of decimal places
     * (basically acts like ceil() but allows for decimal places)
     *
     * @param float $value Float to round up
     * @param int $places the number of decimal places to round to
     * @return float
     */
    public static function round_up($value, $places = 0)
    {
        $offset = 0.5;

        if ($places !== 0) {
            $offset /= pow(10, $places);
        }
        
        $return = round(
            $value + $offset,
            $places,
            PHP_ROUND_HALF_DOWN
        );
        
        return $return;
    }
} 