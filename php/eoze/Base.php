<?php

namespace eoze;

use eoko\config\ConfigManager;

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
	
	public function __construct(array $config = null) {
		$config = Arrays::apply(ConfigManager::get($this), $config);
		$this->config = new DataArray($config);
	}
	
	public static function getClass() {
		return get_called_class();
	}
}
