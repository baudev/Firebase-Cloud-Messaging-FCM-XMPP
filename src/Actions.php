<?php
/**
 * User: Baudev
 * Date: 18/06/2018
 */

namespace FCMStream;


use FCMStream\helpers\Logs;

class Actions
{

    private $coreInstance;


    public function __construct(Core $core)
    {
        $this->setCoreInstance($core);
    }

    /**
     * Send a downstream message
     * @param Message $message
     * @throws exceptions\FCMConnectionException
     * @throws exceptions\FCMMessageFormatException
     */
    public function sendMessage(Message $message){
        $this->getCoreInstance()->write($this->getCoreInstance()->getRemote(), '
<message><gcm xmlns="google:mobile:data">
    ' . json_encode($message, JSON_FORCE_OBJECT ) .'
</gcm></message>');

        // we pass the log after json_decode() because it check contents and throw error if needed

        if (!empty($message->getTo())) {
            Logs::writeLog(Logs::DEBUG, "Sending a message to: " . $message->getTo());
        }
        if (!empty($message->getCondition())) {
            Logs::writeLog(Logs::DEBUG, "Sending a message to: " . $message->getCondition());
        }
        Logs::writeLog(Logs::DEBUG, "Message ID: " . $message->getMessageId());
        if (!empty($message->getData())) {
            Logs::writeLog(Logs::DEBUG, "Content: " . json_encode($message->getData()));
        }
        if (!empty($message->getNotification())) {
            Logs::writeLog(Logs::DEBUG, "Content: " . json_encode($message->getNotification()));
        }
    }

    /**
     * @return Core
     */
    public function getCoreInstance(): Core
    {
        return $this->coreInstance;
    }

    /**
     * @param Core $coreInstance
     */
    public function setCoreInstance(Core $coreInstance): void
    {
        $this->coreInstance = $coreInstance;
    }



}