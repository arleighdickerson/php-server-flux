<?php


namespace flux;


use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;

/**
 * Class PrefixedMethodStore
 * @package flux
 */
abstract class PrefixedMethodStore extends Store {
    private static $_methodMap;

    const HANDLER_METHOD_PREFIX = 'handle';

    protected final function onDispatch($payload) {
        $type = ArrayHelper::getValue($payload, 'type');
        if (($method = ArrayHelper::getValue($this->getMethodMap(), $type)) !== null) {
            $this->applyToPayload($method, $payload);
        }
    }

    private function getMethodMap() {
        if (static::$_methodMap === null) {
            $reflector = new \ReflectionClass($this);
            $methods = array_filter($reflector->getMethods(), function (\ReflectionMethod $method) {
                return StringHelper::startsWith($method->getName(), self::HANDLER_METHOD_PREFIX)
                    && $method->getName() != self::HANDLER_METHOD_PREFIX;
            });
            $names = ArrayHelper::getColumn($methods, function (\ReflectionMethod $method) {
                return $method->getName();
            });
            static::$_methodMap = ArrayHelper::index(
                $names,
                function ($name) {
                    return lcfirst(substr($name, strlen(self::HANDLER_METHOD_PREFIX)));
                }
            );
        }
        return static::$_methodMap;
    }

    private function applyToPayload($method, array $payload) {
        return call_user_func_array(
            [$this, $method],
            $this->bindParams($method, $payload)
        );
    }

    private function bindParams($method, $params) {
        $params['payload'] = $params;
        $method = new \ReflectionMethod($this, $method);
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
            throw new Exception(\Yii::t('yii', 'Store handler missing required parameters: {params}', [
                'params' => implode(', ', $missing),
            ]));
        }
        return $args;
    }
}

