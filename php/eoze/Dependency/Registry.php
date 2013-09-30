<?php

namespace eoze\Dependency;

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
		if (isset($config['register()'])) {
			$this->register($config['register()']);
		}
	}

	private static function getSpecInterface($spec, &$fallbacks = false) {
		if (is_object($spec)) {
			$class = get_class($spec);
		} else if (is_string($spec)) {
			$class = $spec;
		} else {
			throw new IllegalArgumentException('Illegal argument type for $spec: ' 
					. gettype($spec));
		}
		if ($fallbacks !== false) {
			$fallbacks = Classes::getImplementedInterfaces($class, false);
		}
		return $class;
	}

	public function register($spec, $provider = null) {
		if (is_array($spec)) {
			if ($provider !== null) {
				throw new IllegalArgumentException();
			}
			foreach ($spec as $spec => $provider) {
				if (is_int($spec)) {
					$this->register($provider);
				} else {
					$this->register($spec, $provider);
				}
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
			if ($this->useFallbacks && $fallbacks) {
				foreach ($fallbacks as $fallback) {
					$this->fallbacks[$fallback] =& $this->functionnality[$interface];
				}
			}
		}
	}

	private static function instantiate($spec, &$provider, array $config = null) {
		if (is_string($provider)) {
			$provider = new $provider();
		}
		$o = null;
		if (is_object($provider)) {
			if ($provider instanceof ServiceFactory) {
				$provider = $provider->createService();
			}
			if ($provider instanceof Factory) {
				$o = $provider->create($config);
			} else {
				if ($config !== null) {
					throw new IllegalArgumentException(
						"The functionnality provider for $spec is not a factory"
					);
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
		if (is_array($spec)) {
			if (count($spec) > 1) {
				if ($config !== null) {
					// TODO maybe the $spec should be applied to the $config array here?
					// or maybe the reverse?
					throw new IllegalArgumentException();
				} else {
					$config = $spec;
				}
			}
			if (isset($spec['interface'])) {
				$spec = $spec['interface'];
				unset($config['interface']);
			} else if (isset($spec[0])) {
				$spec = $spec[0];
				unset($config[0]);
			} else {
				throw new IllegalArgumentException(
					'Missing interface information in dependency specification'
				);
			}
		}
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
//			dump($this);
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
