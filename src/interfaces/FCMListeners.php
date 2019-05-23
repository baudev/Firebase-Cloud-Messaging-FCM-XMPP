<?php
/**
 * User: Baudev
 * Date: 14/06/2018
 */

namespace FCMStream\interfaces;

use FCMStream\Actions;

interface FCMListeners
{
    /**
     * When a message has been sent
     * @param string $from
     * @param string $messageId
     * @param Actions $actions
     * @return mixed
     */
    public function onSend(string $from, string $messageId, Actions $actions);
    
    /**
     * When a Loop has completed - hook for sending stuff
     * @param Actions $actions
     * @return mixed
     */
    public function onLoop(Actions $actions);

    /**
     * When a delivery receipt of a message requesting one has been received
     * Take care, this method is not supported for messages sent to iOS devices.
     * @see https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#delivery_receipt
     * @param string $from
     * @param string $messageId
     * @param string $status
     * @param int $timestamp
     * @param Actions $actions
     * @return mixed
     */
    public function onReceipt(string $from, string $messageId, string $status, int $timestamp, Actions $actions);
    
    /**
     * When a message has been received
     * @param $data
     * @param int $timeToLive
     * @param string $from
     * @param string $messageId
     * @param string $packageName
     * @param Actions $actions
     * @return mixed
     */
    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions);

    /**
     * When something failed
     * @param null|string $error
     * @param null|string $errorDescription
     * @param null|string $from
     * @param null|string $messageId
     * @param Actions $actions
     * @return mixed
     */
    public function onFail(?string $error, ?string $errorDescription, ?string $from, ?string $messageId, Actions $actions);

    /**
     * When the FCM ID of the recipient has expired
     * @param string $from
     * @param string $newFCMId
     * @param Actions $actions
     * @return mixed
     */
    public function onExpire(string $from, string $newFCMId, Actions $actions);

}