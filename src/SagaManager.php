<?php


namespace aea\flux;


use aea\flux\util\NamedParameters;
use yii\base\Behavior;
use yii\base\Exception;
use yii\caching\Cache;
use yii\caching\FileCache;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\mutex\FileMutex;
use yii\mutex\Mutex;

class SagaManager extends Behavior {
    /**
     * @var Cache
     */
    public $cache = [
        'class' => FileCache::class,
        'gcProbability' => 100,
        'keyPrefix' => '',
        'cacheFileSuffix' => '.saga'
    ];

    /**
     * @var Mutex
     */
    public $mutex = FileMutex::class;
    public $lockTimeout = 10;

    public $managedClasses = [];

    public function init() {
        parent::init();
        $this->cache = Instance::ensure($this->cache, Cache::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);
    }

    public function events() {
        return [
            Dispatcher::EVENT_BEFORE_DISPATCH => function (DispatchEvent $event) {
                $this->beforeDispatch($event);
            },
            Dispatcher::EVENT_AFTER_DISPATCH => function (DispatchEvent $event) {
                $this->afterDispatch($event);
            }
        ];
    }

    // ======================================================
    // Lifecycle Event Hooks
    // ======================================================

    protected function beforeDispatch($event) {
        foreach ($this->managedClasses as $class => $shouldBegin) {
            if (NamedParameters::apply($shouldBegin, $event)) {
                $this->addSagaToCache($this->instantiate($class));
            }
        }
    }

    protected function afterDispatch($event) {
        return;
    }

    // ======================================================
    // Saga Operations
    // ======================================================

    protected function getState(Saga $saga) {
        return $this->criticalSection($saga->getSagaId(), function () use ($saga) {
            list($sagaClass, $state) = $this->cache->get($saga->getSagaId());
            return $state;
        });
    }

    protected function setState(Saga $saga, $newState) {
        return $this->criticalSection($saga->getSagaId(), function () use ($saga, $newState) {
            list($sagaClass, $oldState) = $this->cache->get($saga->getSagaId());
            $state = ArrayHelper::merge($oldState, $newState);
            $this->cache->set($saga->getSagaId(), [$sagaClass, $state]);
            return $state;
        });
    }

    protected function end(Saga $saga) {
        $saga->getDispatcher()->unregister($saga->dispatchToken);
        $this->criticalSection([$saga->getSagaId(),/* 'keyset' */], function () use ($saga) {
            //$keyset = $this->cache->get('keyset');
            //unset($keyset[$saga->getSagaId()]);
            //$this->cache->set('keyset', $keyset);
            $this->cache->delete($saga->getSagaId());
        });
    }

    protected function instantiate($class, $sagaId = null) {
        if ($sagaId === null) {
            $sagaId = \Yii::$app->security->generateRandomString(8);
        }
        $config = $this->getThunks() + compact('class', 'sagaId');
        return \Yii::createObject($config);
    }

    private $_thunks;

    protected function getThunks() {
        if ($this->_thunks === null) {
            $getState = function ($saga) {
                return $this->getState($saga);
            };
            $setState = function ($saga, $newState) {
                return $this->setState($saga, $newState);
            };
            $end = function ($saga) {
                return $this->end($saga);
            };
            $this->_thunks = compact('getState', 'setState', 'end');
        }
        return $this->_thunks;
    }

    // ======================================================
    // Cache Operations
    // ======================================================

    protected function addSagaToCache(Saga $saga) {
        $this->criticalSection([$saga->getSagaId(), /*'keyset'*/], function () use ($saga) {
            //$keyset = $this->cache->get('keyset');
            //$keyset[$saga->getSagaId()] = $saga->getSagaId();
            //$this->cache->set('keyset', $keyset);
            $this->cache->set($saga->getSagaId(), [$saga->className(), $saga->initialState()]);
        });
    }

    private $_flyweights = [];

    protected function findOne($sagaId) {
        if (isset($this->_flyweights[$sagaId])) {
            return $this->_flyweights[$sagaId];
        }
        list($sagaClass,) = $this->cache->get($sagaId);
        $saga = $this->instantiate($sagaClass, $sagaId);
        $this->_flyweights[$sagaId] = $saga;
        return $saga;
    }

    protected function keySet() {
        $this->criticalSection('keyset', function () {
            $cache = Instance::ensure($this->cache, FileCache::class);
            $glob = \Yii::getAlias($cache->cachePath);
            for ($i = 0; $i <= $cache->directoryLevel; $i++) {
                $glob .= '/*';
            }
            $glob .= $cache->cacheFileSuffix;
            return array_map(function ($path) use ($cache) {
                return substr(pathinfo($path, PATHINFO_FILENAME), 0, strlen($path) - strlen($cache->cacheFileSuffix));
            }, glob($glob));
        });
    }


    protected function findAll() {
        //return $this->criticalSection('keyset', function () {
        return array_map(
            $this->cache->multiGet($this->keySet()/*ArrayHelper::getValue($this->cache, 'keyset', [])*/),
            [$this, 'findOne']
        );
        //});
    }

    private function criticalSection($names, callable $thunk) {
        if (!is_array($names)) {
            $names = [$names];
        }
        foreach ($names as $name) {
            $this->lock($name);
        }
        $result = call_user_func($thunk);
        foreach ($names as $name) {
            $this->unlock($name);
        }
        return $result;
    }

    private function unlock($name) {
        $this->mutex->release($name);
    }

    private function lock($name) {
        if (!$this->mutex->acquire($name, $this->lockTimeout)) {
            throw new Exception("could not acquire lock`$name`");
        }
    }
}
