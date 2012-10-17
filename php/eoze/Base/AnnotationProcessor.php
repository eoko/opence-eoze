<?php

namespace eoze\Base;

use ReflectionClass,
	ReflectionProperty,
	ReflectionMethod;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class AnnotationProcessor {
	
	private $classesSetterInjections = array();
	
	private $classesInjections = array();
	
	// ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED
	// === ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
	private static $filter = 768;
	
	private static $eozeInjectRegex;
	
	private static $varRegex;
	
	private static $paramRegex;
	
	private static function initRegexes() {
		if (self::$eozeInjectRegex) {
			return;
		}
		
		$injectTag = '@Eoze:inject';
		// Those are (roughly) the official regexes from php.net/manual
		$nsClass = '\\\\?(?P<class>[a-zA-Z_\x7f-\xff][\\\\a-zA-Z0-9_\x7f-\xff]*)';
		$variable  = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
		
		// doc line start
		$start = '/^[\s*]*';
		
		self::$eozeInjectRegex = $start . "$injectTag(?:\s*$|\s+$nsClass)/m";
		
		self::$varRegex        = $start . "@var\s+$nsClass/m";
		
		self::$paramRegex      = $start . "@param\s+(?:$nsClass\s+)?\\$(?P<name>$variable)/m";
	}
	
	public function __construct() {
		self::initRegexes();
	}
	
	public static function parsePropertyDocCommentInjections($doc) {
		if ($doc instanceof ReflectionProperty) {
			$doc = $doc->getDocComment();
		}
		if ($doc && preg_match(self::$eozeInjectRegex, $doc, $m)) {
			if (isset($m['class'])) {
				return trim($m['class']);
			} else if (preg_match(self::$varRegex, $doc, $m)) {
				return trim($m['class']);
			} else {
				throw new InvalidAnnotationException('@var type is required');
			}
		} else {
			return null;
		}
	}
	
	public function parsePropertyInjections($class) {
		if (!isset($this->classesInjections[$class])) {
			$reflectionClass = new ReflectionClass($class);
			$injections = array();
			foreach ($reflectionClass->getProperties(self::$filter) as $property) {
				$name = $property->getName();
				if (null !== $spec = self::parsePropertyDocCommentInjections($property)) {
					$injections[$name] = $spec;
				}
			}
			$this->classesInjections[$class] = $injections ? $injections : false;
		}
		return $this->classesInjections[$class] ? $this->classesInjections[$class] : null;
	}
	
//	public static function parseSetterDocCommentInjections($doc) {
//		if ($doc instanceof ReflectionMethod) {
//			$doc = $doc->getDocComment();
//		}
//		if ($doc && preg_match(self::$eozeInjectRegex, $doc, $m)) {
//			if (isset($m['class'])) {
//				return array(trim($m['class']));
//			} else if (null !== $setters = self::parseParamTags($doc)) {
//				return $setters;
//			} else {
//				throw new InvalidAnnotationException('@param tags are required');
//			}
//		} else {
//			return null;
//		}
//	}
	
	public static function parseParamTags($doc) {
		if (preg_match_all(self::$paramRegex, $doc, $matches, PREG_SET_ORDER)) {
			$params = null;
			foreach ($matches as $match) {
				$params[$match['name']] 
						= (isset($match['class']) && $match['class']) ? $match['class'] : null;
			}
			return $params;
		} else {
			return null;
		}
	}
	
	public static function parseMethodParamInjection(ReflectionMethod $method) {
		$len = $method->getNumberOfParameters();
		if ($len === 0) {
			return null;
		}
		$doc = $method->getDocComment();
		if (preg_match(self::$eozeInjectRegex, $doc, $matches)) {
			$parameters = $method->getParameters();
			if (isset($matches['class'])) {
				if ($len !== 1) {
					throw new InvalidAnnotationException();
				} else {
					$paramName = $parameters[0]->getName();
					return array($paramName => $matches['class']);
				}
			} else {
				$injections = null;
				$missings = null;
				foreach ($parameters as $param) {
					if (!$param->isOptional()) {
						if (null !== $class = $param->getClass()) {
							$injections[$param->getName()] = $class->getName();
						} else {
							$paramName = $param->getName();
							// put a place holder to respect params order
							$injections[$paramName] = null;
							$missings[] = $paramName;
						}
					} else {
						if (count($injections) !== $method->getNumberOfRequiredParameters()) {
							throw new InvalidAnnotationException(
								"Cannot determine classes for dependency method"
								. $method->getDeclaringClass()->getName() .'::'. $method->getName()
							);
						}
						break;
					}
				}
				if ($missings) {
					$paramTags = self::parseParamTags($doc);
					foreach ($missings as $paramName) {
						if (isset($paramTags[$paramName])) {
							$injections[$paramName] = $paramTags[$paramName];
						} else {
//							dump($paramTags);
							throw new InvalidAnnotationException(
								"Missing interface information for param $$paramName of method "
								. $method->getDeclaringClass()->getName() .'::'. $method->getName()
							);
						}
					}
				}
				return $injections;
			}
		} else {
			return null;
		}
	}
	
	public function parseSetterInjections($class) {
		if (!isset($this->classesSetterInjections[$class])) {
			$reflectionClass = new ReflectionClass($class);
			$injections = array();
			foreach ($reflectionClass->getMethods(self::$filter) as $method) {
				if (null !== $methodInjections = self::parseMethodParamInjection($method)) {
					$injections[$method->getName()] = $methodInjections;
				}
//				assert($method instanceof ReflectionMethod);
//				$name = $method->getName();
//				if (null !== $spec = $this->parseSetterDocCommentInjections($method)) {
//					$injections[$name] = $spec;
//				}
			}
			$this->classesSetterInjections[$class] = $injections ? $injections : false;
		}
		return $this->classesSetterInjections[$class] 
				? $this->classesSetterInjections[$class] : null;
	}
	
}
