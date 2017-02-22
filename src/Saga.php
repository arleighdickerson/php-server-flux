<?php


namespace aea\flux;


use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

abstract class Saga extends Store {
    private $_sagaId;
    private $_getState;
    private $_setState;
    private $_end;

    public function __construct(array $config = []) {
        foreach (['sagaId', 'getState', 'setState', 'end'] as $prop) {
            if (($this->{"_$prop"} = ArrayHelper::remove($config, $prop)) === null) {
                throw new InvalidConfigException("missing required config key `$prop`");
            }
        }
        parent::__construct($config);
    }

    public function getSagaId() {
        return $this->_sagaId;
    }

    /**
     * @return array
     */
    public function initialState() {
        return [];
    }

    /**
     * @return array
     */
    protected function getState() {
        return call_user_func($this->_getState, $this);
    }

    /**
     * @param array $newState
     * @return array
     */
    protected function setState(array $newState) {
        return call_user_func($this->_setState, $this, $newState);
    }

    protected function end() {
        return call_user_func($this->_end, $this);
    }
}
