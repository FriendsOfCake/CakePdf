<?php
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\Folder;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Routing\DispatcherFactory;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__) . DS);
define('APP_DIR', 'test_app');
define('WEBROOT_DIR', 'webroot');
define('CONFIG', ROOT . DS . 'config' . DS);
define('APP', ROOT . DS . 'tests' . DS . APP_DIR . DS);
define('WWW_ROOT', ROOT . DS . WEBROOT_DIR . DS);
define('TESTS', ROOT . DS . 'tests' . DS . 'Test' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);

require ROOT . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'App',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => 'src',
    'webroot' => 'webroot',
    'www_root' => APP . 'webroot',
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => [
        'plugins' => [APP . 'Plugin' . DS],
        'templates' => [APP . 'Template' . DS]
    ]
]);
Configure::write('Session', [
    'defaults' => 'php'
]);

$TMP = new Folder(TMP);
$TMP->create(TMP . 'cache/persistent', 0777);

$cache = [
    'default' => [
        'engine' => 'Null'
    ],
    '_cake_core_' => [
        'className' => 'Null',
        'prefix' => 'cakepdf_myapp_cake_core_',
        'path' => CACHE . 'persistent/',
        'serialize' => 'File',
        'duration' => '+10 seconds'
    ]
];

Cache::config($cache);

// Ensure default test connection is defined
if (!getenv('db_class')) {
    putenv('db_class=Cake\Database\Driver\Sqlite');
    putenv('db_dsn=sqlite::memory:');
}

ConnectionManager::config('test', [
    'className' => 'Cake\Database\Connection',
    'driver' => getenv('db_class'),
    'dsn' => getenv('db_dsn'),
    'database' => getenv('db_database'),
    'login' => getenv('db_login'),
    'password' => getenv('db_password'),
    'timezone' => 'UTC'
]);

Plugin::load('CakePdf', ['path' => ROOT, 'routes' => true]);
DispatcherFactory::add('ControllerFactory');
