<?php


namespace flux;


use yii\base\Component;
use yii\base\Exception;
use yii\helpers\VarDumper;

/**
 * Class Store
 * @package flux
 * @property Dispatcher $dispatcher
 */
abstract class Store extends Component {
    const EVENT_ON_CHANGE = 'change';

    private $_dispatchToken;
    private $_changed = false;
    private $_dispatcher;

    public function __construct(Dispatcher $dispatcher, array $config = []) {
        $this->_dispatcher = $dispatcher;
        $this->_dispatchToken = $dispatcher->register(function ($payload) {
            $this->invokeOnDispatch($payload);
        });
        parent::__construct($config);
    }

    protected abstract function onDispatch($payload);

    public function addListener($callback) {
        $this->on(self::EVENT_ON_CHANGE, $callback);
    }

    public function getDispatcher() {
        return $this->_dispatcher;
    }

    public function getDispatchToken() {
        return $this->_dispatchToken;
    }

    public function hasChanged() {
        $this->assertDispatching();
        return $this->_changed;
    }

    protected function emitChange() {
        $this->assertDispatching();
        $this->_changed = true;
    }

    private function invokeOnDispatch($payload) {
        $this->_changed = false;
        $this->onDispatch($payload);
        if ($this->_changed) {
            $this->trigger(self::EVENT_ON_CHANGE);
        }
    }

    private function assertDispatching() {
        $this->assert(
            $this->getDispatcher()->isDispatching(),
            __METHOD__ . ' must be invoked while dispatching.',
            static::class
        );
    }

    private function assert($truthy, $message, $value = '') {
        if (!$truthy) {
            throw new Exception(str_replace('{}', VarDumper::dumpAsString($value), $message));
        }
    }
}
