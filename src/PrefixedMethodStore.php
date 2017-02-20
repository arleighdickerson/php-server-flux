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
    const HANDLER_METHOD_PREFIX = 'handle';

    protected function onDispatch($payload) {
        $type = ArrayHelper::getValue($payload, 'type');
        if (($method = ArrayHelper::getValue($this->getMethodMap(), $type)) !== null) {
            KwArgs::apply([$this, $method], $payload);
        }
    }

    private static $_methodMap = [];

    private function getMethodMap() {
        if (!isset(self::$_methodMap[static::class])) {
            $reflector = new \ReflectionClass($this);
            $methods = array_filter($reflector->getMethods(), function (\ReflectionMethod $method) {
                return StringHelper::startsWith($method->getName(), self::HANDLER_METHOD_PREFIX)
                    && $method->getName() != self::HANDLER_METHOD_PREFIX;
            });
            $names = ArrayHelper::getColumn($methods, function (\ReflectionMethod $method) {
                return $method->getName();
            });
            self::$_methodMap[static::class] = ArrayHelper::index(
                $names,
                function ($name) {
                    return lcfirst(substr($name, strlen(self::HANDLER_METHOD_PREFIX)));
                }
            );
        }
        return self::$_methodMap[static::class];
    }
}
