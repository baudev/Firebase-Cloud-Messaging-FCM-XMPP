<?php
/**
 * User: Baudev
 * Date: 14/06/2018
 */

namespace FCMStream;

use FCMStream\exceptions\FCMConnectionException;
use FCMStream\helpers\Configuration;
use FCMStream\helpers\DatetimeISO8601;
use FCMStream\helpers\Functions;
use FCMStream\helpers\Logs;
use FCMStream\interfaces\FCMListeners;

abstract class Core implements FCMListeners
{

    /*
     *  ABSTRACT METHODS
     */

    abstract public function onLoop(Actions $actions);
    
    abstract public function onSend(string $from, string $messageId, Actions $actions);
    
    abstract public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName, Actions $actions);

    abstract public function onFail(?string $error, ?string $errorDescription, ?string $from, ?string $messageId, Actions $actions);

    abstract public function onExpire(string $from, string $newFCMId, Actions $actions);


    private $retryCount;
    private $remote;

    private $parsedMessage;
    private $isParsing = false;

    /**
     * Callbacks constructor.
     * @param int $senderID https://firebase.google.com/docs/cloud-messaging/concept-options#senderid
     * @param string $apiKey https://firebase.google.com/docs/cloud-messaging/concept-options#apikey
     * @param mixed $debugFile file where will be stored all logs. Ff null, it will go into the PHP output.
     * @param int $debugLevel Level determining how verbose the script must be. 0 any. 1 partially. 2 every.
     * @param int $retryCounter After how many failed reconnection the script must stop
     * @param int $sleepSecondsBeforeReconnection How many seconds the script must sleep before reconnection
     * @param bool $isDebugMode If the script is in debug mode or not. False by default.
     * @param int $timeoutConnection Timeout of the connection
     */
    public function __construct(int $senderID, string $apiKey, $debugFile, int $debugLevel = Logs::WARN, int $retryCounter = 10, int $sleepSecondsBeforeReconnection = 10, bool $isDebugMode = Configuration::PROD, int $timeoutConnection = 30)
    {
        // set the configuration
        Configuration::setSenderID($senderID);
        Configuration::setApiKey($apiKey);
        Configuration::setDebugLevel($debugLevel);
        Configuration::setDebugFile($debugFile);
        Configuration::setRetryMaxAttempts($retryCounter);
        Configuration::setSleepSecondsBeforeReconnection($sleepSecondsBeforeReconnection);
        Configuration::setIsDebugMode($isDebugMode);
        Configuration::setTimeoutConnection($timeoutConnection);

        // set debug output
        if(!empty(Configuration::getDebugFile())){
            Configuration::setDebugFile(fopen(Configuration::getDebugFile(), "a"));
        } else {
            // if any filename has been specified, then we put logs into php logs
            Configuration::setDebugFile('php://output');
        }
    }



    /**
     * Generate a GUID http://php.net/manual/fr/function.com-create-guid.php
     * @return string
     */
    protected function getGUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

    /**
     * Initialize the connection with the FCM server via XMPP
     * @return bool
     * @throws FCMConnectionException
     */
    protected function connectRemote() : bool
    {
        Logs::writeLog(Logs::DEBUG, "Connecting to " . Configuration::FCM_HOST . ":" . Configuration::getPort() . " at ". new DatetimeISO8601());

        // we try opening the connection
        if (!($this->remote = stream_socket_client("tls://" . Configuration::FCM_HOST . ":" . Configuration::getPort(), $errno, $errstr, Configuration::getTimeoutConnection(), STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT))) {
            // something failed, we log it
            Logs::writeLog(Logs::WARN, "Failed to connect", "($errno) $errstr");
            throw new FCMConnectionException("Error while establishing connection with FCM Server", FCMConnectionException::CONNECTION_FAILED);
        }

        // we request a connection to FCM
        $this->write($this->getRemote(),
            '<stream:stream to="gcm.googleapis.com" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');

        // we generate a GUID
        $unique_guid = $this->getGUID();

        // we wait for data http://php.net/manual/fr/function.stream-set-blocking.php
        stream_set_blocking($this->getRemote(), true);

        // while the connection is still alive
        while (($xml = $this->read($this->getRemote())) !== false) {
            // if there is xml received
            if ($xml) {
                // we parse the XML
                if ($root = Functions::parseXML($xml)) {
                    // for each node (the parent one in our cases)
                    foreach ($root->childNodes as $node) {
                        // we get the localName of it (what is inside < > for beginners)
                        // depending of the xml received, we determine what we must send
                        // we follow the rules given here https://firebase.google.com/docs/cloud-messaging/auth-server
                        if ($node->localName == 'features') {
                            foreach ($node->childNodes as $node) {
                                if ($node->localName == 'mechanisms') {
                                    $this->write($this->getRemote(),
                                        '<auth mechanism="PLAIN" xmlns="urn:ietf:params:xml:ns:xmpp-sasl">' . base64_encode(chr(0) . Configuration::getSenderID() . '@gcm.googleapis.com' . chr(0) . Configuration::getApiKey()) . '</auth>');
                                } elseif ($node->localName == 'bind') {
                                    $this->write($this->getRemote(),
                                        '<iq to="' . Configuration::FCM_HOST . '" type="set" id="' . $unique_guid . "-1" . '"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>test</resource></bind></iq>');
                                } elseif ($node->localName == 'session') {
                                    $this->write($this->getRemote(),
                                        '<iq to="' . Configuration::FCM_HOST . '" type="set" id="' . $unique_guid . "-2" . '"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>');
                                }
                            }

                        } elseif ($node->localName == 'success') {
                            $this->write($this->getRemote(),
                                '<stream:stream to="' . Configuration::FCM_HOST . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');
                        } elseif ($node->localName == 'failure') {
                            break;
                        } elseif ($node->localName == 'iq' && $node->getAttribute('type') == 'result') {
                            if ($node->getAttribute('id') == $unique_guid . "-1") {
                                // everything is ok, we start listening
                                return true;
                            }
                        }
                    }

                } else {
                    Logs::writeLog(Logs::DEBUG, "XML unparseable while establishing connection", $xml);
                }
            }
        }
        // the connection is no more alive
        fclose($this->getRemote());
        $this->setRemote(null);
        throw new FCMConnectionException("Connection is no more alive. ", FCMConnectionException::CONNECTION_NO_MORE_ALIVE);
    }

    /**
     * Start streaming
     * @throws FCMConnectionException
     */
    public function stream()
    {

        // if the server isn't connected, then we do it
        if (!$this->isRemoteConnected()) {
            $this->connectRemote();
        }

        // if the server is connected, we start analyzing what is sent
        if ($this->isRemoteConnected()) {
            // has the connection is established, we put the retry counter to 0
            $this->setRetryCount(0);
            Logs::writeLog(Logs::DEBUG, "Streaming FCM Cloud Connection Server...");

            // TODO put this timeout in Configuration class
             stream_set_timeout($this->getRemote(), rand(1,5));
            // we set an infinite loop
            while (($packetData = $this->read($this->getRemote())) !== 1) {
                // we explode the packet after each footer node
                $packetArray = preg_split('/(?<=<\/message>)/', $packetData, -1);
                foreach ($packetArray as $packet) {
                    // make sure that the XML received is well formatted
                    $validXML = $this->analyzeData($packet);
                    // we parse it
                    if ($validXML && ($root = Functions::parseXML($validXML))) {
                        // for each node
                        foreach ($root->childNodes as $node) {
                            if ($node->localName == 'message') {
                                if ($node->getAttribute('type') == 'error') {
                                    foreach ($node->childNodes as $subnode) {
                                        if ($subnode->localName == 'error') {
                                            Logs::writeLog(Logs::WARN, "ERROR " . $subnode->textContent);
                                            $this->onFail($subnode->textContent, null, null, null, new Actions($this));
                                        }
                                    }

                                } elseif ($node->firstChild->localName == 'gcm'
                                    && ($json = $node->firstChild->textContent)
                                    && ($data = json_decode($json)) && @$data->message_type && @$data->message_id) {
                                    if ($data->message_type == 'ack') {
                                        // the recipient has acknowledge the message
                                        Logs::writeLog(Logs::DEBUG, "ACK message #$data->message_id");
                                        $this->onSend($data->from, $data->message_id, new Actions($this));
                                    } elseif ($data->message_type == 'nack') {
                                        // error case
                                        Logs::writeLog(Logs::WARN, "$data->error ($data->error_description) $data->from");
                                        if ($data->error == 'BAD_REGISTRATION' || $data->error == 'DEVICE_UNREGISTERED') {
                                            $this->onFail($data->error, $data->error_description, $data->from, $data->message_id, new Actions($this));
                                        } else {
                                        $this->onFail($data->error, $data->error_description, $data->from, $data->message_id, new Actions($this));
                                        }

                                    }
                                    if ((Functions::isControlMessage($validXML) && $data->message_type == 'control') && ($data->control_type == 'CONNECTION_DRAINING')) {
                                        // we re open a new connection because FCM as to close the current one
                                        Logs::writeLog(Logs::DEBUG, "FCM Server: CONNECTION_DRAINING");
                                        Logs::writeLog(Logs::DEBUG, "FCM Server need to restart the connection due to maintenance or high load.");
                                        $this->retryConnect();
                                    }
                                    if (@$data->registration_id) {
                                        // the FCM has expired, FCM return the new one
                                        Logs::writeLog(Logs::WARN, "CANONICAL ID $data->from -> $data->registration_id");
                                        $this->onExpire($data->from, $data->registration_id, new Actions($this));
                                    }
                                } elseif (($json = $node->firstChild->textContent) && ($mdata = json_decode($json)) && ($client_token = $mdata->from) && ($client_message = $mdata->data) && ($package_app = $mdata->category)) {
                                    // The server received a message. We acknowledge it.
                                    $client_message_id = $mdata->message_id;
                                    $this->sendACK($client_token, $client_message_id);
                                    $this->onReceiveMessage($mdata->data, $mdata->time_to_live, $mdata->from, $client_message_id, $mdata->category, new Actions($this));
                                }
                            }
                        }
                    }
                }
                // Ask if there's anything to send
                $this->onLoop(new Actions($this));
            }
        }
    }



    /**
     * Return if the server is connected to the FCM one
     * @return bool
     */
    protected function isRemoteConnected()
    {
        return ($this->getRemote() && !feof($this->getRemote()));
    }

    /**
     * Close the connection with the FCM server
     * @throws FCMConnectionException
     */
    protected function closeRemote()
    {
        if ($this->getRemote()) {
            Logs::writeLog(Logs::WARN, "Closing connection");
            $this->write($this->getRemote(), Functions::getClosing());
            fclose($this->getRemote());
        }
        $this->setRemote( null);
    }

    /**
     * Exit the script
     */
    protected function exit() {
        Logs::writeLog(Logs::WARN, "Exiting stream() function.");
        exit();
    }

    /**
     * Send content
     * @param $socket
     * @param $xml
     * @return bool|int
     * @throws FCMConnectionException
     */
    public function write($socket, $xml)
    {
        $length = fwrite($socket, $xml . "\n") or $this->retryConnect();;
        Logs::writeLog(Logs::DEBUG, is_numeric($length) ? "Sent $length bytes" : "Failed sending", $xml);
        return $length;
    }

    /**
     * Reads the socket content
     * @param $socket
     * @return bool|string
     */
    protected function read($socket)
    {
        $response  = fread($socket, 1387);
        $meta_data = stream_get_meta_data($socket);
        $length    = (is_string($response) ? (strlen($response) == 1 ? 'character ' . ord($response) : strlen($response) . ' bytes') : json_encode($response));
        Logs::writeLog(Logs::DEBUG, $response === false ? "Failed reading" : "Read $length", $response);
        return (is_string($response) ? trim($response) : $response);
    }



    // todo move this function to another class and pass the object reference $this in order to edit parsedMessage and isParssing

    /**
     * @param $packetData
     * @return string
     * @throws FCMConnectionException
     */
    protected function analyzeData($packetData)
    {
        // check validity of the XML
        if (Functions::isValidStanza($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message is not fragmented.");
            return $packetData;
        }

        // if XML is empty, then it's a message to keep alive the exchange
        if ($packetData == "") {
            Logs::writeLog(Logs::DEBUG, "Keepalive exchange");
            $this->write($this->remote, " ");
        }

        // the message is not complete, we wait for the end of it
        if (Functions::isOnlyFooterIsMissing($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message is fragmented because footer is missing.");
            $this->parsedMessage = null;
            $this->isParsing     = true;

            Logs::writeLog(Logs::DEBUG, "Parsing message..");
            $this->parsedMessage .= $packetData;

        } elseif ($this->isParsing) { // this is the next part of the previous message
            $this->parsedMessage .= $packetData;
        }

        if ($this->isParsing && Functions::isFooterMatch($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message parsed succesfully.");
            $this->isParsing = false;
            return $this->parsedMessage;
        }

        if (Functions::isXMLStreamError($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Stream Error: Invalid XML.");
            Logs::writeLog(Logs::DEBUG, "FCM Server is failed to parse the XML payload.");
            // todo throw an error and reopen connection?
            $this->exit();
        }

    }

    /**
     * @param $registration_id
     * @param $message_id
     * @throws FCMConnectionException
     */
    protected function sendACK($registration_id, $message_id)
    {
        $this->write($this->remote, '<message id=""><gcm xmlns="google:mobile:data">{"to":"' . $registration_id . '","message_id":' . json_encode(htmlspecialchars($message_id, ENT_NOQUOTES), JSON_UNESCAPED_UNICODE) . ',"message_type":"ack"}</gcm></message>');
    }


    /**
     * Try to reconnect the XMPP Server
     * @throws FCMConnectionException
     */
    protected function retryConnect()
    {
        $this->setRetryCount($this->getRetryCount() + 1);

        // if after max authorized attempts the connection still doesn't work, we stop the script
        if ($this->retryCount == Configuration::getRetryMaxAttempts()) {
            echo "Retry attempt has exceeded. \n";
            echo "Failed to retry connect to the remote server. \n";
            throw new FCMConnectionException("Max retry connect attempts exceeded.", FCMConnectionException::CONNECTION_MAX_RETRY_ATTEMPTS_EXCEEDED);
        }

        // we waits some seconds before reconnection
        Logs::writeLog(Logs::WARN, "Reconnecting to the FCM Server in ".Configuration::getSleepSecondsBeforeReconnection()." seconds. ", "");
        sleep(Configuration::getSleepSecondsBeforeReconnection());
        $this->stream();
    }

    /**
     * @return int|null
     */
    protected function getRetryCount() : ?int
    {
        return $this->retryCount;
    }

    /**
     * @param int $retryCount
     */
    protected function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }
    // todo use this method everywhere and pass it to private
    /**
     * @return mixed
     */
    public function getRemote()
    {
        return $this->remote;
    }

    /**
     * @param mixed $remote
     */
    protected function setRemote($remote): void
    {
        $this->remote = $remote;
    }



}