<?php

namespace eoko\database\Dumper;

use eoko\database\Dumper,
	eoko\util\collection\Map,
	eoko\log\Logger;

use RuntimeException;

/**
 * Base implementation of the {@link Dumper} interface. This class composes a 
 * {@link Logger} and a config {@link Map map} for its child concrete classes.
 * It also handles logic for calling {@link DumperListener event listeners}.
 * 
 * Listeners are registered in the 'eoko.database.dump' node of the application
 * configuration.
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 10 avr. 2012
 * @version 1.0.0
 */
abstract class AbstractDumper implements Dumper {

	/**
	 * @var Map
	 */
	private $config;

	/**
	 * @var DumperListener[]|null
	 */
	private $listeners;

	/**
	 * Creates a new {@link AbstractDumper} object.
	 * @param Map $config 
	 */
	public function __construct(Map $config) {
		$this->config = $config;
	}

	public function getLogger() {
		return Logger::get($this);
	}

	public function getConfig() {
		return $this->config;
	}

	final public function dump($dataFilename, $structureFilename = null) {
		if (false !== $this->_beforeDump($dataFilename, $structureFilename)) {
			$this->doDump($dataFilename, $structureFilename);
		}
	}

	/**
	 * Get the {@link DumperListener}s registered in the configuration.
	 * @return DumperListener[]
	 */
	protected function getListeners() {

		// Load cache
		if ($this->listeners === null) {

			$this->listeners = array();

			$config = $this->getConfig()->toArray();

			if ($config['dump']['listeners']) {
				foreach ($config['dump']['listeners'] as $class) {
					if (class_exists($class)) {
						$this->listeners[] = new $class($this);
					} else {
						throw new RuntimeException("Dumper listener class not found: $class");
					}
				}
			}
		}

		return $this->listeners;
	}

	private function _beforeDump($dataFilename, $structureFilename = null) {

		foreach ($this->getListeners() as $listener) {
			if (false === $listener->beforeDump($dataFilename, $structureFilename)) {
				return false;
			}
		}

		return $this->beforeDump($dataFilename, $structureFilename);
	}

	/**
	 * Hook method called before the {@link doDump()} method is called. If this method
	 * returns `false`, then the dump procedure will be stopped (but it is the 
	 * responsability of the implementing class to log information concerning this).
	 * 
	 * @param string $dataFilename
	 * @param string $structureFilename 
	 * @return void|false
	 */
	protected function beforeDump($dataFilename, $structureFilename = null) {}

	/**
	 * Implementation of the dump operation.
	 * @param string $dataFilename
	 * @param string $structureFilename
	 */
	abstract protected function doDump($dataFilename, $structureFilename = null);
}
