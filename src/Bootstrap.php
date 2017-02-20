<?php


namespace flux;

use Yii;

use yii\base\BootstrapInterface;
use yii\base\Object;
use yii\caching\Cache;
use yii\helpers\VarDumper;
use zpt\anno\AnnotationFactory;
use ReflectionClass;
use ArrayObject;

class Bootstrap extends Object implements BootstrapInterface {
    public $cache = 'cache';
    public $prefix = 'annotation';

    public function bootstrap($app) {
        if (!Yii::$container->hasSingleton(AnnotationFactory::class)) {
            Yii::$container->setSingleton(AnnotationFactory::class, function () {
                return $this->buildAnnotationFactory();
            });
        }
        if (!Yii::$container->hasSingleton(Dispatcher::class)) {
            Yii::$container->setSingleton(Dispatcher::class);
        }
    }

    private function buildAnnotationFactory() {
        $factory = new AnnotationFactory();
        if (($cache = Yii::$app->get($this->cache, false)) !== null) {
            /** @var Cache $cache */
            if ($this->prefix !== null) {
                $cache = clone $cache;
                $cache->keyPrefix = $this->prefix;
            }
            $reflector = new ReflectionClass($factory);
            $prop = $reflector->getProperty('_cache');
            $prop->setAccessible(true); //yolo
            $prop->setValue(
                $factory, new CacheWrapper(
                    new ArrayObject(), //l0 in-memory
                    $cache             //l1 persistent
                )
            );
            $prop->setAccessible(false);
        }
        return $factory;
    }
}

class CacheWrapper implements \ArrayAccess {
    /**
     * @var \ArrayAccess[]
     */
    private $_caches;

    public function __construct() {
        $this->_caches = func_get_args();
    }

    public function offsetExists($offset) {
        foreach ($this->_caches as $cache) {
            if ($cache->offsetExists($offset)) {
                return true;
            }
        }
        return false;
    }

    public function offsetGet($offset) {
        foreach ($this->_caches as $level => $cache) {
            if ($cache->offsetExists($offset)) {
                $value = $cache->offsetGet($offset);
                for ($i = 0; $i < $level; $i++) {
                    $this->_caches[$i][$offset] = $value;
                }
                return $value;
            }
        }
        throw new \OutOfBoundsException(
            "Offset `" . VarDumper::dumpAsString($offset) . '` was not found at any cache level'
        );
    }

    public function offsetSet($offset, $value) {
        foreach ($this->_caches as $cache) {
            $cache->offsetSet($offset, $value);
        }
    }

    public function offsetUnset($offset) {
        foreach ($this->_caches as $cache) {
            $cache->offsetUnset($offset);
        }
    }
}