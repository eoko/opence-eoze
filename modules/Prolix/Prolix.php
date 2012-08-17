<?php

namespace eoko\modules\Prolix;

use eoko\module\Module;

use InvalidConfigurationException;
use IllegalArgumentException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */
class Prolix extends Module {
	
	protected $defaultExecutor = 'json';

	public function listExistingModelsTables() {
		
		$models = array();
		$tables = array();
		
		$dir = dir(MODEL_PATH);
		while (false !== $file = $dir->read()) {
			if (preg_match('/^(?P<table>(?P<model>.+)Table)(?:\.class)?\.php$/', $file, $matches)) {
				$models[] = $matches['model'];
				$tables[] = $matches['table'];
			}
		}
		
		return array('models' => $models, 'tables' => $tables);
	}
	
	public function getModelConfig($name) {
		$config = $this->getConfig()->get('models');
		if (!isset($config)) {
			throw new InvalidConfigurationException('Missing config: models');
		}
		if (!array_key_exists($name, $config)) {
			throw new IllegalArgumentException("Model $name is not configured");
		}
		return isset($config[$name]) ? $config[$name] : array();
	}
	
	public function getModelSpec($name) {
		$config = $this->getModelConfig($name);
	}
}
