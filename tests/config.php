<?php
/**
 * Test Application Configuration
 */
use yii\base\Event;
use yii\db\Connection;
use yii\helpers\FileHelper;

return [
    'id' => 'yii-flux-test',
    'basePath' => __DIR__,
    'components' => [
        'cache' => 'yii\caching\FileCache',
        'db' => [
            'class'=>Connection::class,
            'dsn' => "sqlite:@runtime/db.sqlite",
        ],
    ],
];
