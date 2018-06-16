<?php
/**
 * User: Baudev
 * Date: 15/06/2018
 */

namespace FCMStream\helpers;

class Configuration
{

    private static $senderID;
    private static $apiKey;
    private static $debugFile;
    private static $debugLevel;
    private static $retryMaxAttempts;
    private static $sleepSecondsBeforeReconnection;
    private static $isDebugMode;
    private static $timeoutConnection;

    /**
     * Environment mode
     */
    CONST DEBUG = true;
    CONST PROD = false;

    /**
     * FCM Server parameters
     */
    public CONST FCM_HOST = 'fcm-xmpp.googleapis.com';
    private CONST FCM_PROD_PORT = 5235;
    private CONST FCM_DEBUG_PORT = 5236;


    /**
     * @return int
     */
    public static function getSenderID() : int
    {
        return self::$senderID;
    }

    /**
     * @param int $senderID
     */
    public static function setSenderID(int $senderID)
    {
        self::$senderID = $senderID;
    }

    /**
     * @return string
     */
    public static function getApiKey() : string
    {
        return self::$apiKey;
    }

    /**
     * @param string $apiKey
     */
    public static function setApiKey(string $apiKey)
    {
        self::$apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public static function getDebugFile()
    {
        return self::$debugFile;
    }

    /**
     * @param mixed $debugFile
     */
    public static function setDebugFile($debugFile)
    {
        self::$debugFile = $debugFile;
    }

    /**
     * @return int
     */
    public static function getDebugLevel() : int
    {
        return self::$debugLevel;
    }

    /**
     * @param int $debugLevel
     */
    public static function setDebugLevel(int $debugLevel)
    {
        self::$debugLevel = $debugLevel;
    }

    /**
     * @return int
     */
    public static function getRetryMaxAttempts() : int
    {
        return self::$retryMaxAttempts;
    }

    /**
     * @param int $retryMaxAttempts
     */
    public static function setRetryMaxAttempts(int $retryMaxAttempts): void
    {
        self::$retryMaxAttempts = $retryMaxAttempts;
    }

    /**
     * @return int
     */
    public static function getSleepSecondsBeforeReconnection() : int
    {
        return self::$sleepSecondsBeforeReconnection;
    }

    /**
     * @param int $sleepSecondsBeforeReconnection
     */
    public static function setSleepSecondsBeforeReconnection(int $sleepSecondsBeforeReconnection): void
    {
        self::$sleepSecondsBeforeReconnection = $sleepSecondsBeforeReconnection;
    }

    /**
     * @return bool
     */
    public static function getIsDebugMode() : bool
    {
        return self::$isDebugMode;
    }

    /**
     * @param bool $isDebugMode
     */
    public static function setIsDebugMode(bool $isDebugMode): void
    {
        self::$isDebugMode = $isDebugMode;
    }

    /**
     * Return the corresponding port depending if the script is in debug mode or not
     * @return int
     */
    public static function getPort() : int
    {
        if(self::getIsDebugMode()){
            return self::FCM_DEBUG_PORT;
        } else {
            return self::FCM_PROD_PORT;
        }
    }

    /**
     * @return mixed
     */
    public static function getTimeoutConnection()
    {
        return self::$timeoutConnection;
    }

    /**
     * @param mixed $timeoutConnection
     */
    public static function setTimeoutConnection($timeoutConnection): void
    {
        self::$timeoutConnection = $timeoutConnection;
    }

}