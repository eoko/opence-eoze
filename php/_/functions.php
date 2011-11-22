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
		} else {
			return $class;
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
//DBG			echo 'class_extend code failed: ' . $code;
//			dump_trace();
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

/**
 * Resolves the given path in the given namespace (or from the namespace of the
 * given class). .. and . can be used to navigate upper in the namesapce. Either
 * / ou \ can be used as namespace separator. If the path starts with a
 * namespace separator, it will be considered absolute (then, the given namespace
 * will effectively be ignored).
 *
 * @param string|object $ns
 * @param string $path
 * @return string
 * @throws IllegalStateException if the given path resolves upper than the
 * namespace root
 */
function relativeNamespace($ns, $path = null) {
	if (is_object($ns)) $ns = get_namespace($ns);

	$path = str_replace('\\', '/', $path);

	if (!$path || $path === '' || $path === '.') {
		return $ns;
	} else if ($path === '/') {
		return '\\';
	} else {
		$pathParts = explode(
			'/',
			trim($path, '/')
		);

		if (substr($path, 0, 1) === '/') {
			$parts = array();
		} else {
			$parts = explode('\\', trim($ns, '\\'));
		}

		foreach ($pathParts as $p) {
			if (! ($p === '' || $p === '.' || $p === '\\')) {
				if ($p === '..') {
					if (count($parts) > 0) {
						array_pop($parts);
					} else {
						throw new IllegalStateException(
							"Path ($path) go upper than root on namespace: $ns"
						);
					}
				} else {
					$parts[] = $p;
				}
			}
		}

		return implode('\\', $parts) . '\\';
	}
}

/**
 * This function is an alias for the function {@link relativeNamespace()}.
 * @return string
 */
function relative_namespace() {
	return call_user_func_array('relativeNamespace', func_get_args());
}


function is_reference_to(&$a, &$b) {
	if ($a !== $b)
		return false;

	$temp = $a;
	$checkval = ($a === null) ? "" : null;
	$a = $checkval;

	if ($b === $checkval) {
		$a = $temp;
		return true;
	} else {
		$a = $temp;
		return false;
	}
}


/**
 * An alternative to print_r that unlike the original does not use output buffering with
 * the return parameter set to true. Thus, Fatal errors that would be the result of print_r
 * in return-mode within ob handlers can be avoided.
 *
 * Comes with an extra parameter to be able to generate html code. If you need a
 * human readable DHTML-based print_r alternative, see http://krumo.sourceforge.net/
 *
 * Support for printing of objects as well as the $return parameter functionality
 * added by Fredrik Wollsén (fredrik dot motin at gmail), to make it work as a drop-in
 * replacement for print_r (Except for that this function does not output 
 * paranthesises around element groups... ;) )
 *
 * Based on return_array() By Matthew Ruivo (mruivo at gmail)
 * (http://se2.php.net/manual/en/function.print-r.php#73436)
 */
function obsafe_print_r($var, $return = false, $html = false, $level = 0) {
	$spaces = "";
	$space = $html ? "&nbsp;" : " ";
	$nl = $html ? "<br />" : "\n";
	for ($i = 1; $i <= 6; $i++) {
		$spaces .= $space;
	}
	$tabs = $spaces;
	for ($i = 1; $i <= $level; $i++) {
		$tabs .= $spaces;
	}
	if (is_array($var)) {
		$title = 'Array';
	} elseif (is_object($var)) {
		$title = get_class($var) . " Object$nl(";
		$footer = ')';
		$o = $var;
		$var = array();
		$ro = new ReflectionObject($o);
		foreach ($ro->getProperties() as $p) {
			$p->setAccessible(true);
			$var[$p->getName()] = $p->getValue($o);
		}
		foreach ($ro->getStaticProperties() as $p) {
			$p->setAccessible(true);
			$var['parent::' . $p->getName()] = $p->getValue($o);
		}
	}
	$output = $title . $nl;
	foreach ($var as $key => $value) {
		if (is_array($value) || is_object($value)) {
			$level++;
			$value = obsafe_print_r($value, true, $html, $level);
			$level--;
		}
		$output .= $tabs . "[" . $key . "] => " . $value . $nl;
	}
	if (isset($footer)) $output .= $footer;
	if ($return)
		return $output;
	else
		echo $output;
}

function includeExtension($fromFile, $extension, $require = true, $once = true, $baseExtension = null) {
	if ($baseExtension === null) {
		$baseExtension = 'class|interface|api|functions|fn';
	} else if (is_array($baseExtension)) {
		foreach ($baseExtension as &$ext) {
			$ext = preg_quote($ext, '/');
		}
		$baseExtension = implode('|', $baseExtension);
	}
	$bn = basename($fromFile);
	if (preg_match("/^(.+)\.(?:$baseExtension)\.php$/", $bn, $m)) {
		if (is_array($extension)) {
			foreach ($extension as $ext) {
				includeExtension($fromFile, $extension, $require, $once, $baseExtension);
			}
		} else {
			$file = dirname($fromFile) . DS . "$m[1].$extension.php";
			if ($require) {
				if ($once) {
					require_once $file;
				} else {
					require $file;
				}
			} else {
				if ($once) {
					include_once $file;
				} else {
					include $file;
				}
			}
		}
	} else {
		throw new \IllegalStateException('Unexpected filename: ' . $bn);
	}
}