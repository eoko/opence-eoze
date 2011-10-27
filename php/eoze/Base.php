<?php

namespace eoze;

use eoko\config\ConfigManager;
use eoko\util\Arrays;

use eoze\util\Data;
use eoze\util\Data\DataArray;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
class Base {
	
	/**
	 * @var Data
	 */
	protected $config;
	
	final public function __construct(array $config = null) {
		$config = Arrays::apply(ConfigManager::get($this), $config);
		$this->config = new DataArray($config);
		$this->onConstruct();
	}
	
	protected function onConstruct() {}

	/**
	 * Gets the fully qualified (i.e. namespaced) name of the called class.
	 * @return string
	 */
	public static function getClass() {
		return get_called_class();
	}
}
