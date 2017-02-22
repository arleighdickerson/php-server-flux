<?php


namespace aea\flux;


use yii\base\InvalidConfigException;
use yii\caching\Dependency;
use yii\caching\ExpressionDependency;
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

    /**
     * @return string
     */
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
     * @return Dependency|null
     */
    public function getDependency() {
        return null;
    }

    /**
     * @return int|null
     */
    public function getDuration() {
        return null;
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
