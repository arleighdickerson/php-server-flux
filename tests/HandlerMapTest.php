<?php


use aea\flux\Dispatcher;
use aea\flux\DispatchEvent;
use aea\flux\HandlerMap;

class HandlerMapTest extends PHPUnit_Framework_TestCase {
    const ACTION_ONE = 'ACTION_ONE';
    const ACTION_TWO = 'ACTION_TWO';
    const ACTION_THREE = 'ACTION_THREE';

    public $value = 'value';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        Yii::$container->setSingleton(Dispatcher::class);
        Yii::createObject(HandlerMappedStore::class);
    }

    public function testActionOne() {
        $this->dispatch(self::ACTION_ONE);
        $this->assertInstanceOf(DispatchEvent::class, self::$result);
    }

    public function testActionTwo() {
        $this->dispatch(self::ACTION_TWO);
        $this->assertEquals($this->value, self::$result);
    }

    public function testActionThree() {
        $this->dispatch(self::ACTION_THREE);
        $this->assertArrayHasKey('type', self::$result);
    }

    protected function dispatch($type) {
        $value = $this->value;
        Yii::$container->get(Dispatcher::class)->dispatch(compact('type', 'value'));
    }

    public static $result;
}

class HandlerMappedStore extends aea\flux\Store {
    use HandlerMap;

    protected function handlers() {
        return [
            HandlerMapTest::ACTION_ONE => function ($type, $event) {
                HandlerMapTest::$result = $event;
            },
            HandlerMapTest::ACTION_TWO => function ($type, $value) {
                HandlerMapTest::$result = $value;
            },
            function ($payload) {
                HandlerMapTest::$result = $payload;
            }
        ];
    }
}