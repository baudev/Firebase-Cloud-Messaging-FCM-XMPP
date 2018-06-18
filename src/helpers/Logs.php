<?php
/**
 * User: Baudev
 * Date: 15/06/2018
 */

namespace FCMStream\helpers;

class Logs
{

    /**
     * Verbose logs levels
     */
    public CONST DEBUG = 2;
    public CONST WARN = 1;
    public CONST ANY = 0;

    /**
     * Function that write logs
     * @param int $level Level of the log. It declares if the log must be written depending of the verbose mode of the script.
     * @param string $title Title
     * @param string $content Content
     */
    public static function writeLog(int $level, string $title, string $content = '')
    {
        if (Configuration::getDebugFile() && $level <= Configuration::getDebugLevel()) {
            echo "=== $title === " . new DatetimeISO8601() . " ===\n";
            echo strlen(trim($content)) ? trim($content) . "\n" : "";
            fwrite(Configuration::getDebugFile(), "=== $title === " . new DatetimeISO8601() . " ===\n" . (strlen(trim($content)) ? trim($content) . "\n" : ""));
        }
    }

}