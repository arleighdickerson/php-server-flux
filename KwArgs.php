<?php


namespace flux;


use yii\base\Exception;
use yii\helpers\VarDumper;

abstract class KwArgs {
    public static function apply(callable $to, array $params) {
        $params = static::bindParams($to, $params);
        return call_user_func_array($to, $params);
    }

    protected static function bindParams($method, $params) {
        $params['payload'] = $params;
        $method = static::reflectOn($method);
        $args = [];
        $missing = [];
        $actionParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = (array)$params[$name];
                } elseif (!is_array($params[$name])) {
                    $args[] = $actionParams[$name] = $params[$name];
                } else {
                    throw new Exception(\Yii::t('yii', 'Invalid data received for store handler parameter "{param}".', [
                        'param' => $name,
                    ]));
                }
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