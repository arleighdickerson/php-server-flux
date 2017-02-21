<?php


namespace flux;


use yii\helpers\ArrayHelper;

trait HandlerMapTrait {
    private $_handlerMap;

    protected function onDispatch($event) {
        if ($this->_handlerMap === null) {
            $this->_handlerMap = $this->handlerMap();
        }
        $key = $this->resolveHandlerKey($event);
        if (isset($this->_handlerMap[$key])) {
            KwArgs::apply($this->_handlerMap[$key], $event);
        } elseif (isset($this->_handlerMap[0])) {
            KwArgs::apply($this->_handlerMap[0], $event);
        }
    }

    protected function resolveHandlerKey($event) {
        return ArrayHelper::getValue($event->payload, $this->getHandlerKey());
    }

    /**
     * @return string
     */
    protected function getHandlerKey() {
        return 'type';
    }

    /**
     * @return callable[]
     */
    protected abstract function handlerMap();
}
