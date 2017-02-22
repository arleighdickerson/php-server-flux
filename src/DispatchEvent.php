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
 * @property int $timestamp
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
        $this->_payload = ArrayHelper::remove($config, 'payload');
        $this->_timestamp = ArrayHelper::remove($config, 'timestamp');
        $this->_uuid = ArrayHelper::remove($config, 'uuid');
        $this->_isReplay = ArrayHelper::remove($config, 'isReplay', false);
        parent::__construct($config);
    }

    public function init() {
        parent::init();
        if ($this->_timestamp === null) {
            $this->_timestamp = microtime();
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
        return $this->_isReplay;
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
     * @return int
     */
    public function getTimestamp() {
        return $this->_timestamp;
    }
}
