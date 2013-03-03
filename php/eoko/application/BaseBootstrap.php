<?php

namespace eoko\application;

use Eoze\Session\SaveHandler\ObservableDbTableGateway;
use Eoze\Session\SaveHandler\ObservableInterface;
use Zend\Db\TableGateway\TableGateway as DbTableGateway;
use Zend\Session\Config\SessionConfig;
use Zend\Session\Container as SessionContainer;
use Zend\Session\SaveHandler\DbTableGatewayOptions;
use Zend\Session\SessionManager;
use Zend\Session\Validator as SessionValidator;
use Zend\Session\ManagerInterface as SessionManagerInterface;
use eoko\Authentification\UserSession;
use eoko\cqlix\ExtendedModel;
use eoko\database\Database;
use eoko\log\Logger;
use eoko\module\ModuleManager;
use eoko\config\ConfigManager;
use eoko\php\ClassLoader;
use eoko\config\Application;
use eoko\modules\Kepler\CometEvents;
use eoko\cqlix\Cqlix;

class BaseBootstrap extends Bootstrap {

	public function __invoke() {

		$classLoader = $this->registerClassLoader();

		$sessionManager = $this->initSessionManager();

		$this->initConfigManager($sessionManager);
//		dump(__FILE__ . ':' . __LINE__);

		parent::__invoke();

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

		$classLoader->addIncludePath(array(
			MODEL_PATH, MODEL_PROXY_PATH
		));

		return $classLoader;
	}

	/**
	 * @return SessionManagerInterface
	 */
	protected function initSessionManager() {
		$config = new SessionConfig();
		$config
			->setName('Eoze_Authentication')
			->setRememberMeSeconds(3600*24);

		$sessionManager = new SessionManager($config);

		// Validators
		$validators = $sessionManager->getValidatorChain();
		$validators->attach('session.validate', array(new SessionValidator\HttpUserAgent(), 'isValid'));
		$validators->attach('session.validate', array(new SessionValidator\RemoteAddr(), 'isValid'));

		// --- Save handler
		// TableGateway
		$dbAdapter = Database::getDefaultDbAdapter();
		$tableGateway = new DbTableGateway('zf_sessions', $dbAdapter);
		// Save Handler
		$saveHandlerOptions = new DbTableGatewayOptions();
		$saveHandler = new ObservableDbTableGateway($tableGateway, $saveHandlerOptions);;
		// Assign
		$sessionManager->setSaveHandler($saveHandler);

		// --- Register service
		Application::setDefaultSessionManager($sessionManager);

		// --- Configure container
		SessionContainer::setDefaultManager($sessionManager);

		return $sessionManager;
	}

	protected function initConfigManager(/** @noinspection PhpUnusedParameterInspection */
			SessionManagerInterface $sessionManager) {
		ConfigManager::init();
	}

	protected function initUserSession(/** @noinspection PhpUnusedParameterInspection */
			SessionManagerInterface $sessionManager) {
		return Application::getInstance()->getUserSession();
	}

	/**
	 *
	 * Requires: paths, config
	 *
	 * @param SessionManagerInterface|SessionManager $sessionManager
	 * @param UserSession $userSession
	 */
	protected function initCometEvents(SessionManagerInterface $sessionManager, UserSession $userSession) {
		if (ConfigManager::get('eoko/routing', 'comet', false)) {
			$comet = new CometEvents(MY_EOZE_PATH, $sessionManager->getId());
			ExtendedModel::setDefaultCometEvents($comet);

			$userSession
				->onLogin(function() use($comet, $userSession) {
					$comet->start($userSession->getUserId());
				});

			$saveHandler = $sessionManager->getSaveHandler();

			if ($saveHandler instanceof ObservableInterface) {
				$saveHandler->getEventManager()->attach(
					ObservableDbTableGateway::EVENT_DESTROY,
					function() use($comet) {
						$comet->destroy();
					}
				);
			} else {
				Logger::get($this)->warn('Session manager must be observable or comet files will never be cleaned!');
			}
		}
	}
}
