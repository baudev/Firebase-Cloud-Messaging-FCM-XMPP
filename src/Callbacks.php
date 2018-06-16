<?php
/**
 * User: Baudev
 * Date: 14/06/2018
 */

namespace FCMStream;

class Callbacks extends Core
{

    // TODO comment methods

    private $onSend;
    private $onReceiveMessage;
    private $onFail;
    private $onExpire;

    /**
     * Call the callback function
     * @param string $from
     * @param string $messageId
     * @return mixed
     */
    public function onSend(string $from, string $messageId)
    {
        return @call_user_func($this->onSend, $from, $messageId);
    }

    /**
     * Set the callback function
     * @param mixed $onSend
     */
    public function setOnSend($onSend)
    {
        $this->onSend = $onSend;
    }

    /**
     * Call the callback function
     * @param $data
     * @param int $timeToLive
     * @param string $from
     * @param string $messageId
     * @param string $packageName
     * @return mixed
     */
    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName)
    {
        return @call_user_func($this->onReceiveMessage, $data, $timeToLive, $from, $messageId, $packageName);
    }

    /**
     * Set the callback function
     * @param mixed $onReceiveMessage
     */
    public function setOnReceiveMessage($onReceiveMessage)
    {
        $this->onReceiveMessage = $onReceiveMessage;
    }

    /**
     * Call the callback function
     * @param string $error
     * @param string $from
     * @return mixed
     */
    public function onFail(string $error, string $from)
    {
        return @call_user_func($this->onFail, $error, $from);
    }

    /**
     * Set the callback function
     * @param mixed $onFail
     */
    public function setOnFail($onFail)
    {
        $this->onFail = $onFail;
    }

    /**
     * Call the callback function
     * @param string $from
     * @param string $newFCMId
     * @return mixed
     */
    public function onExpire(string $from, string $newFCMId)
    {
        return @call_user_func($this->onExpire, $from, $newFCMId);
    }

    /**
     * Set the callback function
     * @param mixed $onExpire
     */
    public function setOnExpire($onExpire)
    {
        $this->onExpire = $onExpire;
    }


}