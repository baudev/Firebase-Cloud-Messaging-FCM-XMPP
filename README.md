# FCM UPSTREAM XMPP - PHP
This PHP program, based on the unmaintained [sourc7/FCMStream](https://github.com/sourc7/FCMStream) repository, allows receiving and sending messages with the XMPP Protocol using [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging/).

### INSTALLATION

```
composer install
```

- Then configure your script following the [EXAMPLE](#example) part.

- Run the script with PHP. For example, if you php file is named `index.php` then run: 

```
php index.php
``` 

### EXAMPLE

- Using a class (best solution):

```php
class YOURCLASSNAME extends \FCMStream\Core {

    public function onSend(string $from, string $messageId)
    {
        // TODO: Implement onSend() method.
    }

    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName)
    {
        // TODO: Implement onReceiveMessage() method.
    }

    public function onFail(string $error, string $from)
    {
        // TODO: Implement onFail() method.
    }

    public function onExpire(string $from, string $newFCMId)
    {
        // TODO: Implement onExpire() method.
    }
}

$test = new YOURCLASSNAME(0000000000000, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'debugfile.txt', \FCMStream\helpers\Logs::DEBUG);
$test->stream();
```

- Using function callback parameters:

```php
$test = new FCMStream\Callbacks(0000000000000, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'debugfile.txt', \FCMStream\helpers\Logs::ANY);

// onSend callback
$test->setOnSend(function (string $from, string $messageId){
    
});

// onReceiveMessage callback
$test->setOnReceiveMessage(function ($data, int $timeToLive, string $from, string $messageId, string $packageName){
});

// onFail callback
$test->setOnFail(function (string $error, string $from){
    
});

// onExpire callback
$test->setOnExpire(function (string $from, string $newFCMId){
    
});

$test->stream();
```

### USAGE

1. **Downstream Messages**: server-to-device through FCM

![](doc/downstream.png)

2. **Upstream Messages**: device-to-server through FCM

![](doc/upstream.png)  

### TODO

- Continue commenting code
- Add methods for responding easily, to set message priority and so on
- Improve README
- Support lower PHP version ? 

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

    
  