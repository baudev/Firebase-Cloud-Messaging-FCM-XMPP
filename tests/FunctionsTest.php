<?php
/**
 * Created by Baudev
 * http://github.com/baudev
 * Date: 19/04/2019
 */

class FunctionsTest extends \PHPUnit\Framework\TestCase
{

    public function testIsHeaderMatch(){
        $message = '<message>
                      <gcm xmlns="google:mobile:data">
                      {
                        "message_type":"nack",
                        "message_id":"msgId1",
                        "from":"SomeInvalidRegistrationId",
                        "error":"BAD_REGISTRATION",
                        "error_description":"Invalid token on \'to\' field: SomeInvalidRegistrationId"
                      }
                      </gcm>
                    </message>';
        $this->assertTrue(\FCMStream\helpers\Functions::isHeaderMatch($message));

        $message = '<message to="xxxxxxxxxxxx@gcm.googleapis.com" from="devices@gcm.googleapis.com" type="normal"><gcm xmlns="google:mobile:data">{"data":{';
        $this->assertTrue(\FCMStream\helpers\Functions::isHeaderMatch($message));
    }

    public function testIsFooterMatch(){
        $message = '"category":"com.xxxxxxxxxxxxxxxxxxxxx"}</gcm></message>';
        $this->assertTrue(\FCMStream\helpers\Functions::isFooterMatch($message));
    }

    public function testIsControlMessage(){
        $message = '<message><data:gcm xmlns:data="google:mobile:data">{"message_type":"control""control_type":"CONNECTION_DRAINING"}</data:gcm></message>';
        $this->assertTrue(\FCMStream\helpers\Functions::isControlMessage($message));
    }

    public function testIsStreamError(){
        $message = '<stream:error><invalid-xml xmlns="urn:ietf:params:xml:ns:xmpp-streams"/></stream:error>';
        $this->assertTrue(\FCMStream\helpers\Functions::isXMLStreamError($message));
    }

    public function testParseXML(){
        // we simulate the connection negotiation
        \FCMStream\helpers\Functions::parseXML('<stream:stream to="gcm.googleapis.com"version="1.0" xmlns="jabber:client"xmlns:stream="http://etherx.jabber.org/streams">');
        \FCMStream\helpers\Functions::parseXML('<stream:features><mechanisms xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><mechanism>X-OAUTH2</mechanism><mechanism>X-GOOGLE-TOKEN</mechanism><mechanism>PLAIN</mechanism></mechanisms></stream:features>');
        \FCMStream\helpers\Functions::parseXML('<auth mechanism="PLAIN"xmlns="urn:ietf:params:xml:ns:xmpp-sasl">MTI2MjAwMzQ3OTMzQHByb2plY3RzLmdjbS5hbmFTeUIzcmNaTmtmbnFLZEZiOW1oekNCaVlwT1JEQTJKV1d0dw==</auth>');
        \FCMStream\helpers\Functions::parseXML('<success xmlns="urn:ietf:params:xml:ns:xmpp-sasl"/>');
        \FCMStream\helpers\Functions::parseXML('<stream:stream to="gcm.googleapis.com"
        version="1.0" xmlns="jabber:client"
        xmlns:stream="http://etherx.jabber.org/streams">');
        \FCMStream\helpers\Functions::parseXML('<stream:features><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"/><session xmlns="urn:ietf:params:xml:ns:xmpp-session"/></stream:features>');
        \FCMStream\helpers\Functions::parseXML('<iq type="set"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"></bind></iq>');
        \FCMStream\helpers\Functions::parseXML('<iq type="result"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"><jid>SENDER_ID@gcm.googleapis.com/RESOURCE</jid></bind></iq>');
        $this->assertNotNull(\FCMStream\helpers\Functions::getClosing());
    }

}