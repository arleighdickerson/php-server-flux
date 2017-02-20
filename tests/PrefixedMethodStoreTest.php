<?php

class PrefixedMethodStoreTest extends StoreTest {
    protected static function createStore() {
        return new PrefixedMethodStore([
            'eventHandled' => function () {
                static::receivesPayload();
            }
        ]);
    }
}

class PrefixedMethodStore extends \flux\PrefixedMethodStore {
    public $eventHandled;

    public function handleEvent() {
        call_user_func($this->eventHandled);
    }
}
