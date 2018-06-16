<?php
/**
 * User: Baudev
 * Date: 15/06/2018
 */

namespace FCMStream\helpers;

use DateTime;

class DatetimeISO8601 extends DateTime
{
    /**
     * Format date string output to ISO8601 format
     */
    public function __toString() : string
    {
        return $this->format(DateTime::ISO8601);
    }

}