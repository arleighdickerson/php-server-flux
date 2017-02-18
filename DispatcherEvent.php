<?php


namespace flux;


use yii\base\Event;

/**
 * Class DispatcherEvent
 * @package flux
 */
class DispatcherEvent extends Event {
    public $isValid = true;

    /**
     * @var array
     */
    private $_payload;

    public function __construct($payload, array $config = []) {
        $this->_payload = $payload;
        parent::__construct($config);
    }

    public function getPayload() {
        return $this->_payload;
    }
}

