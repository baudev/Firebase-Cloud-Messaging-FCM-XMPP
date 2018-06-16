<?php
/**
 * User: Baudev
 * Date: 15/06/2018
 */

namespace FCMStream\helpers;

use DOMDocument;

class Functions
{

    private static $opening, $closing;

    public static function isValidStanza($subject)
    {
        if ((static::isHeaderMatch($subject)) && static::isFooterMatch($subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isInvalidStanza($subject)
    {
        if ((static::isHeaderMatch($subject)) && static::isFooterMatch($subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isFooterMissing($subject)
    {
        if ((static::isHeaderMatch($subject)) && !static::isFooterMatch($subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isHeaderMatch($subject)
    {
        if (preg_match("/<message to=\".*.\" from=\".*.\" type=\".*.\">/", $subject) || preg_match("/<message>/", $subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isFooterMatch($subject)
    {
        if (preg_match("/<\/message>/", $subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isControlMessage($subject)
    {
        if (preg_match('/<message><data:gcm xmlns:data="google:mobile:data">/', $subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isXMLStreamError($subject)
    {
        if (preg_match('/<stream:error><invalid-xml xmlns="urn:ietf:params:xml:ns:xmpp-streams"\/><\/stream:error>/', $subject)) {
            return true;
        } else {
            return false;
        }
    }

    public static function parseXML($xml)
    {
        $doc          = new DOMDocument();
        $doc->recover = true;
        if ($doc->loadXML($xml, LIBXML_NOWARNING | LIBXML_NOERROR) && $doc->documentElement && $doc->documentElement->localName == 'stream') {
            self::$opening = substr($xml, 0, strpos($xml, '>') + 1) and self::$closing = "</{$doc->documentElement->tagName}>";
        } elseif (self::$opening && self::$closing && $doc->loadXML(self::$opening . $xml . self::$closing, LIBXML_NOWARNING | LIBXML_NOERROR) && $doc->documentElement && $doc->documentElement->localName == 'stream') {
            return $doc->documentElement;
        } else {
            return false;
        }

        return $doc->documentElement;
    }

    /**
     * @return mixed
     */
    public static function getOpening()
    {
        return self::$opening;
    }

    /**
     * @param mixed $opening
     */
    public static function setOpening($opening): void
    {
        self::$opening = $opening;
    }

    /**
     * @return mixed
     */
    public static function getClosing()
    {
        return self::$closing;
    }

    /**
     * @param mixed $closing
     */
    public static function setClosing($closing): void
    {
        self::$closing = $closing;
    }




}