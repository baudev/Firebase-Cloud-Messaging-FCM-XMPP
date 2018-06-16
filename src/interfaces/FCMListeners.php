<?php
/**
 * User: Baudev
 * Date: 14/06/2018
 */

namespace FCMStream\interfaces;

interface FCMListeners
{
    /**
     * When a message has been sent
     * @param string $from
     * @param string $messageId
     * @return mixed
     */
    public function onSend(string $from, string $messageId);

    /**
     * When a message has been received
     * @param $data
     * @param int $timeToLive
     * @param string $from
     * @param string $messageId
     * @param string $packageName
     * @return mixed
     */
    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName);

    /**
     * When something failed
     * @param string $error
     * @param string $from
     * @return mixed
     */
    public function onFail(string $error, string $from);

    /**
     * When the GCM ID of the recipient has expired
     * @param string $from
     * @param string $newFCMId
     * @return mixed
     */
    public function onExpire(string $from, string $newFCMId);

}