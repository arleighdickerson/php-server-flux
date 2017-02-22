<?php


namespace aea\flux;


use Ramsey\Uuid\Uuid;
use yii\base\Event;
use yii\helpers\ArrayHelper;

/**
 * Class DispatcherEvent
 * @package flux
 * @property boolean $isReplay
 * @property string $uuid
 * @property array $payload
 * @property float $timestamp
 */
class DispatchEvent extends Event {
    /**
     * @var boolean
     */
    private $_isReplay;

    /**
     * @var string
     */
    private $_uuid;

    /**
     * @var array
     */
    private $_payload;

    /**
     * @var int
     */
    private $_timestamp;

    public function __construct(array $config = []) {
        static $props = [
            'isReplay',
            'uuid',
            'payload',
            'timestamp',
        ];
        foreach ($props as $prop) {
            $this->{"_$prop"} = ArrayHelper::remove($config, $prop);
        }
        parent::__construct($config);
    }

    public function init() {
        parent::init();
        if ($this->_timestamp === null) {
            $this->_timestamp = microtime(true);
        }
        if ($this->_payload === null) {
            $this->_payload = [];
        }
        if ($this->_uuid === null) {
            $this->_uuid = (string)Uuid::getFactory()->uuid4();
        }
    }

    /**
     * @return boolean
     */
    public function getIsReplay() {
        return boolval($this->_isReplay);
    }

    /**
     * @return string
     */
    public function getUuid() {
        return $this->_uuid;
    }

    /**
     * @return array
     */
    public function getPayload() {
        return $this->_payload;
    }

    /**
     * @return float
     */
    public function getTimestamp() {
        return $this->_timestamp;
    }
}
