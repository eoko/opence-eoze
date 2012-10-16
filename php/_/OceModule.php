<?php

abstract class OceModule {

	public $name;
	public $path;
	public $simple;

	private $config = null;

	public function __construct($name, $path, $simple) {
		$this->name = $name;
		$this->simple = $simple;
		$this->path = $path;
	}

	public static function isValid($name, $path) {
		return file_exists($path . DS . "config.yml")
				|| file_exists($path . DS . "$name.yml");
	}

	/**
	 * @return Config
	 */
	public function getConfig() {

		if ($this->config !== null) return $this->config;

		if (!file_exists($path = $this->path . DS . "config.yml")
				&& !file_exists($path = $this->path . DS . "$this->name.yml")
				) {

			throw new SystemException(
				"No config information available for module: $this->name ($this->path)"
			);
		}

		return $this->config = Config::load($path, $this->name);
	}

	/**
	 * Get the class name identifying this module.
	 * @return string
	 */
	public function getClass() {
		return $this->getConfig()->class;
	}

	/**
	 * @return boolean TRUE if this module has a controller, else false.
	 */
	abstract public function hasController();

	/**
	 * Load the controller class of this module.
	 */
	abstract public function loadController();

}