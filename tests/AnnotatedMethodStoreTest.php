<?php

class AnnotatedMethodStoreTest extends StoreTest {
    protected static function createStore() {
        return new AnnotatedMethodStore([
            'eventHandled' => function () {
                static::receivesPayload();
            }
        ]);
    }
}

class AnnotatedMethodStore extends \flux\AnnotatedMethodStore {
    public $eventHandled;

    /**
     * @handles event
     */
    public function invokeEventHandler() {
        call_user_func($this->eventHandled);
    }
}
