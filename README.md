
# Firebase Cloud Messaging (FCM) XMPP - PHP  
This PHP program, based on the unmaintained [sourc7/FCMStream](https://github.com/sourc7/FCMStream) repository, allows receiving and sending messages with the XMPP Protocol using [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging/).  
  
## INSTALLATION  
  
```  
git clone https://github.com/baudev/Firebase-Cloud-Messaging-FCM-XMPP.git
cd Firebase-Cloud-Messaging-FCM-XMPP/
composer install  
```  

## EXAMPLE  
- Create an `index.php` file and write into it one of the two following script (method you prefer). Don't forget replacing :  `SENDER_ID`, `SERVER KEY`.
- Run the script: `php index.php`
1. Using a class (**best solution**):   
```php  
class YOURCLASSNAME extends \FCMStream\Core {  
  
	public function onSend(string $from, string $messageId, Actions $actions) { 
		 // TODO: Implement onSend() method. 
	 }  
 
	public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions) { 
		// we answer to the message received 
		$message = new \FCMStream\Message();  
		$message->setTo($from);  
		$message->setMessageId("message_id_test");  
		$message->setPriority(\FCMStream\Message::PRIORITY_HIGH);  
		$message->setData(array("test" => "Hello World!")); 
		 
		$actions->sendMessage($message);
	}  

	public function onFail(string $error, string $errorDescription, string $from, string $messageId, Actions $actions) { 
		// TODO: Implement onFail() method. 
	}  
	 public function onExpire(string $from, string $newFCMId, Actions $actions) { 
		// TODO: Implement onExpire() method. 
	 }
}  
  
$test = new YOURCLASSNAME('SENDER_ID', 'SERVER KEY', 'debugfile.txt', \FCMStream\helpers\Logs::DEBUG);  
$test->stream();  
```

2. Using function callback parameters:

```php  
$test = new FCMStream\Callbacks('SENDER_ID', 'SERVER KEY', 'debugfile.txt', \FCMStream\helpers\Logs::ANY);  
  
// onSend callback  
$test->setOnSend(function (string $from, string $messageId, Actions $actions){  
	// TODO: Implement onSend() method.
  });  
  
// onReceiveMessage callback  
$test->setOnReceiveMessage(function ($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions){ 
	// we answer to the message received 
	$message = new \FCMStream\Message();  
	$message->setTo($from);  
	$message->setMessageId("message_id_test");  
	$message->setPriority(\FCMStream\Message::PRIORITY_NORMAL);  
	$message->setData(array("test" => "Hello World!"));  
	
	$actions->sendMessage($message);  
});
  
// onFail callback  
$test->setOnFail(function (string $error, string $errorDescription, string $from, string $messageId, Actions $actions){ 
	// TODO: Implement onFail() method. 
  });  
  
// onExpire callback  
$test->setOnExpire(function (string $from, string $newFCMId, Actions $actions){  
	// TODO: Implement onExpire() method. 
  });  
$test->stream();  
```  
  
## USAGE  
  
1. **Downstream Messages**: server-to-device through FCM  
  
![](doc/downstream.png)  
  
2. **Upstream Messages**: device-to-server through FCM  
  
![](doc/upstream.png)    

## DOCUMENTATION

### `FCMStream\Core`

*As `FCMStream\Callbacks` extends from `FCMStream\Core`, you can use in both cases the following functions.*

#### `new FCMStream\Core($senderID, $apiKey, $debugFile, [$debugLevel], [$retryCounter], [$sleepSecondsBeforeReconnection], [$isDebugMode], [$timeoutConnection])`

Configure the connection between the Server and Firebase Cloud Messaging one.

- `$senderId` - int: The FCM Sender ID. See [Sender ID](https://firebase.google.com/docs/cloud-messaging/concept-options#senderid).
- `$apiKey` - string: The FCM Api Key. See [Api Key](https://firebase.google.com/docs/cloud-messaging/concept-options#apikey).
- `$debugFile` - string|null: Path to the file which will contains all logs. If `null`, all logs will be written in PHP ones.
- `$debugLevel` - *(optional)* int:  Determine how verbose the script must be. Can be `Logs::DEBUG` for all logs, `Logs::WARN` for important ones and `Logs::ANY` for any logs. Default: `Logs::WARN`.
- `$retryCounter` - *(optional)* int: How many times we try to reconnect to the FCM server before stopping the script execution. Default: `10`.
- `$sleepSecondsBeforeReconnection` - *(optional)* int: How many seconds we wait before reconnecting to the FCM server after an unknowned error. Default: `10`.
- `$isDebugMode` - *(optional)* int: Determine if the script is in production mode or not. Can be `Configuration::PROD` for production and `Configuration::DEBUG` for debugging. Default: `Configuration::PROD`
- `$timeoutConnection` - *(optional)* int: Timeout in seconds of the connection between the server and the FCM one. Default: `30`.

#### `stream()` 

Launch the connection between the server and the FCM one and start listening.

- `@throws FCMConnectionException`

#### `onSend($from, $messageId, $actions)`

Triggered when a message has been acknowledge by the FCM server.

- `$from` - string: FCM ID of the acknowledge message recipient.
- `$messageId` - string: Message ID of the acknowledge message.
- `$actions` - Actions: Instance of the Actions class allowing sending messages.

#### `onReceiveMessage($data, $timeToLive, $from, $messageId, $packageName, $actions)`

Triggered when a message has been received by the server.

- `$data` - string: Content payload of the message received.
- `$timeToLive` - int: how long in seconds the message will be kept on the FCM server.
- `$from` - string: FCM ID of the sender.
- `$messageId` - string: Message ID of the message received.
- `$packageName` - string: Package name of the application that sent the message.
- `$actions` - Actions: Instance of the Actions class allowing sending messages.

#### `onFail([$error], [$errorDescription], [$from], [$messageId], Actions $actions)`

Triggered when an error occurred such as bad FCM ID, invalid JSON payload... 

- `$error` - null|string: XMPP error code or Stanza error text. See [XMPP error code](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#error-codes) and [Stanza error](https://firebase.google.com/docs/cloud-messaging/server#stanza).
- `$errorDescription` - null|string: Description of the error.
- `$from` - null|string: FCM ID of the user to whom the error action is directed such as a message (if the error is linked to an directed action).
- `$messageId` - null|string: Message ID of the message that caused the error (if it's the case).
- `$actions` - Actions: Instance of the Actions class allowing sending messages.

*NOTE:* Sometimes the error can be fatal and close the XMPP connection. Then, the script tries to reconnect itself to the FCM server. It can justify that a message is sent many seconds after the triggering of `onFail()` method. 

#### `onExpire($from, $newFCMId, $actions)`

Triggered when a FCM ID has expired.

- `$from` - string: FCM ID to which a message was sent and which has expired.
- `$newFCMId` - string: new FCM ID in replacement of the `$from` one.
- `$actions` - Actions: Instance of the Actions class allowing sending messages.


### `FCMStream\Actions`

#### `sendMessage($message)`

Send the message passed as parameter.

- `$message` - Message: Message object that must be sent to the FCM server.
- `@throws FCMConnectionException`
- `@throws FCMMessageFormatException`

### `FCMStream\Message`

Class designed to format a message as JSON.

#### `new FCMStream\Message(?string $to = null, ?string $condition = null, ?string $messageId = null, ?string $collapseKey = null, ?string $priority = self::PRIORITY_NORMAL, ?bool $contentAvailable = null, ?bool $mutableContent = null, int $timeToLive = 2419200, bool $deliveryReceiptRequested = false, bool $dryRun = false, ?array $data = null, ?array $notification = null)`

- `$to` - *(optional)* null|string: This parameter specifies the recipient of a message.
The value can be a device's registration token, a device group's notification key, or a single topic (prefixed with  `/topics/`). To send to multiple topics, use the  `condition`  parameter.
- `$condition` - *(optional)* null|string: This parameter specifies a logical expression of conditions that determines the message target. See [Downstream XMPP Message](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#downstream-xmpp-messages-json)
- `$messageId` - *(optional)* null|string: This parameter uniquely identifies a message in an XMPP connection. This parameter is required and mustn't be `null`. 
- `$collapseKey` - *(optional)* null|string: This parameter identifies a group of messages (e.g., with `collapse_key: "Updates Available"`) that can be collapsed so that only the last message gets sent when delivery is resumed. See [Downstream XMPP Message](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#downstream-xmpp-messages-json)
- `$priority`- *(optional)* int: Set the priority of the message. Can be `Message::PRIORITY_NORMAL` or `Message::PRIORITY_HIGH`. Default: `Message::PRIORITY_NORMAL`
- `$contentAvailable` - *(optional)* null|boolean: On iOS, use this field to represent `content-available` in the APNs payload. See [Downstream XMPP Message](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#downstream-xmpp-messages-json). Default: `null`.
- `$mutableContent` - *(optional)* null|boolean: Currently for iOS 10+ devices only. On iOS, use this field to represent `mutable-content` in the APNS payload. See [Downstream XMPP Message](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#downstream-xmpp-messages-json). Default: `null`.
- `$timeToLive` - *(optional)* int: how long in seconds the message will be kept on the FCM server. Must be between `0` and `2419200` (4 weeks). Default: `2419200`.
- `$deliveryReceiptRequested` - *(optional)* boolean: This parameter lets the app server request confirmation of message delivery. [Delivery receipt](https://firebase.google.com/docs/cloud-messaging/xmpp-server-ref#ccs) is not supported yet. Default: `false`.
- `$dryRun` - *(optional)* boolean: This parameter, when set to `true`, allows developers to test a request without actually sending a message. Default: `false`.
- `$data` - *(optional)* null|array: This parameter specifies the key-value pairs of the message's payload. Default: `null`.
- `$notification` -  *(optional)* null|array: This parameter specifies the predefined, user-visible key-value pairs of the notification payload. This parameter is not fully supported yet. Default: `null`. 

### TODO  
  
- [ ] Add more comments
- [X] Add methods for responding easily, to set message priority and so on. *Notification property is not handled yet*  
- [X] Improve README  
- [ ] Support lower PHP version ?   
  
### CREDITS  
  
- Images in the [USAGE](#usage) part are coming from the [XAMARIN documentation](https://docs.microsoft.com/en-us/xamarin/android/data-cloud/google-messaging/google-cloud-messaging).  
- Major part of the code is coming from the [sourc7/FCMStream](https://github.com/sourc7/FCMStream) repository. As it is unmaintained, I allow myself to fork it and improve it.  
  
### LICENSE  
  
```  
MIT License  
  
Copyright (c) 2016 Ante Radman (Radoid), Irvan Kurniawan (TechRapid), Baudev.  
  
Permission is hereby granted, free of charge, to any person obtaining a copy  
of this software and associated documentation files (the "Software"), to deal  
in the Software without restriction, including without limitation the rights  
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell  
copies of the Software, and to permit persons to whom the Software is  
furnished to do so, subject to the following conditions:  
  
The above copyright notice and this permission notice shall be included in all  
copies or substantial portions of the Software.  
  
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR  
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,  
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE  
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER  
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,  
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE  
SOFTWARE.  
```