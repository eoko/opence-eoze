<?php
/**
 * Initiatilization script.
 * <ul>
 * <li>Initialize PATH and other constants
 * <li>Setup class autoloader
 * <li>Configure Logger appenders
 * </ul>
 * @author Éric Ortéga <eric@mail.com>
 */

use eoko\config\ConfigManager;

$directAccess = false;
date_default_timezone_set('Europe/London');

function defineIf($name, $value) {
	if (!defined($name)) define($name, $value);
}

// Directories
defineIf('DS', DIRECTORY_SEPARATOR);
defineIf('ROOT', realpath(dirname(__FILE__) . DS . '..' . DS . '..') . DS);

defineIf('SITE_BASE_URL', 'http://' .
		(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')
		. rtrim(dirname($_SERVER['PHP_SELF']) , '/\\') . '/' );

if (file_exists($filename = ROOT . '../config.php')
		|| file_exists($filename = ROOT . 'config.php')) {
	require_once $filename;
}

// Config
defineIf('APP_NAME', 'Opence');
defineIf('APP_TITLE', 'OpenCE!');

// removed on 2/26/11 2:27 PM
//$phpSubDirs = array(
//	'GridModule'
//);

// deprecated on 2/26/11 2:10 PM
//if (!isset($dbConfig)) {
//	$dbConfig = array(
//		'user' => 'root'
//		,'host' => 'localhost'
//		,'database' => 'oce_dev'
//		,'password' => 'root'
//	);
//}

defineIf('USE_CONTROLLER_CACHE', false);

defineIf('BIN_PATH', ROOT . 'bin' . DS);

defineIf('LIB_DIR', null);
defineIf('LIB_PATH', ROOT . (LIB_DIR ? LIB_DIR . DS : null));

defineIf('EOZE_DIR', 'eoze');
defineIf('EOZE_PATH', str_replace(DS . DS, DS, LIB_PATH . EOZE_DIR . DS));
defineIf('EOZE_CONFIG_PATH', EOZE_PATH . 'config' . DS);
defineIf('EOZE_BASE_URL', preg_replace('@([^:/])//@', '$1/', SITE_BASE_URL . '/' . EOZE_DIR) . '/');

defineIf('PHP_DIR', 'php');
defineIf('PHP_PATH', EOZE_PATH . PHP_DIR . DS);
defineIf('APP_PATH', ROOT . 'app' . DS);
defineIf('APP_PHP_PATH', APP_PATH . PHP_DIR . DS);

defineIf('CACHE_PATH', EOZE_PATH . 'cache' . DS);
defineIf('LOG_PATH', EOZE_PATH . 'log' . DS);
defineIf('HELP_PATH', ROOT . 'help' . DS);
defineIf('LIBS_PATH', PHP_PATH . 'lib' . DS);
defineIf('DATABASE_DUMP_PATH', ROOT . 'mysql' . DS);

defineIf('MODULES_DIR', 'modules');
defineIf('MODULES_PATH', ROOT . MODULES_DIR . DS);

defineIf('IMAGES_PATH', ROOT . 'images' . DS);

defineIf('HTML_TPL_PATH', ROOT . 'tpl' . DS);
defineIf('CSS_BASE_URL', 'css/');

defineIf('CSS_PATH', EOZE_PATH . 'css' . DS);
defineIf('CSS_URL', EOZE_BASE_URL . 'css/');
defineIf('JS_PATH', EOZE_PATH . 'js' . DS);
defineIf('JS_URL', EOZE_BASE_URL . 'js/');

//defineIf('MODEL_PATH', ROOT . 'models' . DS);
//defineIf('MODEL_BASE_PATH', MODEL_PATH . 'base' . DS);
//defineIf('MODEL_PROXY_PATH', MODEL_PATH . 'proxy' . DS);
//defineIf('MODEL_QUERY_PATH', MODEL_PATH . 'query' . DS);

defineIf('LIB_BASE_URL', SITE_BASE_URL . LIB_DIR . '/');
defineIf('LIB_IMAGES_BASE_URL', LIB_BASE_URL . 'images' . '/');
defineIf('LIB_PHP_BASE_URL', LIB_BASE_URL . PHP_DIR . '/');

defineIf('MEDIA_PATH', ROOT . 'medias' . DS);
defineIf('MEDIA_BASE_URL', SITE_BASE_URL . 'medias/');
defineIf('EXPORTS_PATH', MEDIA_PATH . 'exports' . DS);
defineIf('EXPORTS_BASE_URL', MEDIA_BASE_URL . 'exports/');

defineIf('BACKUPS_PATH', ROOT . 'backup' . DS);
defineIf('BACKUPS_BASE_URL', SITE_BASE_URL . 'backup/');

defineIf('CONFIG_PATH', ROOT . 'config' . DS);

defineIf('MODULES_BASE_URL', SITE_BASE_URL . MODULES_DIR . '/');
if (defined('APP_MODULES_DIR')) {
	defineIf('APP_MODULES_BASE_URL', SITE_BASE_URL . APP_MODULES_DIR . '/');
}

defineIf('MODULES_NAMESPACE', 'eoko\\modules\\');
defineIf('APP_MODULES_NAMESPACE', MODULES_NAMESPACE);

require_once PHP_PATH . 'functions.php';
require_once PHP_PATH . 'dump.php';

if (!function_exists('lcfirst')) {
	function lcfirst($str) {
		return strtolower($str[0]) . substr($str, 1);
	}
}

function pathFromNamespace($ns, $path) {
	return PHP_PATH . str_replace('\\', DS, $ns) . DS . $path;
}

function includeFromNamespace($ns, $filename, $require = true, $once = true) {
	$filename = pathFromNamespace($ns, $filename);
	if ($require) {
		if ($once) require_once $filename;
		else require $filename;
	} else {
		if ($once) include_once $filename;
		else include $filename;
	}
}

// Includes which must be loaded before the class autoloader is set...

//... Exceptions bases
require_once PHP_PATH . 'router_exceptions.inc.php';
//... Language
//require_once PHP_PATH . 'Language.class.php'; // declares the function lang in the global namespace
includeFromNamespace('eoko\\i18n', 'Language.class.php');
eoko\i18n\Language::importFunctions(__NAMESPACE__);
//require_once PHP_PATH . 'Language.class.php'; // declares the function lang in the global namespace
//... Logger
require_once PHP_PATH . 'Logger.class.php';

// TODO: make that ondemand compliant!
require_once PHP_PATH . 'ModelRelation.class.php';
require_once PHP_PATH . 'ModelField.class.php';
require_once PHP_PATH . 'ModelRelationInfo.class.php';

//require_once PHP_PATH . str_replace('\\', DS, 'eoko/cqlix/Relation/Relation') . '.classes.php';
//require_once PHP_PATH . str_replace('\\', DS, 'eoko/cqlix/Relation/RelationInfo') . '.classes.php';

// --- Exception handler ---
if ((!isset($test) || !$test) && (!isset($is_script) || !$is_script)) {
//	require_once (PHP_PATH . 'ExceptionHandler.class.php');
}

// --- Class loader --
// Autoload for helpers in /inc
require_once PHP_PATH . 'eoko' . DS . 'php' . DS . 'ClassLoader.class.php';
$classLoader = eoko\php\ClassLoader::register();

$classLoader->addIncludePath(array(
	PHP_PATH,
	APP_PHP_PATH,
));

if (USE_CONTROLLER_CACHE) $classLoader->addIncludePath(CACHE_PATH . 'php');

// removed on 2/26/11 2:27 PM
//foreach ($phpSubDirs as $dir) {
//	$classLoader->addIncludePath(PHP_PATH . $dir . DS);
//	$classLoader->addIncludePath(APP_PHP_PATH . $dir . DS);
//}


// === Configure Plugins ===

eoko\plugin\PluginManager::init();


// === Configure application ===

if (function_exists('configure_application')) {
	$bootstrap = configure_application();
}
if (!isset($bootstrap)) $bootstrap = new \eoko\application\BaseBootstrap();
$bootstrap();


// Load directories configuration (from eoze\application)
function loadAppConfig($classLoader) {
	$appConfig = ConfigManager::get('eoze\application');
	if (isset($appConfig['directories'])) {
		$dc = $appConfig['directories'];
		if (isset($dc['models'])) {
			$m = $dc['models'];
			if (substr($m, -1) !== DS) $m .= DS;
			define('MODEL_PATH', ROOT . $m);
			define('MODEL_BASE_PATH', MODEL_PATH . 'base' . DS);
			define('MODEL_PROXY_PATH', MODEL_PATH . 'proxy' . DS);
			define('MODEL_QUERY_PATH', MODEL_PATH . 'query' . DS);

			$classLoader->addIncludePath(array(
				MODEL_PATH, MODEL_PROXY_PATH
			));
		}
	}
}

loadAppConfig($classLoader);

// Finally, start the session (must be done after the autoloader has been set,
// so that object stored in session (notably: UserSession) can be instantiated)
session_start();

require_once 'debug.inc.php';

// debug
//Logger::addAppender(new LoggerOutputAppender(true));

if (!defined('ADD_LOG_APPENDERS') || ADD_LOG_APPENDERS) {
	Logger::addAppender(new LoggerFileAppender());
}

\eoko\util\file\FileTypes::getInstance();

if ((!isset($test) || !$test)
		&& (!isset($is_script) || !$is_script)
		&& (!defined('UNIT_TEST') || !UNIT_TEST)
//		&& !interface_exists('PHPUnit_Framework_Test', false)
) {

	Router::getInstance()->route();
}
