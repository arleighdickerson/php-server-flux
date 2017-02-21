<?php


namespace aea\flux;

use aea\flux\util\NamedParameters;
use yii\helpers\ArrayHelper;

trait HandlerMap {
    private $_handlerMap;

    /**
     * @return callable[]
     */
    protected abstract function handlers();

    protected function onDispatch($event) {
        if ($this->_handlerMap === null) {
            $this->_handlerMap = $this->handlers();
        }
        $type = $this->getPayloadType($event);
        if (isset($this->_handlerMap[$type])) {
            NamedParameters::apply($this->_handlerMap[$type], $event);
        } elseif (isset($this->_handlerMap[0])) {
            NamedParameters::apply($this->_handlerMap[0], $event);
        }
    }

    protected function getPayloadType($event) {
        return ArrayHelper::getValue(
            $event->payload,
            $this->getPayloadKey()
        );
    }

    /**
     * @return string
     */
    protected function getPayloadKey() {
        return 'type';
    }
}
