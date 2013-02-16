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
use eoko\config\Application;
use eoko\modules\Kepler\CometEvents;

date_default_timezone_set('Europe/Paris');
bcscale(2);

// Directories
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT')) define('ROOT', realpath(__DIR__ . DS . '..' . DS . '..') . DS);

if (!defined('SITE_BASE_URL')) define('SITE_BASE_URL', 'http://' .
		(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost')
		. rtrim(dirname($_SERVER['PHP_SELF']) , '/\\') . '/' );

if (file_exists($filename = ROOT . '../config.php')
		|| file_exists($filename = ROOT . 'config.php')) {
	require_once $filename;
}

// Config
if (!defined('APP_NAME')) define('APP_NAME', 'opence');
if (!defined('APP_TITLE')) define('APP_TITLE', 'Open.CE');

if (!defined('WEB_DIR')) define('WEB_DIR', 'public');
if (!defined('WEB_DIR_URL')) define('WEB_DIR_URL', SITE_BASE_URL . WEB_DIR . '/');
if (!defined('WEB_DIR_PATH')) define('WEB_DIR_PATH', ROOT . WEB_DIR . DS);

if (!defined('USE_CONTROLLER_CACHE')) define('USE_CONTROLLER_CACHE', false);

if (!defined('BIN_PATH')) define('BIN_PATH', ROOT . 'bin' . DS);

if (!defined('LIB_DIR')) define('LIB_DIR', null);
if (!defined('LIB_PATH')) define('LIB_PATH', ROOT . (LIB_DIR ? LIB_DIR . DS : null));

if (!defined('EOZE_DIR')) define('EOZE_DIR', 'eoze');
if (!defined('EOZE_PATH')) define('EOZE_PATH', str_replace(DS . DS, DS, LIB_PATH . EOZE_DIR . DS));
if (!defined('EOZE_BASE_URL')) define('EOZE_BASE_URL', preg_replace('@([^:/])//@', '$1/', SITE_BASE_URL . '/' . EOZE_DIR) . '/');

if (!defined('PHP_DIR')) define('PHP_DIR', 'php');
if (!defined('PHP_PATH')) define('PHP_PATH', EOZE_PATH . PHP_DIR . DS);
if (!defined('APP_PATH')) define('APP_PATH', ROOT . 'app' . DS);
if (!defined('APP_PHP_PATH')) define('APP_PHP_PATH', APP_PATH . PHP_DIR . DS);

// Home directory
if (!defined('MY_EOZE_PATH')) define('MY_EOZE_PATH', ROOT . '.eoze' . DS);

if (!defined('HELP_PATH')) define('HELP_PATH', ROOT . 'help' . DS);
if (!defined('LIBS_PATH')) define('LIBS_PATH', PHP_PATH . 'lib' . DS);
if (!defined('DATABASE_DUMP_PATH')) define('DATABASE_DUMP_PATH', ROOT . 'mysql' . DS);

if (!defined('MODULES_DIR')) define('MODULES_DIR', 'modules');
if (!defined('MODULES_PATH')) define('MODULES_PATH', ROOT . MODULES_DIR . DS);

if (!defined('IMAGES_PATH')) define('IMAGES_PATH', ROOT . 'images' . DS);

if (!defined('HTML_TPL_PATH')) define('HTML_TPL_PATH', ROOT . 'tpl' . DS);
if (!defined('CSS_BASE_URL')) define('CSS_BASE_URL', 'css/');

if (!defined('CSS_PATH')) define('CSS_PATH', EOZE_PATH . 'css' . DS);
if (!defined('CSS_URL')) define('CSS_URL', EOZE_BASE_URL . 'css/');
if (!defined('JS_PATH')) define('JS_PATH', EOZE_PATH . 'js' . DS);
if (!defined('JS_URL')) define('JS_URL', EOZE_BASE_URL . 'js/');

if (!defined('LIB_BASE_URL')) define('LIB_BASE_URL', SITE_BASE_URL . LIB_DIR . '/');
if (!defined('LIB_IMAGES_BASE_URL')) define('LIB_IMAGES_BASE_URL', LIB_BASE_URL . 'images' . '/');
if (!defined('LIB_PHP_BASE_URL')) define('LIB_PHP_BASE_URL', LIB_BASE_URL . PHP_DIR . '/');

if (!defined('MEDIA_PATH')) define('MEDIA_PATH', ROOT . 'medias' . DS);
if (!defined('MEDIA_BASE_URL')) define('MEDIA_BASE_URL', SITE_BASE_URL . 'medias/');
if (!defined('EXPORTS_PATH')) define('EXPORTS_PATH', MEDIA_PATH . 'exports' . DS);
if (!defined('EXPORTS_BASE_URL')) define('EXPORTS_BASE_URL', MEDIA_BASE_URL . 'exports/');

if (!defined('BACKUPS_PATH')) define('BACKUPS_PATH', ROOT . 'backup' . DS);
if (!defined('BACKUPS_BASE_URL')) define('BACKUPS_BASE_URL', SITE_BASE_URL . 'backup/');

if (!defined('MODULES_BASE_URL')) define('MODULES_BASE_URL', SITE_BASE_URL . MODULES_DIR . '/');
if (defined('APP_MODULES_DIR')) {
	if (!defined('APP_MODULES_BASE_URL')) {
		define('APP_MODULES_BASE_URL', SITE_BASE_URL . APP_MODULES_DIR . '/');
	}
}

if (!defined('MODULES_NAMESPACE')) define('MODULES_NAMESPACE', 'eoko\\modules\\');

// Models
if (!defined('MODEL_PATH')) define('MODEL_PATH', ROOT . 'models' . DS);
if (!defined('MODEL_BASE_PATH')) define('MODEL_BASE_PATH', ROOT . 'models' . DS . 'base' . DS);
if (!defined('MODEL_PROXY_PATH')) define('MODEL_PROXY_PATH', ROOT . 'models' . DS . 'proxy' . DS);

function createEozeDirIf($path) {
	if (!file_exists($path)) {
		@mkdir($path);
	}
}
createEozeDirIf(MY_EOZE_PATH);
if (!file_exists(MY_EOZE_PATH . '.htaccess')) {
	file_put_contents(MY_EOZE_PATH . '.htaccess', 'DENY FROM ALL' . PHP_EOL);
}

// web dir
createEozeDirIf(WEB_DIR_PATH);

require_once PHP_PATH . '_/functions.php';
require_once PHP_PATH . '_/dump.php';

// Vendors
if (!defined('EOZE_AS_LIB') || !EOZE_AS_LIB) {
	require_once EOZE_PATH . 'vendor' . DS . 'autoload.php';
}

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
require_once PHP_PATH . '_/router_exceptions.inc.php';
require_once PHP_PATH . 'eoko/php/ErrorException.php';
//... Language
//require_once PHP_PATH . 'Language.php'; // declares the function lang in the global namespace
includeFromNamespace('eoko\\i18n', 'Language.php');
eoko\i18n\Language::importFunctions(__NAMESPACE__);
//require_once PHP_PATH . 'Language.php'; // declares the function lang in the global namespace
//... Logger
require_once PHP_PATH . '_/Logger.php';

// TODO: make that ondemand compliant!
require_once PHP_PATH . '_/ModelRelation.php';
require_once PHP_PATH . '_/ModelField.php';
require_once PHP_PATH . '_/ModelFieldBase.php';
require_once PHP_PATH . '_/ModelRelationInfo.php';

// --- Exception handler ---
if ((!isset($test) || !$test) && (!isset($is_script) || !$is_script)
		&& !(defined('EOZE_AS_LIB') && EOZE_AS_LIB)) {
	require_once (PHP_PATH . '_/ExceptionHandler.php');
}


// === Bootstrap ===

// ClassLoader not plugged yet!
require_once __DIR__ . '/eoko/application/Bootstrap.php';
require_once __DIR__ . '/eoko/application/BaseBootstrap.php';

if (function_exists('configure_application')) {
	$bootstrap = configure_application();
}

if (!isset($bootstrap)) {
	$bootstrap = new \eoko\application\BaseBootstrap();
}

$bootstrap();


// == Logging ==

if (ConfigManager::get('eoko/log/appenders/Output') || isset($_GET['olog'])) {
	Logger::addAppender(new LoggerOutputAppender());
}

if (ConfigManager::get('eoko/log/appenders/FirePHP')) {
	Logger::addAppender(new LoggerFirePHPAppender());
}

if (null !== $level = ConfigManager::get('eoko/log', 'level', null)) {
	Logger::getLogger()->setLevel(constant("Logger::$level"));
}

if (($levels = ConfigManager::get('eoko/log/levels'))) {
	Logger::setLevels($levels);
}

if (!defined('ADD_LOG_APPENDERS') || ADD_LOG_APPENDERS) {
	if (ConfigManager::get('eoko/log/appenders/File')) {
		Logger::addAppender(new LoggerFileAppender());
	}
}


// == File types ==

\eoko\util\file\FileTypes::getInstance();


// == Routing ==

if ((!isset($test) || !$test)
		&& (!isset($is_script) || !$is_script)
		&& (!defined('UNIT_TEST') || !UNIT_TEST)
		&& (!defined('EOZE_AS_LIB') || !EOZE_AS_LIB)
//		&& !interface_exists('PHPUnit_Framework_Test', false)
) {

	Router::getInstance()->route();
}
