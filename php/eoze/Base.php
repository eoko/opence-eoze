<?php

namespace eoze;

use eoze\Base\ConfigurableClass;
use eoze\Dependency\Registry;

use eoko\config\ConfigManager;

use ReflectionClass, ReflectionProperty;

use IllegalStateException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 27 oct. 2011
 */
class Base extends ConfigurableClass {
	
	/**
	 * @var Registry
	 */
	private static $dependencyRegistry;
	
	public function __construct(array $config = null) {
		parent::__construct($config);
		$this->dependencyRegistry = Eoze::getEoze()->getRegistry();
		$this->resolveDependencies();
		$this->construct();
	}
	
	protected function construct() {}

	/**
	 * Gets the {@link Registry} used to resolve dependencies within this
	 * class.
	 * @return Registry
	 */
	public function getDependencyRegistry() {
		return $this->dependencyRegistry;
	}

	/**
	 * Gets the fully qualified (i.e. namespaced) name of the called class.
	 * @return string
	 */
	public static function getClass() {
		return get_called_class();
	}
	
	protected function locale($message, $num = 1, array $replacements = null) {
//		return $this->messageTranslator->translate($message, $num, $replace);
		return $message;
	}
	
	private function resolveDependencies() {
		$injections = array();
		foreach ($this->config as $key => $value) {
			if ($key{0} === '$') {
				$key = substr($key, 1);
				$injections[$key] = $value;
			}
		}
		$this->getInjectionsFromAnnotations($injections);
		foreach ($injections as $key => $spec) {
			$this->dependencyRegistry->hook($this->$key, $spec);
		}
	}
	
	private function getInjectionsFromAnnotations(array &$injections) {
		$class = new ReflectionClass($this);
		$filter = ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED;
		foreach ($class->getProperties($filter) as $property) {
			$name = $property->getName();
			if (!isset($injections[$name])) {
				$doc = $property->getDocComment();
				if ($doc) {
					if (preg_match('/^[\s*]*@Eoze:inject(?:$|\s+(.+)$)/m', $doc, $m)) {
						if (isset($m[1])) {
							$spec = trim($m[1]);
						} else if (preg_match('/^[\s*]*@var\s+([^\s]+)$/m', $doc, $m)) {
							$spec = trim($m[1]);
						} else {
							throw new IllegalStateException('Illegal annotation ');
						}
						$injections[$name] = $spec;
					}
				}
			}
		}
	}
	
}
