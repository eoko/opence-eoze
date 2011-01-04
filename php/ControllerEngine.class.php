<?php

/**
 * Handle internal operations for controllers. This class mainly exists to
 * separate actual controller, of which every public method must represent
 * an accessible action, from application logic. This way, ControllerEngine
 * can expose public method to the rest of the application but not as a
 * controller's action.
 */
class ControllerEngine {

	protected $moduleName;
	protected $controller;

	/** @var Config */
	protected $config = null;
	protected $configNodes = null;

	protected $path = null;
	protected $baseUrl = null;

	public function __construct(Controller $controller, $path, $baseUrl) {
		$this->controller = $controller;
		$this->moduleName = $controller->controllerName;
		$this->baseUrl = $baseUrl;
		$this->path = $path;
	}

	public function getModulePath($ds = DS, $require = true) {
		if (
			(defined('APP_MODULES_PATH') && is_dir($dir = APP_MODULES_PATH . $this->moduleName))
			|| is_dir($dir = MODULES_PATH . $this->moduleName)
		) {
			return $this->path = $dir . $ds;
		} else if ($require) {
			throw new IllegalStateException('Cannot find path for module: ' . $this->moduleName);
		} else {
			return null;
		}
	}

	public function getRelativePath($file) {
		if ($file[0] === '.' || $file[0] === '/' || $file[0] === '\\') {
			throw new SecurityException();
		} else if ($this->path !== null) {
			return "$this->path$file";
		} else {
			null;
		}
	}

	public function getRelativeUrl($relativeUrl) {
		return "$this->baseUrl$relativeUrl";
	}

	/**
	 * Get the path of the given template for this Controller. The template
	 * will first search in the Controller's own template sources, then
	 * application-wide template sources. (This behavior is *NOT* yet
	 * implemented).
	 * @todo implement behavior as described in doc
	 * @param string $name
	 * @return string
	 */
	public function getTemplatePath($name) {
		return $this->getRelativePath("$name.html.php");
	}

	/**
	 * @return Config
	 */
	public function getConfig($node = null) {
		if ($node !== null) {
			if (isset($this->configNodes[$node])) return $this->configNodes[$node];

			$root = $this->path;

			if (file_exists($path = "$root$node.yml")) {
				return $this->configNodes[$node] = Config::load($path);
			} else {
				return $this->configNodes[$node] = $this->getConfig()->node($node);
			}
		} else {
			if ($this->config !== null) return $this->config;

			if (
				file_exists($path = "$root$this->moduleName.yml")
				|| file_exists($path = "{$root}config.yml")
			) {
				return $this->config = Config::load($path);
			} else {
				throw new SystemException("Missing config file for module: $this->moduleName");
			}
		}
		throw new IllegalStateException('Unreachable code');
	}

	public function processHtmlFragment(TemplateHtml $tpl) {
		if ($tpl instanceof TemplateHtmlRoot) {
			$tpl->render();
		} else {
			$root = $this->getRootTemplate();
			$tpl->setRootTemplate($root);
			$root->body = $tpl;
			$root->render();
		}
	}

	protected function getRootTemplate() {
		return new TemplateHtmlRoot($this->getTemplatePath('index'), $this->controller);
	}
}