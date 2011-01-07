<?php

const GET_NAMESPACE_RETURN_ABSOLUTE_CLASSNAME = 1;
const GET_NAMESPACE_RET_ABS_CLASS             = GET_NAMESPACE_RETURN_ABSOLUTE_CLASSNAME;
const GET_NAMESPACE_RTRIM                     = 2;
const GET_NAMESPACE_LTRIM                     = 4;
CONST GET_NAMESPACE_TRIM                      = 6;

//function get_namespace($class, &$relativeClassName = false, &$absoluteClassName = false, $trim = false) {
function get_namespace($class, &$className = false, $opts = 0) {
	
	if (is_int($className) && func_num_args() === 2) {
		$opts = $className;
		$className = false;
	}
	if (is_object($class)) $class = get_class($class);

	if (preg_match('/(^.+)\\\\([^\\\\]+)$/', $class, $m)) {
		if ($className !== false) {
			if ($opts & GET_NAMESPACE_RETURN_ABSOLUTE_CLASSNAME) {
				$className = $class;
			} else {
				$className = $m[2];
			}
		}
		if ($opts & GET_NAMESPACE_TRIM) {
			return trim($m[1], '\\');
		} else if ($opts & GET_NAMESPACE_RTRIM) {
			return $m[1];
		} else if ($opts & GET_NAMESPACE_LTRIM) {
			return ltrim($m[1], '\\') . '\\';
		} else {
			return $m[1] . '\\';
		}
	} else {
		if ($className !== false) {
			if ($opts & GET_NAMESPACE_RETURN_ABSOLUTE_CLASSNAME) {
				if (substr($class, 0, 1) === '\\') {
					$className = $class;
				} else {
					$className = "\\$class";
				}
			} else {
				if (substr($class, 0, 1) === '\\') {
					$className = substr($class, 1);
				} else {
					$className = $class;
				}
			}
		}
		if ($opts & GET_NAMESPACE_TRIM) {
			return '';
		} else if ($opts & GET_NAMESPACE_RTRIM) {
			return '';
		} else if ($opts & GET_NAMESPACE_LTRIM) {
			return '';
		} else {
			return '\\';
		}
	}
}

function relative_classname($class) {
	if (preg_match('/\\\\([^\\\\]+)$/', $class, $m)) {
		return $m[1];
	} else {
		if (substr($class, -1) === '\\') {
			throw new IllegalArgumentException('Not a class name pattern: ' . $class);
		}
	}
}

function get_relative_classname($class) {
	if (is_object($class)) $class = get_class($class);
	return relative_classname($class);
}

function parseNamespace($class, &$className = false, $opts = 0) {

	if (is_int($className) && func_num_args() === 2) {
		$opts = $className;
		$className = false;
	}
	
	if (is_array($class)) {
		if (count($class) !== 2) {
			throw new IllegalArgumentException('$class array must have exactly 2 elements (namespace, class)');
		}

		list($ns, $class) = $class;
		$ns = rtrim($ns, '\\');
		if ($className !== false) {
			if ($opts & GET_NAMESPACE_RETURN_ABSOLUTE_CLASSNAME) $className = "$ns\\$class";
			else $className = $class;
		}

		if ($opts & GET_NAMESPACE_TRIM) {
			return ltrim($ns, '\\');
		} else if ($opts & GET_NAMESPACE_RTRIM) {
			return $ns;
		} else if ($opts & GET_NAMESPACE_LTRIM) {
			return ltrim($ns, '\\') . '\\';
		} else {
			return "$n\\";
		}
	}

	if (is_object($class)) {
		return get_namespace($class, $relativeClassName, $absoluteClassName, $rtrim);
	} else if (substr($class, -1) === '\\') { // must be done after we've tested $class is not an object
		// we've got only a namespace, here!
		if ($className !== false) {
			throw new IllegalArgumentException("Cannot extract class from a naked namespace: $class");
		}
		if ($opts & GET_NAMESPACE_TRIM) {
			return trim($class, '\\');
		} else if ($opts & GET_NAMESPACE_RTRIM) {
			return trim($class, '\\');
		} else if ($opts & GET_NAMESPACE_LTRIM) {
			return ltrim($class, '\\') . '\\';
		} else {
			return "$class\\";
		}
	} else {
		return get_namespace($class, $className, $opts);
	}
}

function get_namespace_trimmed($class, &$className = false, $opts = GET_NAMESPACE_RTRIM){
	if (is_int($className) && func_num_args() === 2) {
		$opts = $className;
		$className = false;
	}
	$opts |= GET_NAMESPACE_RTRIM;
	return get_namespace($class, $className, $opts);
}

/**
 *
 * @param string $newClassName
 * @param string $baseClassName
 * @param string $namespace
 * @return string the code which performs the extend operation
 */
function class_extend($newClassName, $baseClassName, $namespace = null, $execute = true) {

	if (class_exists($newClassName, false)) {
		throw new IllegalStateException('Cannot redeclare class ' . $newClassName);
	}

	if ($namespace !== null) {
		$namespace = rtrim($namespace, '\\');
	} else {
		$namespace = get_namespace_trimmed($newClassName, $newClassName);
	}

	if (substr($baseClassName, 0, 1) !== '\\') $baseClassName = "\\$baseClassName";

	if ($namespace !== '') {
		$code = "namespace $namespace { class $newClassName extends $baseClassName {} }";
	} else {
		$code = "class $newClassName extends $baseClassName {}";
	}

	if ($execute) {
		if (false === eval($code)) {
		 dump_trace();
			throw new IllegalStateException('class_extend code failed: ' . $code);
		}
	}

	return $code;
}

/**
 * @internal rx: ??? what is that??
 */
function bcabs($n) {
	if (substr($n, 0, 1) === '-') return substr($n, 1);
	else return $n;
}