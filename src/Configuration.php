<?php
/**
 * User: Baudev
 * Date: 13/06/2018
 */

class Configuration
{

    private $senderID;
    private $apiKey;
    private $debugFile;
    private $debugLevel;

    /**
     * Configuration constructor.
     * @param $senderID
     * @param $apiKey
     * @param $debugFile
     * @param $debugLevel
     */
    public function __construct($senderID, $apiKey, $debugFile, $debugLevel)
    {
        $this->setSenderID($senderID);
        $this->setApiKey($apiKey);
        $this->setDebugFile($debugFile);
        $this->setDebugLevel($debugLevel);
    }

    /**
     * @return mixed
     */
    public function getSenderID()
    {
        return $this->senderID;
    }

    /**
     * @param mixed $senderID
     */
    public function setSenderID($senderID)
    {
        $this->senderID = $senderID;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public function getDebugFile()
    {
        return $this->debugFile;
    }

    /**
     * @param mixed $debugFile
     */
    public function setDebugFile($debugFile)
    {
        $this->debugFile = $debugFile;
    }

    /**
     * @return mixed
     */
    public function getDebugLevel()
    {
        return $this->debugLevel;
    }

    /**
     * @param mixed $debugLevel
     */
    public function setDebugLevel($debugLevel)
    {
        $this->debugLevel = $debugLevel;
    }

}