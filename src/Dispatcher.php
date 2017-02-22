<?php

namespace aea\flux;

use yii\base\Component;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * Class Dispatcher
 * @package flux
 */
class Dispatcher extends Component {
    const EVENT_BEFORE_DISPATCH = 'beforeDispatch';
    const EVENT_AFTER_DISPATCH = 'afterDispatch';
    const EVENT_FAILED_DISPATCH = 'failedDispatch';

    private $_callbacks = [];
    private $_isDispatching = false;
    private $_isHandled = [];
    private $_isPending = [];
    private $_lastId = 0;
    private $_pendingPayload;

    /**
     * @param callable $callback
     * @return string
     */
    public function register(callable $callback) {
        $id = $this->_lastId++;
        $this->_callbacks[$id] = $callback;
        return $id;
    }

    /**
     * @param string $id
     * @return void
     */
    public function unregister($id) {
        $id = $this->resolveKey($id);
        $this->assert(isset($this->_callbacks[$id]),
            'Dispatcher.unregister(...): {} does not map to a registered callback.',
            $id
        );
        unset($this->_callbacks[$id]);
    }

    /**
     * @return void
     */
    public function waitFor() {
        $ids = array_map([$this, 'resolveKey'], func_get_args());
        $this->assert(
            $this->_isDispatching,
            'Dispatcher.waitFor(...): Must be invoked while dispatching.',
            $ids
        );
        foreach ($ids as $id) {
            if ($this->_isPending[$id]) {
                $this->assert(
                    isset($this->_isHandled[$id]),
                    'Dispatcher.waitFor(...): Circular dependency detected while waiting for {}',
                    $id
                );
                continue;
            }
            $this->assert(
                isset($this->_callbacks[$id]),
                'Dispatcher.waitFor(...): {} does not map to a registered callback.',
                $id
            );
            $this->invokeCallback($id);
        }
    }

    /**
     * @param  DispatchEvent|array|string $type
     * @param  array $payload
     * @return string the processed event's uuid
     * @throws \Exception
     */
    public function dispatch($type, $payload = []) {
        $this->assert(
            !$this->_isDispatching,
            'Dispatch.dispatch(...): Cannot dispatch in the middle of a dispatch.'
        );

        /** @var DispatchEvent $event */
        $event = $type instanceof DispatchEvent
            ? $type
            : new DispatchEvent([
                'payload' => is_string($type) ? compact('type') + $payload : $type
            ]);

        $this->trigger(self::EVENT_BEFORE_DISPATCH, $event);
        $this->startDispatching($event);
        try {
            foreach ($this->_callbacks as $id => $callback) {
                if (!ArrayHelper::getValue($this->_isPending, $id, false)) {
                    $this->invokeCallback($id);
                }
            }
            $this->trigger(self::EVENT_AFTER_DISPATCH, $event);
        } catch (\Exception $e) {
            $this->trigger(self::EVENT_FAILED_DISPATCH, $event);
            throw $e;
        } finally {
            $this->stopDispatching();
        }
        return $event->uuid;
    }

    /**
     * @return boolean
     */
    public function isDispatching() {
        return $this->_isDispatching;
    }

    private function invokeCallback($id) {
        $id = $this->resolveKey($id);
        $this->_isPending[$id] = true;
        call_user_func($this->_callbacks[$id], $this->_pendingPayload);
        $this->_isHandled[$id] = true;
    }

    private function startDispatching($event) {
        foreach ($this->_callbacks as $id => $callback) {
            $this->_isPending[$id] = false;
            $this->_isHandled[$id] = false;
        }
        $this->_pendingPayload = $event;
        $this->_isDispatching = true;
    }

    private function stopDispatching() {
        $this->_pendingPayload = null;
        $this->_isDispatching = false;
    }

    private function assert($truthy, $message, $value = '') {
        if (!$truthy) {
            throw new Exception(str_replace('{}', VarDumper::dumpAsString($value), $message));
        }
    }

    private function resolveKey($callableOrId) {
        if (is_callable($callableOrId)) {
            if (($key = array_search($callableOrId, $this->_callbacks)) !== false) {
                return $key;
            }
        }
        return $callableOrId;
    }
}
