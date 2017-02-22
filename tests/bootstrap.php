<?php

use aea\flux\Dispatcher;

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_APP_BASE_PATH') or define('YII_APP_BASE_PATH', __DIR__);

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@runtime', __DIR__ . '/runtime');

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace("\\", "/", $class) . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
    return false;
});

$app = new yii\console\Application(require(__DIR__ . '/config.php'));

$sql = <<<SQL
/*PRAGMA foreign_keys = TRUE;*/
CREATE TABLE IF NOT EXISTS action (
  uuid      TEXT NOT NULL UNIQUE PRIMARY KEY,
  payload   TEXT NOT NULL,
  timestamp FLOAT NOT NULL
) WITHOUT ROWID;
CREATE TABLE IF NOT EXISTS chunk (
  uuid    TEXT NOT NULL UNIQUE PRIMARY KEY, /*REFERENCES action (uuid),*/
  content TEXT NOT NULL
) WITHOUT ROWID;
SQL;

$app->getDb()->createCommand($sql)->execute();
