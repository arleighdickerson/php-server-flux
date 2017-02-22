<?php


namespace aea\flux\replay;


use aea\flux\Dispatcher;
use yii\base\Behavior;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class ActionSink extends Behavior {
    public $db = 'db';

    public function events() {
        return [
            Dispatcher::EVENT_BEFORE_DISPATCH => function ($event) {
                $this->beforeDispatch($event);
            },
            Dispatcher::EVENT_AFTER_DISPATCH => function ($event) {
                $this->afterDispatch($event);
            },
            Dispatcher::EVENT_FAILED_DISPATCH => function ($event) {
                $this->failedDispatch($event);
            },
        ];
    }

    protected function beforeDispatch($event) {
        if (!$event->isReplay) {
            $this->writeEvent($event);
        }
    }

    protected function afterDispatch($event) {
    }

    public function failedDispatch($event) {
        if (!$event->isReplay) {
            $this->removeEvent($event);
        }
    }

    protected function writeEvent($event) {
        /** @var Connection $db */
        $content = ArrayHelper::remove($event->payload, 'content');
        $this->getDb()->createCommand()->insert('action', [
            'uuid' => $event->uuid,
            'payload' => Json::encode($event->payload),
            'timestamp' => $event->timestamp
        ])->execute();
        if ($content !== null) {
            $this->getDb()->createCommand()->insert('chunk', [
                'uuid' => $event->uuid,
                'content' => $content,
            ])->execute();
        }
    }

    protected function removeEvent($event) {
        foreach (['chunk', 'action'] as $table) {
            $this->getDb()
                ->createCommand()
                ->delete($table, ['uuid' => $event->uuid])
                ->execute();
        }
    }

    /**
     * @return Connection
     */
    public function getDb() {
        return Instance::ensure($this->db, Connection::class);
    }
}


