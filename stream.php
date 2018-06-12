<?php
/**
 * FCMStream stream class.
 * It will connect to Firebase Cloud Connection server, stream and keep the XMPP session open.
 *
 * @package FCMStream
 */

class FCMStream
{
	private $host = 'fcm-xmpp.googleapis.com';
	private $port = 5235;
	private $sender_id;
	private $api_key;

	private $remote;
	private $service;

	private $debugFile = 'php://output';
	private $debugLevel = 2;

	private $onSend;
	private $onFail;
	private $onExpire;
	private $onReceiveMessage;

	private $parsedMessage;
	private $isParsing = false;

  private $retryCount = 0;

	function __construct($options = array()) {
		foreach ($this as $key => $value)
			if (!empty($options[$key]))
				$this->$key = $options[$key];

		if ($this->debugFile)
			$this->debugFile = fopen($this->debugFile, "a");
	}

  function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
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

	// Service functions

	function connectRemote() {

		$this->debug(2, "Connecting to $this->host:$this->port...");
		if (!($this->remote = stream_socket_client("tls://$this->host:$this->port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT))) {
			$this->debug(1, "Failed to connect", "($errno) $errstr");
			return false;
		}

		$this->write($this->remote,
			'<stream:stream to="'.$this->host.'" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');

      $unique_guid = $this->getGUID();

		stream_set_blocking($this->remote, 1);
		while (($xml = $this->read($this->remote)) !== false)
			if ($xml)
				if ($root = $this->parseXML($xml)) {
					foreach ($root->childNodes as $node)
						if ($node->localName == 'features') {
							foreach ($node->childNodes as $node)
								if ($node->localName == 'mechanisms')
									$this->write($this->remote,
										'<auth mechanism="PLAIN" xmlns="urn:ietf:params:xml:ns:xmpp-sasl">'.base64_encode(chr(0).$this->sender_id.'@gcm.googleapis.com'.chr(0).$this->api_key).'</auth>');
								elseif ($node->localName == 'bind')
									$this->write($this->remote,
										'<iq to="'.$this->host.'" type="set" id="' .$unique_guid. "-1" . '"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><resource>test</resource></bind></iq>');
								elseif ($node->localName == 'session')
									$this->write($this->remote,
										'<iq to="'.$this->host.'" type="set" id="' .$unique_guid. "-2" . '"><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></iq>');
						}
						elseif ($node->localName == 'success')
							$this->write($this->remote,
								'<stream:stream to="'.$this->host.'" version="1.0" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">');
						elseif ($node->localName == 'failure')
							break;
						elseif ($node->localName == 'iq' && $node->getAttribute('type') == 'result')
							if ($node->getAttribute('id') == $unique_guid. "-1")
                return true;
				} else
					$this->debug(2, "Unparseable", $xml);

		fclose($this->remote);
		$this->remote = null;
		return false;
	}

	function isRemoteConnected() {
		return ($this->remote && !feof($this->remote));
	}

	function closeRemote() {
		if ($this->remote) {
			$this->debug(1, "Closing connection");
			$this->write($this->remote, $this->closing);
			fclose($this->remote);
		}
		$this->remote = null;
	}

  function exit() {
    $this->debug(1, "Exiting stream() function.");
    exit();
  }

	function stream() {

		if (!$this->isRemoteConnected())
			$this->connectRemote();

		if ($this->isRemoteConnected()) {
    $this->retryConnect = 0;
		$this->debug(2, "Streaming FCM Cloud Connection Server...");

		stream_set_timeout($this->remote, 150);

		while (($packetData = $this->read($this->remote)) !==  42412) {
			$validXML = $this->analyzeData($packetData);
			if ($validXML && ($root = $this->parseXML($validXML))) {
				foreach ($root->childNodes as $node) {
					if ($node->localName == 'message') {
						if ($node->getAttribute('type') == 'error') {
							foreach ($node->childNodes as $subnode)
								if ($subnode->localName == 'error')
									$this->debug(1, "ERROR ".$subnode->textContent);
						} elseif ($node->firstChild->localName == 'gcm'
								&& ($json = $node->firstChild->textContent)
								&& ($data = json_decode($json)) && @$data->message_type && @$data->message_id) {
							if ($data->message_type == 'ack') {
								$this->debug(2, "ACK message #$data->message_id");
								@call_user_func($this->onSend, $data->message_id);
							}
							elseif ($data->message_type == 'nack') {
								$this->debug(1, "$data->error ($data->error_description) $data->from");
								if ($data->error == 'BAD_REGISTRATION' || $data->error == 'DEVICE_UNREGISTERED')
									@call_user_func($this->onExpire, $data->from, null);
								else
									@call_user_func($this->onFail, $data->message_id, $data->error, $data->error_description);
							}
              if (($this->isControlMessage($validXML) && $data->message_type == 'control') && ($data->control_type == 'CONNECTION_DRAINING')) {
                $this->debug(2, "FCM Server: CONNECTION_DRAINING");
                $this->debug(2, "FCM Server need to restart the connection due to maintenance or high load.");
                $this->retryConnect();
              }
							if (@$data->registration_id) {
								$this->debug(1, "CANONICAL ID $data->from -> $data->registration_id");
								@call_user_func($this->onExpire, $data->from, $data->registration_id);
							}
						}
						elseif (($json = $node->firstChild->textContent) && ($mdata = json_decode($json)) && ($client_token = $mdata->from) && ($client_message = $mdata->data->data)) {
                						$client_message_id = $mdata->message_id;
              						$this->sendACK($client_token, $client_message_id);
              						@call_user_func($this->onReceiveMessage, $client_token, $client_message_id, $client_message);

						 }
						}
					}
				}
			}
		}
	}

	// Auxiliary functions

	private function write($socket, $xml) {
		$length = fwrite($socket, $xml."\n")  or $this->retryConnect();
		$this->debug(2, is_numeric($length) ? "Sent $length bytes" : "Failed sending", $xml);
		return $length;
	}

	private function read($socket) {
		$response = fread($socket, 1387);
    $meta_data = stream_get_meta_data($socket);
  	$length = (is_string($response) ? (strlen($response) == 1 ? 'character '.ord($response) : strlen($response).' bytes') : json_encode($response));
  	$this->debug(2, $response === false ? "Failed reading" : "Read $length", $response);
  	return (is_string($response) ? trim($response) : $response);
	}

  	private function analyzeData($packetData) {
			if($this->isValidStanza($packetData)) {
				$this->debug(2, "Message is not fragmented. \n");
				return $packetData;
			}

      if($packetData == "") {
  				$this->debug(2, "Keepalive exchange \n");
          $this->write($this->remote, " ");
        } elseif($this->isInvalidStanza($packetData)) {
    				$this->debug(2, "Message doesn't have valid XMPP header and footer. \n");
        }

		if($this->isFooterMissing($packetData)) {
			$this->debug(2, "Message is fragmented. \n");
      $this->parsedMessage = null;
			$this->isParsing = true;

				$this->debug(2, "Parsing message.. \n");
				$this->parsedMessage .= $packetData;

    	} elseif($this->isParsing) {
				$this->parsedMessage .= $packetData;
			}

			if ($this->isParsing && $this->isFooterMatch($packetData)) {
	       $this->debug(2, "Message parsed succesfully. \n");
         $this->isParsing = false;
         return $this->parsedMessage;
			}

      if($this->isXMLStreamError($packetData)) {
        $this->debug(2, "Stream Error: Invalid XML. \n");
        $this->debug(2, "FCM Server is failed to parse the XML payload. \n");
        $this->exit();
      }

		}

    private function sendACK($registration_id, $message_id) {
      $this->write($this->remote, '<message><gcm xmlns="google:mobile:data">{"to":"'.$registration_id.'","message_id":'. json_encode(htmlspecialchars($message_id, ENT_NOQUOTES), JSON_UNESCAPED_UNICODE) .',"message_type":"ack"}</gcm></message>');
}

  private function sendBack($registration_id, $client_message, $client_message_id) {
    $this->debug(2, "Sending message: " . $client_message);
    $this->debug(2, "Message ID: " . $client_message_id);
  $this->write($this->remote, '<message><gcm xmlns="google:mobile:data">
{
	"to":"'.$registration_id.'",
	"data":{"body":' . json_encode(htmlspecialchars($client_message, ENT_NOQUOTES), JSON_UNESCAPED_UNICODE) .'},
	"message_id":'.json_encode(htmlspecialchars($client_message_id . "-N", ENT_NOQUOTES), JSON_UNESCAPED_UNICODE).'
}
</gcm></message>');
}

function send($full_json_message) {
  $this->write($this->remote, $full_json_message);
}

  private function isValidStanza($subject) {
    if(($this->isHeaderMatch($subject)) && $this->isFooterMatch($subject)) {
      return true;
    } else {
      return false;
    }
  }

  private function isInvalidStanza($subject) {
    if(($this->isHeaderMatch($subject)) && $this->isFooterMatch($subject)) {
      return true;
    } else {
      return false;
    }
  }

  private function isFooterMissing($subject) {
    if(($this->isHeaderMatch($subject)) && !$this->isFooterMatch($subject)) {
      return true;
    } else {
      return false;
    }
  }

	private function isHeaderMatch($subject) {
	if (preg_match("/<message to=\".*.\" from=\".*.\" type=\".*.\">/", $subject) || preg_match("/<message>/", $subject)) {
		return true;
	} else {
		return false;
	}
	}

	private function isFooterMatch($subject) {
		if (preg_match("/<\/message>/", $subject)) {
			return true;
		} else {
			return false;
		}
	}

  private function isControlMessage($subject) {
    if (preg_match('/<message><data:gcm xmlns:data="google:mobile:data">/', $subject)) {
      return true;
    } else {
    return false;
  }
}

private function isXMLStreamError($subject) {
  if (preg_match('/<stream:error><invalid-xml xmlns="urn:ietf:params:xml:ns:xmpp-streams"\/><\/stream:error>/', $subject)) {
    return true;
  } else {
    return false;
  }
}

private function retryConnect() {
  $this->retryCount++;

  if($this->retryCount == 10) {
  echo "Retry attempt has exceeded (10) . \n";
  echo "Failed to retry connect to the remote server. \n"; $this->exit();
}

	echo "Reconnecting to the FCM Server in 10 seconds. \n";
	sleep(10);
	$this->stream();
}

	private $opening, $closing;

	private function parseXML($xml) {
		$doc = new DOMDocument();
		$doc->recover = true;
		if ($doc->loadXML($xml, LIBXML_NOWARNING|LIBXML_NOERROR) && $doc->documentElement && $doc->documentElement->localName == 'stream')
			$this->opening = substr($xml, 0, strpos($xml, '>')+1) and $this->closing = "</{$doc->documentElement->tagName}>";
		elseif ($this->opening && $this->closing && $doc->loadXML($this->opening.$xml.$this->closing, LIBXML_NOWARNING|LIBXML_NOERROR) && $doc->documentElement && $doc->documentElement->localName == 'stream')
			return $doc->documentElement;
		else
			return false;
		return $doc->documentElement;
	}

	private function debug($level, $title, $content = '') {
		if ($this->debugFile && $level <= $this->debugLevel)
			fwrite($this->debugFile, "=== $title === ".date('H:i:s')." ===\n". (strlen(trim($content)) ? trim($content)."\n" : ""));
	}

}


<?
