<?php
/**
 * Created by Baudev
 * http://github.com/baudev
 * Date: 19/04/2019
 */

require_once __DIR__ . '../vendor/autoload.php';

use FCMStream\Actions;
use FCMStream\Core;
use FCMStream\Message;


class FirstExample extends Core {

    /**
     * When a message has been sent to the Firebase server
     * @param string $from
     * @param string $messageId
     * @param Actions $actions
     */
    public function onSend(string $from, string $messageId, Actions $actions) {
        echo 'Message has been sent from this server to Firebase one!';
    }

    /**
     * When a delivery receipt of a message requesting one has been received
     * Take care, this method is not supported for messages sent to iOS devices.
     * @see https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#delivery_receipt
     * @param string $from
     * @param string $messageId
     * @param string $status
     * @param int $timestamp
     * @param Actions $actions
     */
    public function onReceipt(string $from, string $messageId, string $status, int $timestamp, Actions $actions)
    {
        echo 'The delivery receipt asked for the message ' . $messageId . ' has been received';
    }

    /**
     * When a message has been received
     * @param $data
     * @param int $timeToLive
     * @param string $from
     * @param string $messageId
     * @param string $packageName
     * @param Actions $actions
     * @throws \FCMStream\exceptions\FCMConnectionException
     * @throws \FCMStream\exceptions\FCMMessageFormatException
     */
    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions) {
        echo 'A message has been received:';
        print_r($data);

        // we answer to the message received
        $message = new Message();
        $message->setTo($from); // the recipient is now the receiver
        $message->setMessageId("message_id_test"); // random id
        $message->setPriority(Message::PRIORITY_HIGH); // message priority
        $message->setData(array("test" => "Hello World!")); // the content

        $actions->sendMessage($message); // we send the message
    }

    /**
     * The method is executed each X microseconds.
     * To enable this method, you must execute enableOnLoopMethod()
     * !! Warning !! Enabling this method can increase a lot the usage of your CPU!
     * @param Actions $actions
     */
    public function onLoop(Actions $actions)
    {
        echo 'This line is called each 5 seconds if you uncomment the line 108! You can send messages here for example';
    }

    /**
     * When something failed
     * @param null|string $error
     * @param null|string $errorDescription
     * @param null|string $from
     * @param null|string $messageId
     * @param Actions $actions
     */
    public function onFail(?string $error, ?string $errorDescription, ?string $from, ?string $messageId, Actions $actions) {
        echo 'An error has occured:';
        print_r($error);
    }

    /**
     * When the FCM ID of the recipient has expired
     * @param string $from
     * @param string $newFCMId
     * @param Actions $actions
     */
    public function onExpire(string $from, string $newFCMId, Actions $actions) {
        echo 'The following FCM Id is expiring: ' . $from.' You must now use the following one: '.$newFCMId;
    }
}

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \\
//   REPLACE 123456789 by your SENDER ID
//       REPLACE SERVER KEY by yours
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \\
$test = new FirstExample(123456789, 'SERVER KEY', 'debugfile.txt', \FCMStream\helpers\Logs::DEBUG);
try {
    // $test->enableOnLoopMethod(5 * 1000 * 1000); // enables the onLoop method. She will be called each 5 seconds.
    // Before uncommenting the previous line, see https://github.com/baudev/Firebase-Cloud-Messaging-FCM-XMPP/wiki/References#enableonloopmethodmicroseconds
    $test->stream(); // we start the connection
} catch (\FCMStream\exceptions\FCMConnectionException $e) {
    echo 'Error while connecting to the FCM server: '.$e->getMessage();
}