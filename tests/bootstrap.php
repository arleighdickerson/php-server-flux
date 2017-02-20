<?php

use flux\Bootstrap;
use yii\caching\Cache;
use yii\helpers\ArrayHelper;

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
Yii::setAlias("@flux", dirname(__DIR__) . '/src');

class MockPersistentCache extends Cache {
    private $_backing = [];

    protected function getValue($key) {
        return $this->_backing[$key];
    }

    protected function setValue($key, $value, $duration) {
        $this->_backing[$key] = $value;
    }

    protected function addValue($key, $value, $duration) {
        if (!isset($this->_backing[$key])) {
            $this->_backing[$key] = $value;
        }
    }

    protected function deleteValue($key) {
        unset($this->_backing[$key]);
    }

    protected function flushValues() {
        $this->_backing = [];
    }
}

(new Bootstrap())->bootstrap(new yii\console\Application(
        ArrayHelper::merge(
            require(__DIR__ . '/config.php'), [
                'components' => [
                    'cache' => 'MockPersistentCache'
                ]
            ]
        )
    )
);
