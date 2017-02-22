<?php


namespace aea\flux\util;


use aea\flux\DispatchEvent;
use yii\base\Exception;
use yii\helpers\VarDumper;

abstract class NamedParameters {
    public static function apply(callable $handler, DispatchEvent $event) {
        $namedParams = static::createNamedParams($event);
        $arguments = static::createArguments($handler, $namedParams);
        return call_user_func_array($handler, $arguments);
    }

    protected static function createNamedParams(DispatchEvent $event) {
        $params = $event->payload;
        $params['payload'] = $params;
        $params['event'] = $event;
        $params['uuid'] = $event->uuid;
        $params['timestamp'] = $event->timestamp;
        return $params;
    }

    protected static function createArguments($target, $params) {
        $method = static::reflectOn($target);
        $args = [];
        $missing = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new Exception(\Yii::t('yii', '{closure} missing required parameters: {params}', [
                'params' => implode(', ', $missing),
                'closure' => VarDumper::dumpAsString($method),
            ]));
        }
        return $args;
    }

    protected static function reflectOn($callable) {
        if (is_array($callable)) {
            list($target, $name) = $callable;
            return new \ReflectionMethod($target, $name);
        }
        if (is_string($callable)) {
            return new \ReflectionFunction($callable);
        }
        if (is_callable($callable)) {
            $closure = &$callable;
            return new \ReflectionFunction($closure);
        }
    }
}