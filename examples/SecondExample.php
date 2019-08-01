<?php
/**
 * Created by Baudev
 * http://github.com/baudev
 * Date: 19/04/2019
 */

// This example is, contrary to the first one, using callbacks.
// You should prefer using the first example (FirstExample.php)!

require_once __DIR__ . '../vendor/autoload.php';

use FCMStream\Actions;
use FCMStream\Message;

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \\
//   REPLACE 123456789 by your SENDER ID
//       REPLACE SERVER KEY by yours
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \\
$test = new FCMStream\Callbacks(123456789, 'SERVER KEY', 'debugfile.txt', \FCMStream\helpers\Logs::DEBUG);

// onSend callback
$test->setOnSend(function (string $from, string $messageId, Actions $actions){
    echo 'Message has been sent from this server to Firebase one!';
});

// onReceipt callback
$test->setOnReceipt(function (string $from, string $messageId, string $status, int $timestamp, Actions $actions) {
    echo 'The delivery receipt asked for the message ' . $messageId . ' has been received';
});

// onReceiveMessage callback
$test->setOnReceiveMessage(function ($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions){
    echo 'A message has been received:';
    print_r($data);

    // we answer to the message received
    $message = new Message();
    $message->setTo($from); // the recipient is now the receiver
    $message->setMessageId("message_id_test"); // random id
    $message->setPriority(Message::PRIORITY_HIGH); // message priority
    $message->setData(array("test" => "Hello World!")); // the content

    $actions->sendMessage($message); // we send the message
});

// onLoop callback
// To enable this method, you must execute enableOnLoopMethod()
// !! Warning !! Enabling this method can increase a lot the usage of your CPU!
$test->setOnLoop(function (Actions $actions) {
    echo 'This line is called each 5 seconds if you uncomment the line 66! You can send messages here for example';
});

// onFail callback
$test->setOnFail(function (?string $error, ?string $errorDescription, ?string $from, ?string $messageId, Actions $actions){
    echo 'An error has occured:';
    print_r($error);
});

// onExpire callback
$test->setOnExpire(function (string $from, string $newFCMId, Actions $actions){
    echo 'The following FCM Id is expiring: ' . $from.' You must now use the following one: '.$newFCMId;
});

try {
    // $test->enableOnLoopMethod(5 * 1000 * 1000); // enables the onLoop method. She will be called each 5 seconds.
    // Before uncommenting the previous line, see https://github.com/baudev/Firebase-Cloud-Messaging-FCM-XMPP/wiki/References#enableonloopmethodmicroseconds
    $test->stream(); // we start the connection
} catch (\FCMStream\exceptions\FCMConnectionException $e) {
    echo 'Error while connecting to the FCM server: '.$e->getMessage();
}