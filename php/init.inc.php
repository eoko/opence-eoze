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

use eoko\php\generator\ClassGeneratorManager;
use eoko\config\ConfigManager;
use eoko\module\ModuleManager;

$directAccess = false;
date_default_timezone_set('Europe/London');

function defineIf($name, $value) {
	if (!defined($name)) define($name, $value);
}

// Directories
defineIf('DS', DIRECTORY_SEPARATOR);
//defineIf('ROOT', realpath(dirname(__FILE__) . DS . '..') . DS);
defineIf('ROOT', realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . DS);

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

$phpSubDirs = array(
	'GridModule'
);

if (!isset($dbConfig)) {
	$dbConfig = array(
		'user' => 'root'
		,'host' => 'localhost'
		,'database' => 'oce_dev'
		,'password' => 'root'
	);
}

defineIf('USE_CONTROLLER_CACHE', false);

defineIf('BIN_PATH', ROOT . 'bin' . DS);

defineIf('LIB_DIR', null);
defineIf('LIB_PATH', ROOT . LIB_DIR . DS);

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

//defineIf('JSCORE_BASE_URL', 'js/');
//defineIf('JSCORE_BASE_PATH', ROOT . 'js' . DS);
//defineIf('EXTJS_BASE_URL', JSCORE_BASE_URL . 'ext/');
defineIf('CSS_PATH', EOZE_PATH . 'css' . DS);
defineIf('CSS_URL', EOZE_BASE_URL . 'css/');
defineIf('JS_PATH', EOZE_PATH . 'js' . DS);
defineIf('JS_URL', EOZE_BASE_URL . 'js/');

//if (!defined(APP_JS_PATH) && is_dir($dir = ROOT . 'js')) {
//	define('APP_JS_PATH', $dir . DS);
//	define('APP_JS_URL', SITE_BASE_URL . 'js/');
//}
//if (!defined(APP_CSS_PATH) && is_dir($dir = ROOT . 'css')) {
//	define('APP_CSS_PATH', $dir . DS);
//	define('APP_CSS_URL', SITE_BASE_URL . 'css/');
//}

defineIf('MODEL_PATH', ROOT . 'models' . DS);
defineIf('MODEL_BASE_PATH', MODEL_PATH . 'base' . DS);
defineIf('MODEL_PROXY_PATH', MODEL_PATH . 'proxy' . DS);
defineIf('MODEL_QUERY_PATH', MODEL_PATH . 'query' . DS);

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
//	defineIf('APP_MODULES_BASE_URL', APP_MODULES_DIR . '/');
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
if ((!isset($test) || !$test) && (!isset($is_script) || !$is_script)) require_once (PHP_PATH . 'ExceptionHandler.class.php');

// --- Class loader --
// Autoload for helpers in /inc

$includePaths = array(
	PHP_PATH,
	APP_PHP_PATH,
	MODEL_PATH,
	MODEL_PROXY_PATH
);

if (USE_CONTROLLER_CACHE) $includePaths[CACHE_PATH . 'php'];

//REM ModuleManager::addIncludePaths($includePaths);

foreach ($phpSubDirs as $dir) {
	$includePaths[] = PHP_PATH . $dir . DS;
	$includePaths[] = APP_PHP_PATH . $dir . DS;
}

function __autoload($class) {
	if (!tryAutoLoad($class)) {
		tryAutoLoad($class, '');
	}
}
function tryAutoLoad($class, $suffix = '.class') {

	$classPath = str_replace('\\', DS, $class);

	global $includePaths;
	foreach ($includePaths as $path) {
		if (file_exists($filename = "$path$classPath$suffix.php")) {
			require_once $filename;
			return true;
		}
	}

//	if (substr($class, 0, strlen(MODULES_NAMESPACE)) === MODULES_NAMESPACE) {
//		$classPath = substr($class, strlen(MODULES_NAMESPACE));
//		$classPath = str_replace('\\', DS, $classPath);
//		$classPath = MODULES_PATH . $classPath;
//		if (file_exists($filename = "$classPath$suffix.php")) {
//			require_once $filename;
//			return true;
//		}
//	}
	if (ModuleManager::autoLoad($class, $suffix)) return true;

	if (ClassGeneratorManager::generate($class)) {
		return true;
	}

	if (2 === count($parts = explode('_', $classPath, 2))) {
		$classPath = $parts[0];
		foreach ($includePaths as $path) {
			if (file_exists($filename = "$path$classPath$suffix.php")) {
				require_once $filename;
				return true;
			}
		}
	}
	if (substr($classPath, -5) === 'Query') {
		if (file_exists($filename = MODEL_QUERY_PATH . "$classPath$suffix.php")) {
			require_once $filename;
			return true;
		}
	}
	if (substr($classPath, -5) === 'Proxy') {
		if (file_exists($filename = MODEL_PROXY_PATH . "$classPath$suffix.php")) {
			require_once $filename;
			return true;
		}
	}

	return false;
}


// === Configure Plugins ===

eoko\plugin\PluginManager::init();


// === Configure application ===

if (function_exists('configure_application')) {
	$bootstrap = configure_application();
}
if (!isset($bootstrap)) $bootstrap = new \eoko\application\BaseBootstrap();
$bootstrap();

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

if ((!isset($test) || !$test) && (!isset($is_script) || !$is_script)) Router::getInstance()->route();
