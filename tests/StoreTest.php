<?php


use aea\flux\Dispatcher;

class StoreTest extends PHPUnit_Framework_TestCase {
    public static $store;
    public static $caught = false;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        static::$store = static::createStore();
    }

    public function setUp() {
        parent::setUp();
        static::$caught = false;
    }

    public function testListenerReceivesPayload() {
        $this->dispatch(['type' => 'event']);
        $this->assertTrue(static::$caught);
    }

    public static function receivesPayload() {
        static::$caught = true;
    }

    protected static function createStore() {
        return new Store([
            'onDispatch' => [static::class, 'receivesPayload']
        ]);
    }

    protected function dispatch($ev = null) {
        Yii::$container->get(Dispatcher::class)->dispatch($ev);
    }
}

class Store extends aea\flux\Store {
    public $onDispatch;

    protected function onDispatch($payload) {
        $dispatch = $this->onDispatch;
        $dispatch($payload);
    }
}