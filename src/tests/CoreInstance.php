<?php

namespace FCMStream\tests;

use FCMStream\Core;

class CoreInstance extends Core
{

    public function onSend(string $from, string $messageId, \FCMStream\Actions $actions)
    {
        // TODO: Implement onSend() method.
    }

    public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName, \FCMStream\Actions $actions)
    {
        // TODO: Implement onReceiveMessage() method.
    }

    public function onFail(?string $error, ?string $errorDescription, ?string $from, ?string $messageId, \FCMStream\Actions $actions)
    {
        // TODO: Implement onFail() method.
    }

    public function onExpire(string $from, string $newFCMId, \FCMStream\Actions $actions)
    {
        // TODO: Implement onExpire() method.
    }
}