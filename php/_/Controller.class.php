<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

throw new Exception('DEPRECATED');

use eoko\util\HttpResponse;

if (!isset($GLOBALS['directAccess'])) { header('HTTP/1.0 404 Not Found'); exit('Not found'); }

class Controller {

	private $action;
	public $controllerName;
	/** @var Request */
	protected $request;

	/** @var ControllerEngine */
	public $engine;

	public function __construct($controllerName, $path, $baseUrl, $action, Request &$request) {
		$this->controllerName = $controllerName;
		$this->action = $action;
		$this->request = $request;
		$this->configure();

		$this->engine = new ControllerEngine($this, $path, $baseUrl);
	}
	
	public function execute($action) {
		return $this->$action();
	}
	
	/**
	 * Returns the Controller's name.
	 * @return string
	 */
	public function __toString() {
		return $this->controllerName;
	}

	// for debug
	public function __call($name, $params) {
		$class = get_class($this);
		throw new UnsupportedActionException($class, $name,
				"Unsupported action $class::$name()");
	}

	protected function configure() {}

	/** @return Boolean */
	public function isAction($action) {
		return $this->action === $action;
	}

	/** @return String */
	public function getAction() {
		return $this->action;
	}

	/** @return String */
	public function getModule() {
		return $this->controllerName;
	}

	protected function getLogger() {
		return Logger::getLogger($this->controllerName);
   	}

	protected function answerRaw($responseData) {
		ExtJSResponse::answerRaw($responseData);
	}
	protected function answer($data = null, $die = true, $return = false) {

		if ($data !== null) {
			ExtJSResponse::put('data', $data);
		}

		Logger::get($this)->warn('deprecated');

		if ($return) ob_start();
		ExtJSResponse::answer($die);
		if ($return) return ob_get_clean();
	}
	protected function answerEx($data = null, $message_s = null, $other = null, 
			$die = true, $return = false) {
		if ($other === null) $other = array();
		ExtJSResponse::successEx($data, $message_s, $other, $die, $return);
	}

	protected function getRelativePath($file) {
		return $this->engine->getRelativePath($file);
	}

	protected function hasPage($pageName) {
		return $this->hasJS($pageName) || $this->hasHtml($pageName);
	}

	protected function answerPage($pageName, $die = true) {

		if (!$this->answerJS($pageName, false)
				&& !$this->answerHtml($pageName, false)) {

			if ($die) {
				$filename = $this->getRelativePath($pageName);
				Logger::getLogger('router')->error("Page file does not exist: {}", $filename . '.{js|html.php}');
				header("HTTP/1.0 404 Not Found");
			}
		}

		if ($die) die();
	}

	protected function hasJS($jsPageName) {
		$filename = $this->getRelativePath($jsPageName) . '.js';
		return file_exists($filename);
	}

	/**
	 *
	 * @param boolean $die
	 * @return Template
	 */
	protected function getIndexModuleTemplate($die = true) {
		if (null === ($tpl = $this->getJSTemplate('index' . '.mod'))
				&& (null ===  $tpl = $this->getJSTemplate($this->controllerName . '.mod'))) {

			Logger::getLogger($this)->info('Module index file does not exist for: {} -- trying automatic generation',
					$this->controllerName);

			if (null === $tpl = ModuleManager::createModule($this->controllerName)) {
				Logger::getLogger($this)->error("JS index file not found");
				if ($die) $this->answer404($die);
			} else {
				Logger::getLogger($this)->info('Module index has been generated: {}',
						$this->controllerName);
				return $tpl;
			}
		} else {
			return $tpl;
		}
	}

	protected function getJSTemplate($jsPageName) {
		if (
			(null !== $filename = $this->getRelativePath($jsPageName) . '.js')
			&& file_exists($filename)
		) {
			return Template::create($filename, $this);
		} else {
			return null;
		}
	}
	
//REM	protected function getPageTemplate($pageName) {
//
//		if (file_exists($filename = $this->getRelativePath($pageName) . '.html')) {
//			return Template::create($filename, $this);
//		} else if (file_exists($filename = $this->getRelativePath($pageName) . '.html.php')) {
//			return Template::create($filename, $this);
//		} else if (file_exists($filename = $this->getRelativePath($pageName) . '.php')) {
//			return Template::create($filename, $this);
//		} else {
//			return null;
//		}
//	}

	protected function answerJS($jsPageName, $die = true) {

		if (null !== $tpl = $this->getJSTemplate($jsPageName)) {
			header('Content-type: application/x-javascript');
			$tpl->render();
		} else {
			if ($die) {
				header("HTTP/1.0 404 Not Found");
				die;
			}
			return false;
		}

		if ($die) die();

		return true;
	}

	protected function hasHtml($htmlPageName) {
		$filename = $this->getRelativePath($htmlPageName) . '.html.php';
		return file_exists($filename);
	}
	
	protected function getHtmlTemplateFilename($name) {
		if (file_exists($filename = $this->getRelativePath("$name.html.php"))) {
			return $filename;
		} else {
			return null;
		}
	}
	
	protected function requireHtmlTemplateFilename($name) {
		if (null !== $filename = $this->getHtmlTemplateFilename($name)) {
			return $filename;
		} else {
			throw new IllegalStateException(
				'Missing html template: ' . get_class($this) . "::$name"
			);
		}
	}

	/**
	 *
	 * @param string $name
	 * @return TemplateHtml
	 */
	protected function createHtmlTemplate($name) {
		return TemplateHtml::create(
			$this->requireHtmlTemplateFilename($name),
			$this
		);
	}
	
	protected function createHtmlRootTemplate($name) {
		return TemplateHtml::createRoot(
			$this->requireHtmlTemplateFilename($name),
			$this
		);
	}

	/**
	 *
	 * @param string $filename
	 * @return Template
	 */
	protected function createTemplate($filename) {
		if (file_exists($filename = $this->getRelativePath($filename))) {
			return Template::create($filename, $this);
		} else {
			return null;
		}
	}

	protected function answerHtml($htmlPageName, $die = true) {

		if (null !== $tpl = $this->createHtmlTemplate($htmlPageName)) {
			$tpl->render();
		} else {
			$relativeName = $this->getRelativePath($htmlPageName);

			if (file_exists($file = $relativeName) || file_exists($file = "$relativeName.html")) {
				include $file;
			} else {
				if ($die) {
					Logger::getLogger('router')->error("Page file does not exist: {}", $htmlPageName);
					header("HTTP/1.0 404 Not Found");
				}
				return false;
			}
		}

		if ($die) die();

		return true;
	}

	/**
	 * Default action get_page.
	 */
	public function get_page() {
		if (false !== ($key = $this->request->hasAny(array('page', 'file'), true))) {
			// will throw 404 if page.js or page.html.php cannot be found
			$this->answerPage($this->request->getRaw($key, true));
		} else {
			$this->answer404();
		}
	}

	/**
	 * Default action get_html.
	 */
	public function get_html() {
		ExtJSResponse::disableJsonAnswer();
		if (false !== ($key = $this->request->hasAny(array('name', 'file', 'html'), true))) {
			// will throw 404 if page.js or page.html.php cannot be found
			$this->answerHtml($this->request->getRaw($key, true));
		} else {
			$this->answer404();
		}
	}

	/**
	 * Default action get_js.
	 */
	public function get_js() {
		if (false !== ($key = $this->request->hasAny(array('name', 'class' ,'file'), true))) {
			// will throw 404 if page.js or page.html.php cannot be found
			Logger::getLogger($this)->debug("$key is: " . $this->request->getRaw($key, true));
			$this->answerJS($this->request->getRaw($key, true));
		} else {
			$this->answer404();
		}
	}

	public function get_index_module() {
		header('Content-type: application/x-javascript');
		$this->getIndexModuleTemplate(true)->render();
	}

	public function get_module() {
		if (false !== ($key = $this->request->hasAny(array('name', 'module'), true))) {
			header('Content-type: application/x-javascript');
			// will throw 404 if page.js or page.html.php cannot be found
			$name = $this->request->getRaw($key, true);
			Logger::getLogger($this)->debug("$key is: " . $name);

			if ($name === $this->controllerName || $name === 'index') {
				return $this->get_index_module();
			} else {
				$this->answerJS($name . '.mod');
			}
		} else {
			$this->get_index_module();
		}
	}

	/**
	 * Default index action.
	 */
	public function index() {
		$this->answerPage($this->controllerName);
	}

	public function answer404($die = true) {
		HttpResponse::answer404($die);
	}

	public function beforeAction($action) {
		if ($action !== 'ping_session') {
			UserSession::updateUserLastActivity();
		}
//		DBG: keep session alive forever!!!
//		UserSession::updateUserLastActivity();
	}

	protected function redirectAction($controller, $action = 'index', $request = null) {
		
		if ($controller instanceof Controller) $controller = $controller->controllerName;

		Logger::dbg('Redirecting action to {}::{}', $controller, $action);

		Router::getInstance()->executeAction($controller, $action, $request);
	}

	public function ping_session() {
		if (UserSession::isIdentified()) {
			ExtJSResponse::put('pong', true);
		} else {
			ExtJSResponse::put('pong', false);
			ExtJSResponse::put('text',
				lang(
					'Vous avez été déconnecté suite à une longue période d\'inactivité. '
					. 'Veuillez vous identifier à nouveau pour continuer votre travail.'
				)
			);
		}
		ExtJSResponse::answer();
	}
}