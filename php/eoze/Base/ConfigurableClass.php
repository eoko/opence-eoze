<?php

namespace eoze\Base;

use eoko\config\ConfigManager;

use eoze\util\Data;
use eoze\util\Data\DataArray;
use eoze\util\Classes;
use eoze\Config\Helper;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 28 oct. 2011
 */
class ConfigurableClass {

	/**
	 * @var Data
	 */
	protected $config;
	
	private static $classConfig = null;
	
	public function __construct(array $config = null) {
		if ($config) {
			$config = Helper::extend(self::getClassConfig(), $config);
		} else {
			$config = self::getClassConfig();
		}
		$this->config = new DataArray($config);
	}
	
	private static function getClassConfig() {
		$class = get_called_class();
		if (!isset(self::$classConfig[$class])) {
			$config = array();
			foreach (array_reverse(Classes::getParentNames($class, true)) as $parent) {
				$config = Helper::extend($config, ConfigManager::get($parent));
			}
			self::$classConfig[$class] = $config;
		}
		return self::$classConfig[$class];
	}
	
}
