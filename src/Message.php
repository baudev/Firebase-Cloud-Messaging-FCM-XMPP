<?php
/**
 * User: Baudev
 * Github: https://github.com/baudev
 * Date: 18/06/2018
 */

namespace FCMStream;


use FCMStream\exceptions\FCMMessageFormatException;

class Message implements \JsonSerializable
{

    public CONST PRIORITY_NORMAL = "normal";
    public CONST PRIORITY_HIGH = "high";

    private $to;
    private $condition;
    private $messageId;
    private $collapseKey;
    private $priority;
    private $contentAvailable;
    private $mutableContent;
    private $timeToLive;
    private $deliveryReceiptRequested;
    private $dryRun;
    private $data;
    private $notification;

    /**
     * Message constructor.
     * @param null|string $to
     * @param null|string $condition
     * @param null|string $messageId
     * @param null|string $collapseKey
     * @param null|string $priority
     * @param bool|null $contentAvailable
     * @param bool|null $mutableContent
     * @param int $timeToLive
     * @param bool $deliveryReceiptRequested
     * @param bool $dryRun
     * @param array|null $data
     * @param array|null $notification
     * @throws FCMMessageFormatException
     */
    public function __construct(?string $to = null, ?string $condition = null, ?string $messageId = null, ?string $collapseKey = null, string $priority = self::PRIORITY_NORMAL, ?bool $contentAvailable = null, ?bool $mutableContent = null, int $timeToLive = 2419200, bool $deliveryReceiptRequested = false, bool $dryRun = false, ?array $data = null, ?array $notification = null)
    {
        if (!empty($to)) {
            $this->setTo($to);
        }
        if (!empty($condition)) {
            $this->setCondition($condition);
        }
        if (!empty($messageId)) {
            $this->setMessageId($messageId);
        }
        if (!empty($collapseKey)) {
            $this->setCollapseKey($collapseKey);
        }
        $this->setPriority($priority);
        if (!empty($contentAvailable)) {
            $this->setContentAvailable($contentAvailable);
        }
        if (!empty($mutableContent)) {
            $this->setMutableContent($mutableContent);
        }
        $this->setTimeToLive($timeToLive);
        $this->setDeliveryReceiptRequested($deliveryReceiptRequested);
        $this->setDryRun($dryRun);
        $this->setData($data);
        $this->setNotification($notification);
    }

    /**
     * @return null|string
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @param null|string $to
     * @throws FCMMessageFormatException
     */
    public function setTo(?string $to): void
    {
        if($this->getCondition()==null) {
            $this->to = $to;
        } else {
            throw new FCMMessageFormatException("You cant set 'to' parameter and 'condition' one together", FCMMessageFormatException::MESSAGE_FORMAT_CONDITION_OR_TO);
        }
    }

    /**
     * @return null|string
     */
    public function getCondition(): ?string
    {
        return $this->condition;
    }

    /**
     * @param null|string $condition
     * @throws FCMMessageFormatException
     */
    public function setCondition(?string $condition): void
    {
        if($this->getTo()==null) {
            $this->condition = $condition;
        } else {
            throw new FCMMessageFormatException("You cant set 'to' parameter and 'condition' one together", FCMMessageFormatException::MESSAGE_FORMAT_CONDITION_OR_TO);
        }
    }

    /**
     * @return null|string
     * @throws FCMMessageFormatException
     */
    public function getMessageId(): ?string
    {
        if($this->messageId != null) {
            return $this->messageId;
        } else {
            throw new FCMMessageFormatException("Message_id can't be null", FCMMessageFormatException::MESSAGE_ID_CANT_BE_NULL);
        }
    }

    /**
     * @param null|string $messageId
     */
    public function setMessageId(?string $messageId): void
    {
        $this->messageId = $messageId;
    }

    /**
     * @return null|string
     */
    public function getCollapseKey(): ?string
    {
        return $this->collapseKey;
    }

    /**
     * @param null|string $collapseKey
     */
    public function setCollapseKey(?string $collapseKey): void
    {
        $this->collapseKey = $collapseKey;
    }

    /**
     * @return null|string
     */
    public function getPriority(): string
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     * @throws FCMMessageFormatException
     */
    public function setPriority(string $priority): void
    {
        if($priority != self::PRIORITY_HIGH && $priority != self::PRIORITY_NORMAL){
            throw new FCMMessageFormatException("Message priority parameter must be PRIORITY_HIGH or PRIORITY_NORMAL", FCMMessageFormatException::MESSAGE_FORMAT_PRIORITY_NEED_TO_BE_HIGH_OR_NORMAL);
        }
        $this->priority = $priority;
    }

    /**
     * @return bool|null
     */
    public function getContentAvailable(): ?bool
    {
        return $this->contentAvailable;
    }

    /**
     * @param bool|null $contentAvailable
     */
    public function setContentAvailable(?bool $contentAvailable): void
    {
        $this->contentAvailable = $contentAvailable;
    }

    /**
     * @return bool|null
     */
    public function getMutableContent(): ?bool
    {
        return $this->mutableContent;
    }

    /**
     * @param bool|null $mutableContent
     */
    public function setMutableContent(?bool $mutableContent): void
    {
        $this->mutableContent = $mutableContent;
    }

    /**
     * @return int
     */
    public function getTimeToLive(): int
    {
        return $this->timeToLive;
    }

    /**
     * @param int $timeToLive
     * @throws FCMMessageFormatException
     */
    public function setTimeToLive(int $timeToLive): void
    {
        if($timeToLive>2419200 || $timeToLive<0){
            throw new FCMMessageFormatException("Time-to-live parameter must be between 0 seconds and 2419200 (4 weeks)", FCMMessageFormatException::MESSAGE_TIME_TO_LIVE_MUST_BE_BETWEEN_0_AND_4_WEEKS);
        }
        $this->timeToLive = $timeToLive;
    }

    /**
     * @return bool
     */
    public function isDeliveryReceiptRequested(): bool
    {
        return $this->deliveryReceiptRequested;
    }

    /**
     * @param bool $deliveryReceiptRequested
     */
    public function setDeliveryReceiptRequested(bool $deliveryReceiptRequested): void
    {
        $this->deliveryReceiptRequested = $deliveryReceiptRequested;
    }

    /**
     * @return bool
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * @param bool $dryRun
     */
    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array|null $data
     */
    public function setData(?array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array|null
     */
    public function getNotification(): ?array
    {
        return $this->notification;
    }

    /**
     * @param array|null $notification
     */
    public function setNotification(?array $notification): void
    {
        $this->notification = $notification;
    }


    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $return = array();
        // for each variable
        foreach ($this as $key => $value){
            $method = 'get' . ucwords($key);
            // if the getter exists
            if(method_exists($this, $method)){
                // check the value isn't empty
                if($this->$method() != null){
                    $return[strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $key))] = $this->$method();
                }
            }
        }
        return $return;
    }
}