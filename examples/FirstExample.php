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
     * @param string $from
     * @param string $messageId
     * @param Actions $actions
     * @return mixed|void
     */
    public function onSend(string $from, string $messageId, Actions $actions) {
        echo 'Message has been sent from this Server!';
    }

    /**
     * @param $data
     * @param int $timeToLive
     * @param string $from
     * @param string $messageId
     * @param string $packageName
     * @param Actions $actions
     * @return mixed|void
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
     * @param string $error
     * @param string $errorDescription
     * @param string $from
     * @param string $messageId
     * @param Actions $actions
     * @return mixed|void
     */
    public function onFail(?string $error, ?string $errorDescription, ?string $from, ?string $messageId, Actions $actions) {
        echo 'An error has occured:';
        print_r($error);
    }

    /**
     * @param string $from
     * @param string $newFCMId
     * @param Actions $actions
     * @return mixed|void
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
    $test->stream(); // we start the connection
} catch (\FCMStream\exceptions\FCMConnectionException $e) {
    echo 'Error while connecting to the FCM server: '.$e->getMessage();
}