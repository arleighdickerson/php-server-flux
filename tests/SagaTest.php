<?php


use aea\flux\Dispatcher;
use aea\flux\HandlerMap;
use aea\flux\Saga;
use aea\flux\SagaManager;

class SagaTest extends PHPUnit_Framework_TestCase {
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        Yii::$container->setSingleton(Dispatcher::class, [
            'as sagaManager' => [
                'class' => SagaManager::class,
                'managedClasses' => [
                    TestSaga::class => function ($type) {
                        return $type == 'begin';
                    }
                ]
            ]
        ]);
    }

    public function testBegin() {
        $this->dispatch('begin');
    }

    public function testSetState() {
        $this->dispatch('updateState', [
            'newState' => [
                1 => 'one'
            ]
        ]);
    }

    public function testEnd() {
        $this->dispatch('end');
    }

    protected function dispatch($a, $b = []) {
        Yii::$container->get(Dispatcher::class)->dispatch($a, $b);
    }

}

class TestSaga extends Saga {
    use HandlerMap;

    protected function handlers() {
        return [
            'state' => function ($newState) {
                $this->setState($newState);
            },
            'end' => function () {
                $this->end();
            }
        ];
    }
}