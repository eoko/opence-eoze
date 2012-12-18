<?php

namespace eoko\module\executor;

use eoko\template\Template;

/**
 * Base class for executors, that implements __get and __set methods for the
 * executor's actions to work with a default template.
 */
abstract class TemplateExecutor extends ExecutorBase {

	/** 
	 * @var \eoko\template\HtmlTemplate the current action default template
	 */
	private $myTemplate = null;

	protected function setTemplate($template = null) {
		if ($template instanceof Template) {
			$this->myTemplate = $template;
		} else {
			$this->myTemplate = $this->createTemplate($template, true);
		}
	}

	abstract protected function createTemplate($name, $require = true, $opts = null);

	/**
	 * @return Template
	 */
	protected function getTemplate() {
		if ($this->myTemplate === null) {
			$this->myTemplate = $this->createTemplate(null);
		}
		return $this->myTemplate;
	}

	protected function clearData() {
		$this->getTemplate()->clearData();
	}

	public function getData() {
		return $this->getTemplate()->getData();
	}

	public function __set($name, $value) {
		if ($this->myTemplate) {
			$this->myTemplate->__set($name, $value);
		} else {
			$this->getTemplate()->__set($name, $value);
		}
	}

	public function __get($name) {
		if ($this->myTemplate) {
			return $this->myTemplate->__get($name);
		} else {
			return $this->getTemplate()->__get($name);
		}
	}

	public function __isset($name) {
		if ($this->myTemplate) {
			return $this->myTemplate->__isset($name);
		} else {
			return $this->getTemplate()->__isset($name);
		}
	}

	public function __unset($name) {
		$this->getTemplate()->__unset($name);
	}

	/**
	 * @param string $in
	 * @param mixed $value
	 * @return Template
	 */
	protected function push($in, $value) {
		return $this->getTemplate()->push($in, $value);
	}

	/**
	 * @param string $in
	 * @param string $key
	 * @param mixed $value
	 * @return Template
	 */
	protected function put($in, $key, $value) {
		return $this->getTemplate()->put($in, $key, $value);
	}

	/**
	 * @param string|array Array $_1
	 * @param array $_2
	 * @return Template
	 */
	protected function merge($_1, $_2 = null) {
		if ($_2 !== null) {
			return $this->getTemplate()->mergeIn($_1, $_2);
		} else {
			return $this->getTemplate()->merge($_1);
		}
	}
}
