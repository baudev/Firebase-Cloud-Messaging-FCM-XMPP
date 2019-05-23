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
    private $onLoop;
    private $onReceipt;
    private $onReceiveMessage;
    private $onFail;
    private $onExpire;

    /**
     * Call the callback function
     * @param string $from
     * @param string $messageId
     * @param Actions $actions
     * @return mixed
     */
    public function onSend(string $from, string $messageId, Actions $actions)
    {
        return @call_user_func($this->onSend, $from, $messageId, $actions);
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
     * @param Actions $actions
     * @return mixed
     */
    public function onLoop(Actions $actions)
    {
        return @call_user_func($this->onLoop, $actions);
    }

    /**
     * Set the callback function
     * @param $onLoop
     */
    public function setOnLoop($onLoop)
    {
        $this->onLoop = $onLoop;
    }

    /**
     * Call the callback function
     * @param string $from
     * @param string $messageId
     * @param string $status
     * @param int $timestamp
     * @param Actions $actions
     * @return mixed
     */
    public function onReceipt(string $from, string $messageId, string $status, int $timestamp, Actions $actions)
    {
        return @call_user_func($this->onReceipt, $from, $messageId, $status, $timestamp, $actions);
    }

    /**
     * Set the callback function
     * @param $onReceipt
     */
    public function setOnReceipt($onReceipt)
    {
        $this->onReceipt = $onReceipt;
    }

    /**
     * Call the callback function
     * @param $data
     * @param int $timeToLive
     * @param string $from
     * @param string $messageId
     * @param string $packageName
     * @param Actions $actions
     * @return mixed
     */
    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions)
    {
        return @call_user_func($this->onReceiveMessage, $data, $timeToLive, $from, $messageId, $packageName, $actions);
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
     * @param null|string $error
     * @param null|string $errorDescription
     * @param null|string $from
     * @param null|string $messageId
     * @param Actions $actions
     * @return mixed
     */
    public function onFail(?string $error, ?string $errorDescription, ?string $from, ?string $messageId, Actions $actions)
    {
        return @call_user_func($this->onFail, $error, $errorDescription, $from, $messageId, $actions);
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
     * @param Actions $actions
     * @return mixed
     */
    public function onExpire(string $from, string $newFCMId, Actions $actions)
    {
        return @call_user_func($this->onExpire, $from, $newFCMId, $actions);
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