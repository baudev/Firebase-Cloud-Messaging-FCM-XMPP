<?php
/**
 * User: Baudev
 * Date: 14/06/2018
 */

namespace FCMStream;

use FCMStream\helpers\Configuration;
use FCMStream\helpers\DatetimeISO8601;
use FCMStream\helpers\Functions;
use FCMStream\helpers\Logs;
use FCMStream\interfaces\FCMListeners;

abstract class Core implements FCMListeners
{

    private $retryCount;
    private $remote;

    private $parsedMessage;
    private $isParsing = false;


    abstract public function onSend(string $from, string $messageId);

    abstract public function onReceiveMessage($data, int $timeToLive, string $from, string $messageId, string $packageName);

    abstract public function onFail(string $error, string $from);

    abstract public function onExpire(string $from, string $newFCMId);

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
    private function getGUID(){
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
     */
    private function connectRemote()
    {
        Logs::writeLog(Logs::DEBUG, "Connecting to " . Configuration::FCM_HOST . ":" . Configuration::getPort() . " at ". new DatetimeISO8601());

        // we try opening the connection
        if (!($this->remote = stream_socket_client("tls://" . Configuration::FCM_HOST . ":" . Configuration::getPort(), $errno, $errstr, Configuration::getTimeoutConnection(), STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT))) {
            // something failed, we log it
            Logs::writeLog(Logs::WARN, "Failed to connect", "($errno) $errstr");
            return false;
        }

        // we request a connection to FCM
        $this->write($this->remote,
            '<stream:stream to="gcm.googleapis.com" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');

        // we generate a GUID
        $unique_guid = $this->getGUID();

        // we wait for data http://php.net/manual/fr/function.stream-set-blocking.php
        stream_set_blocking($this->remote, true);

        // while the connection is still alive
        while (($xml = $this->read($this->remote)) !== false) {
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
                                    $this->write($this->remote,
                                        '<auth mechanism="PLAIN" xmlns="urn:ietf:params:xml:ns:xmpp-sasl">' . base64_encode(chr(0) . Configuration::getSenderID() . '@gcm.googleapis.com' . chr(0) . Configuration::getApiKey()) . '</auth>');
                                } elseif ($node->localName == 'bind') {
                                    $this->write($this->remote,
                                        '<iq to="' . Configuration::FCM_HOST . '" type="set" id="' . $unique_guid . "-1" . '"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>test</resource></bind></iq>');
                                } elseif ($node->localName == 'session') {
                                    $this->write($this->remote,
                                        '<iq to="' . Configuration::FCM_HOST . '" type="set" id="' . $unique_guid . "-2" . '"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>');
                                }
                            }

                        } elseif ($node->localName == 'success') {
                            $this->write($this->remote,
                                '<stream:stream to="' . Configuration::FCM_HOST . '" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');
                        } elseif ($node->localName == 'failure') {
                            break;
                        } elseif ($node->localName == 'iq' && $node->getAttribute('type') == 'result') {
                            if ($node->getAttribute('id') == $unique_guid . "-1") {
                                return true;
                            }
                        }
                    }

                } else {
                    Logs::writeLog(Logs::DEBUG, "Unparseable", $xml);
                }
            }
        }

        // we close the connection
        fclose($this->remote);
        $this->remote = null;
        return false;
    }

    /**
     * Return if the server is connected to the FCM one
     * @return bool
     */
    private function isRemoteConnected()
    {
        return ($this->remote && !feof($this->remote));
    }

    /**
     * Close the connection with the FCM server
     */
    public function closeRemote()
    {
        if ($this->remote) {
            Logs::writeLog(Logs::WARN, "Closing connection");
            $this->write($this->remote, Functions::getClosing());
            fclose($this->remote);
        }
        $this->remote = null;
    }

    /**
     * Exit the script
     */
    private function exit() {
        Logs::writeLog(Logs::WARN, "Exiting stream() function.");
        exit();
    }

    /**
     * Start streaming
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
            $this->retryConnect = 0;
            Logs::writeLog(Logs::DEBUG, "Streaming FCM Cloud Connection Server...");

            // TODO put this timeout in Configuration class
            stream_set_timeout($this->remote, 150);
            // we set an infinite loop
            while (($packetData = $this->read($this->remote)) !== 1) {
                // make sure that the XML received is well formatted
                $validXML = $this->analyzeData($packetData);
                // we parse it
                if ($validXML && ($root = Functions::parseXML($validXML))) {
                    // for each node
                    foreach ($root->childNodes as $node) {
                        if ($node->localName == 'message') {
                            if ($node->getAttribute('type') == 'error') {
                                foreach ($node->childNodes as $subnode) {
                                    if ($subnode->localName == 'error') {
                                        Logs::writeLog(Logs::WARN, "ERROR " . $subnode->textContent);
                                    }
                                }

                            } elseif ($node->firstChild->localName == 'gcm'
                                && ($json = $node->firstChild->textContent)
                                && ($data = json_decode($json)) && @$data->message_type && @$data->message_id) {
                                if ($data->message_type == 'ack') {
                                    Logs::writeLog(Logs::DEBUG, "ACK message #$data->message_id");
                                    $this->onSend($data->from, $data->message_id);
                                } elseif ($data->message_type == 'nack') {
                                    Logs::writeLog(Logs::WARN, "$data->error ($data->error_description) $data->from");
                                    // TODO handle errors
                                    if ($data->error == 'BAD_REGISTRATION' || $data->error == 'DEVICE_UNREGISTERED') {
                                        // TODO add fields such as message_id
                                        $this->onExpire($data->from, null);
                                    } else {
                                        $this->onFail($data->error, $data->from);
                                    }

                                }
                                // todo in documentation fcm says to have two connections no ?
                                if ((Functions::isControlMessage($validXML) && $data->message_type == 'control') && ($data->control_type == 'CONNECTION_DRAINING')) {
                                    Logs::writeLog(Logs::DEBUG, "FCM Server: CONNECTION_DRAINING");
                                    Logs::writeLog(Logs::DEBUG, "FCM Server need to restart the connection due to maintenance or high load.");
                                    $this->retryConnect();
                                }
                                if (@$data->registration_id) {
                                    // todo check this case
                                    Logs::writeLog(Logs::WARN, "CANONICAL ID $data->from -> $data->registration_id");
                                    $this->onExpire($data->from, $data->registration_id);
                                }
                            } elseif (($json = $node->firstChild->textContent) && ($mdata = json_decode($json)) && ($client_token = $mdata->from) && ($client_message = $mdata->data) && ($package_app = $mdata->category)) {
                                $client_message_id = $mdata->message_id;
                                $this->sendACK($client_token, $client_message_id);
                                $this->onReceiveMessage($mdata->data, $mdata->time_to_live, $mdata->from, $client_message_id, $mdata->category);
                            }
                        }
                    }
                }
            }
        }
    }

// Auxiliary functions

    public function write($socket, $xml)
    {
        $length = fwrite($socket, $xml . "\n") or $this->retryConnect();
        Logs::writeLog(Logs::DEBUG, is_numeric($length) ? "Sent $length bytes" : "Failed sending", $xml);
        return $length;
    }

    // todo see what is meta data
    private function read($socket)
    {
        $response  = fread($socket, 1387);
        $meta_data = stream_get_meta_data($socket);
        $length    = (is_string($response) ? (strlen($response) == 1 ? 'character ' . ord($response) : strlen($response) . ' bytes') : json_encode($response));
        Logs::writeLog(Logs::DEBUG, $response === false ? "Failed reading" : "Read $length", $response);
        return (is_string($response) ? trim($response) : $response);
    }
    // todo move this function to another class and pass the object reference $this in order to edit parsedMessage and isParssing
    private function analyzeData($packetData)
    {
        if (Functions::isValidStanza($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message is not fragmented. \n");
            return $packetData;
        }

        if ($packetData == "") {
            Logs::writeLog(Logs::DEBUG, "Keepalive exchange \n");
            $this->write($this->remote, " ");
        } elseif (Functions::isInvalidStanza($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message doesn't have valid XMPP header and footer. \n");
        }

        if (Functions::isFooterMissing($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message is fragmented. \n");
            $this->parsedMessage = null;
            $this->isParsing     = true;

            Logs::writeLog(Logs::DEBUG, "Parsing message.. \n");
            $this->parsedMessage .= $packetData;

        } elseif ($this->isParsing) {
            $this->parsedMessage .= $packetData;
        }

        if ($this->isParsing && Functions::isFooterMatch($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Message parsed succesfully. \n");
            $this->isParsing = false;
            return $this->parsedMessage;
        }

        if (Functions::isXMLStreamError($packetData)) {
            Logs::writeLog(Logs::DEBUG, "Stream Error: Invalid XML. \n");
            Logs::writeLog(Logs::DEBUG, "FCM Server is failed to parse the XML payload. \n");
            $this->exit();
        }

    }

    private function sendACK($registration_id, $message_id)
    {
        $this->write($this->remote, '<message id=""><gcm xmlns="google:mobile:data">{"to":"' . $registration_id . '","message_id":' . json_encode(htmlspecialchars($message_id, ENT_NOQUOTES), JSON_UNESCAPED_UNICODE) . ',"message_type":"ack"}</gcm></message>');
    }

    public function sendBack($registration_id, $client_message, $client_message_id)
    {
        Logs::writeLog(Logs::DEBUG, "Sending message: " . $client_message);
        Logs::writeLog(Logs::DEBUG, "Message ID: " . $client_message_id);
        // TODO remove body in json
        // TODO add priority choice
        $this->write($this->remote, '<message><gcm xmlns="google:mobile:data">
{
    "to":"' . $registration_id . '",
    "data":{"body":' . json_encode(htmlspecialchars($client_message, ENT_NOQUOTES), JSON_UNESCAPED_UNICODE) . '},
    "message_id":' . json_encode(htmlspecialchars($client_message_id . "-N", ENT_NOQUOTES), JSON_UNESCAPED_UNICODE) . '
}
</gcm></message>');
    }

    private function send($full_json_message)
    {
        $this->write($this->remote, $full_json_message);
    }

    /**
     * Try to reconnect the XMPP Server
     */
    private function retryConnect()
    {
        $this->retryCount++;

        // if after max authorized attempts the connection still doesn't work, we stop the script
        if ($this->retryCount == Configuration::getRetryMaxAttempts()) {
            echo "Retry attempt has exceeded (10) . \n";
            echo "Failed to retry connect to the remote server. \n";
            exit();
        }

        // we waits some seconds before reconnection
        echo "Reconnecting to the FCM Server in ".Configuration::getSleepSecondsBeforeReconnection()." seconds. \n";
        sleep(Configuration::getSleepSecondsBeforeReconnection());
        $this->stream();
    }

    /**
     * @return int
     */
    private function getRetryCount() : int
    {
        return $this->retryCount;
    }

    /**
     * @param int $retryCount
     */
    private function setRetryCount(int $retryCount): void
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
    private function setRemote($remote): void
    {
        $this->remote = $remote;
    }

}