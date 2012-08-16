<?php

namespace eoze;

use eoze\Base\ConfigurableClass;
use eoze\Dependency\Registry;
use eoze\Base\AnnotationProcessor;

use eoko\config\ConfigManager;

use ReflectionClass, ReflectionProperty, ReflectionMethod;

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
	private $dependencyRegistry;
	
	public function __construct(array $config = null) {
		parent::__construct($config);
		// Init dependency registry
		if (null !== $registry = $this->config->getOr('eoze\Dependency\Registry')) {
			$this->dependencyRegistry = $registry;
		} else {
			$this->dependencyRegistry = Eoze::getEoze()->getRegistry();
		}
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
	
//	protected function locale($message, $num = 1, array $replacements = null) {
////		return $this->messageTranslator->translate($message, $num, $replace);
//		return $message;
//	}
	
	/**
	 * @var AnnotationProcessor
	 */
	private static $annotationProcessor;

	/**
	 * @return AnnotationProcessor
	 */
	private static function getAnnotationProcessor() {
		if (!self::$annotationProcessor) {
			self::$annotationProcessor = new AnnotationProcessor();
		}
		return self::$annotationProcessor;
	}
	
	private function resolveDependencies() {
		$annotationProcessor = self::getAnnotationProcessor();
		$class = get_class($this);
		
		$propertyInjections = $annotationProcessor->parsePropertyInjections($class);
		foreach ($this->config as $key => $value) {
			if ($key{0} === '$') {
				$key = substr($key, 1);
				$propertyInjections[$key] = $value;
			}
		}
		
		$setterInjections = $annotationProcessor->parseSetterInjections($class);
		
		if ($propertyInjections) {
			foreach ($propertyInjections as $property => $spec) {
				$this->dependencyRegistry->hook($this->$property, $spec);
			}
		}

		if ($setterInjections) {
			foreach ($setterInjections as $method => $argumentSpecs) {
				if ($argumentSpecs) {
					$arguments = array();
					foreach ($argumentSpecs as $spec) {
						$arguments[] = $this->dependencyRegistry->get($spec);
					}
					switch (count($arguments)) {
						case 1:
							$this->$method($arguments[0]);
							break;
						case 2:
							$this->$method($arguments[0], $arguments[1]);
							break;
						case 3:
							$this->$method($arguments[0], $arguments[1], $arguments[2]);
							break;
					}
				} else {
					$this->$method();
				}
			}
		}
	}
	
}
