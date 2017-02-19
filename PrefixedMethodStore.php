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

    protected final function onDispatch($payload) {
        $type = ArrayHelper::getValue($payload, 'type');
        if (($method = ArrayHelper::getValue($this->getMethodMap(), $type)) !== null) {
            KwArgs::apply([$this, $method], $payload);
        }
    }

    private function getMethodMap() {
        $reflector = new \ReflectionClass($this);
        $methods = array_filter($reflector->getMethods(), function (\ReflectionMethod $method) {
            return StringHelper::startsWith($method->getName(), self::HANDLER_METHOD_PREFIX)
                && $method->getName() != self::HANDLER_METHOD_PREFIX;
        });
        $names = ArrayHelper::getColumn($methods, function (\ReflectionMethod $method) {
            return $method->getName();
        });
        return ArrayHelper::index(
            $names,
            function ($name) {
                return lcfirst(substr($name, strlen(self::HANDLER_METHOD_PREFIX)));
            }
        );
    }
}
