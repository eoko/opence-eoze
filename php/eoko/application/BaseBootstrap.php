<?php

namespace eoko\application;

use eoko\module\ModuleManager;
use eoko\config\ConfigManager;
use eoko\php\ClassLoader;
use eoko\php\SessionManager;
use eoko\config\Application;
use UserSession;
use eoko\modules\Kepler\CometEvents;
use eoko\cqlix\Cqlix;

class BaseBootstrap extends Bootstrap {

	public function __invoke() {

		$classLoader = $this->registerClassLoader();

		parent::__invoke();

		$this->loadDirectoriesConfiguration($classLoader);

		$sessionManager = $this->initSessionManager();

		$userSession = $this->initUserSession($sessionManager);

		$this->initCometEvents($sessionManager, $userSession);

		Cqlix::init();
	}

	protected function initModulesLocations() {
		// instantiate the module manager, after the config paths have been
		// initialized
		ModuleManager::getInstance();
	}

	protected function registerModuleFactories() {}

	protected function initGlobalEvents() {}

	protected function registerClassLoader() {
		/** @noinspection PhpIncludeInspection */
		require_once PHP_PATH . 'eoko' . DS . 'php' . DS . 'ClassLoader.php';

		$classLoader = ClassLoader::register();

		$classLoader->addIncludePath(array(
			PHP_PATH,
			APP_PHP_PATH,
		));

		foreach (explode(':', get_include_path()) as $path) {
			if ($path !== '.') {
				$classLoader->addIncludePath($path);
			}
		}

		if (USE_CONTROLLER_CACHE) {
			$classLoader->addIncludePath(CACHE_PATH . 'php');
		}

		return $classLoader;
	}

	protected function loadDirectoriesConfiguration($classLoader) {

		$appConfig = ConfigManager::get('eoze\application');

		if (isset($appConfig['directories'])) {

			$dc = $appConfig['directories'];

			if (isset($dc['models'])) {

				$m = $dc['models'];

				if (substr($m, -1) !== DS) {
					$m .= DS;
				}

				define('MODEL_PATH', ROOT . $m);
				define('MODEL_BASE_PATH', MODEL_PATH . 'base' . DS);
				define('MODEL_PROXY_PATH', MODEL_PATH . 'proxy' . DS);

				$classLoader->addIncludePath(array(
					MODEL_PATH, MODEL_PROXY_PATH
				));
			}
		}
	}

	/**
	 * @return SessionManager
	 */
	protected function initSessionManager() {
		$sessionManager = new SessionManager();
		Application::setDefaultSessionManager($sessionManager);
		return $sessionManager;
	}

	/**
	 * @param SessionManager $sessionManager
	 * @return UserSession
	 */
	protected function initUserSession(SessionManager $sessionManager) {
		return new \eoko\security\UserSessionHandler\LegacyWrapper($sessionManager);
	}

	/**
	 * @param SessionManager $sessionManager
	 * @param UserSession $userSession
	 */
	protected function initCometEvents(SessionManager $sessionManager, $userSession) {
		if (ConfigManager::get('eoko/routing', 'comet', false)) {
			CometEvents::start(TMP_PATH, $userSession, $sessionManager);
		}
	}
}
