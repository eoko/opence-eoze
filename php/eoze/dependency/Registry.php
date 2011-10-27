<?php

namespace eoze\dependency;

use UnsupportedOperationException;
use IllegalStateException;
use IllegalArgumentException;

use eoko\log\Logger;
use eoko\config\ConfigManager;

use eoze\util\Classes;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
class Registry {
	
	private $functionnality;

	private $useFallbacks = true;
	
	private $fallbacks;
	
	public function __construct(array $config = null) {
		$this->useFallbacks = !isset($config['useFallbacks']) || $config['useFallbacks'];
	}

	private static function getSpecInterface($spec, &$fallbacks = false) {
		if (is_object($spec)) {
			$class = get_class($spec);
		} else if (is_string($spec)) {
			$class = $spec;
		} else {
			throw new IllegalArgumentException();
		}
		if ($fallbacks !== false) {
			$fallbacks = Classes::getImplementedInterfaces($class, false);
		}
		return $class;
	}
	
	public function register($spec, $provider = null) {
		if (is_array($spec)) {
			foreach ($spec as $spec) {
				$this->registerAs($spec, $provider);
			}
		} else {
			if ($provider === null) {
				$provider = $spec;
			}
			$interface = self::getSpecInterface($spec, $fallbacks);
			if (isset($this->functionnality[$interface])) {
				Logger::get($this)->warn("Already registered: $interface");
			}
			$this->functionnality[$interface] = $provider;
			if ($this->useFallbacks) {
				foreach ($fallbacks as $fallback) {
					$this->fallbacks[$fallback] =& $this->functionnality[$interface];
				}
			}
		}
	}
	
	private static function instantiate($spec, &$provider, array $config = null) {
		if (is_string($provider)) {
			$provider = new $provider(ConfigManager::get($provider));
		}
		$o = null;
		if (is_object($provider)) {
			if ($provider instanceof ServiceFactory) {
				$provider = $provider->createService(ConfigManager::get($provider));
			}
			if ($provider instanceof Factory) {
				$o = $provider->create($config);
			} else {
				if ($config !== null) {
					Logger::get($this)->warn('$config object is ignored');
				}
				$o = $provider;
			}
		}
		if ($o) {
			if (is_a($o, $spec)) {
				return $o;
			} else {
				throw new IllegalStateException('Wrong dependency type (expected: '
						. self::getSpecInterface($spec) . ', actual: ' . get_class($o) . ')');
			}
		} else {
			return null;
		}
	}
	
	public function getIf($spec, array $config = null) {
		if (isset($this->functionnality[$spec])) {
			return self::instantiate($spec, $this->functionnality[$spec], $config);
		} else if (isset($this->fallbacks[$spec])) {
			return self::instantiate($spec, $this->fallbacks[$spec], $config);
		}
	}
	
	public function get($spec, array $config = null) {
		if (null !== $o = $this->getIf($spec, $config)) {
			return $o;
		} else {
			throw new MissingDependencyException("Missing dependency: $spec");
		}
	}
	
	public function hook(&$hook, $spec, array $config = null) {
		$hook = $this->get($spec, $config);
	}
	
	public function hookIf(&$hook, $spec, array $config = null) {
		$hook = $this->getIf($spec, $config);
	}
}
