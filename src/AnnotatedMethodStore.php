<?php


namespace flux;

use ReflectionClass;
use ReflectionMethod;
use yii\helpers\ArrayHelper;
use Yii;
use zpt\anno\AnnotationFactory;

abstract class AnnotatedMethodStore extends Store {
    public $annotation = 'handles';

    /**
     * @var AnnotationFactory
     */
    private $_annotationFactory;

    public function __construct(array $config = []) {
        $this->_annotationFactory = ArrayHelper::remove($config, 'annotationFactory');
        parent::__construct($config);
    }

    public function init() {
        parent::init();
        if ($this->_annotationFactory === null) {
            $this->_annotationFactory = Yii::$container->get(AnnotationFactory::class);
        }
    }

    protected function onDispatch($payload) {
        if (isset($payload['type'])) {
            $methods = array_filter($this->getMethodMap(), function ($types) use ($payload) {
                return in_array($payload['type'], $types);
            });
            foreach ($methods as $method => $events) {
                KwArgs::apply([$this, $method], $payload);
            }
        }
    }

    private static $_methodMap = [];

    private function getMethodMap() {
        if (!isset(self::$_methodMap[static::class])) {
            $reflector = new ReflectionClass($this);
            $methods = array_filter($reflector->getMethods(), function (ReflectionMethod $method) {
                return $this->_annotationFactory->get($method)->isAnnotatedWith($this->annotation);
            });
            $names = ArrayHelper::getColumn($methods, function (ReflectionMethod $method) {
                return $method->getName();
            });
            $annotations = array_map(function (ReflectionMethod $method) {
                $value = $this->_annotationFactory->get($method)->offsetGet($this->annotation);
                return is_string($value) ? [$value] : $value;
            }, $methods);
            self::$_methodMap[static::class] = array_combine($names, $annotations);
        }
        return self::$_methodMap[static::class];
    }
}
