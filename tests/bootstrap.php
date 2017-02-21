<?php

use aea\flux\Dispatcher;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_APP_BASE_PATH') or define('YII_APP_BASE_PATH', __DIR__);

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace("\\", "/", $class) . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
    return false;
});
Yii::$container->setSingleton(Dispatcher::class);

new yii\console\Application(require(__DIR__ . '/config.php'));
