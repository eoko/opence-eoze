<?php

namespace eoze\Base;

use eoko\config\ConfigManager;
use eoko\util\Arrays;

use eoze\util\Data;
use eoze\util\Data\DataArray;

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
	
	public function __construct(array $config = null) {
		$config = Arrays::apply(ConfigManager::get($this), $config);
		$this->config = new DataArray($config);
	}
	
}
