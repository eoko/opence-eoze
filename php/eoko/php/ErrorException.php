<?php

namespace eoko\php;

use Exception;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 14 mars 2012
 */
class ErrorException extends Exception {
	
	private static $registered = false;
	
	private static $onErrorListeners = null;
	
	public function __construct($code, $message, $file, $line) {
		$this->code    = $code;
		$this->file    = $file;
		$this->line    = $line;
		$this->message = $message;
	}
	
	public function __toString() {
		$s = "PHP error with message: '{$this->getMessage()}'";
		if ($this->file) {
			$s .= " in $this->file";
			if ($this->line) {
				$s .= ":$this->line";
			}
		}
		$s .= PHP_EOL;
		$s .= 'Stack trace:' . PHP_EOL;
		
		$trace = $this->getTrace();
		if (count($trace)) {
			array_shift($trace);
		}
		
		$traceLines = array();
		$i = 0;
		foreach ($trace as $traceLine) {
			$ts = "#$i ";
			if (isset($traceLine['file'])) {
				$ts .= $traceLine['file'];
			}
			if (isset($traceLine['line'])) {
				$ts .= ':' . $traceLine['line'];
			}
			$ts .= ': ';
			if (isset($traceLine['class'])) {
				$ts .= $traceLine['class'] . '->';
			}
			if (isset($traceLine['function'])) {
				$ts .= $traceLine['function'] . '(';
				if (isset($traceLine['args'])) {
					$args = array();
					foreach ($traceLine['args'] as $arg) {
						if ($arg === null) {
							$args[] = 'NULL';
						} else if (is_bool($arg)) {
							$args[] = $arg ? 'TRUE' : 'FALSE';
						} else if (is_object($arg)) {
							$args[] = 'Object(' . get_class($arg) . ')';
						} else if (is_array($arg)) {
							$args[] = 'Array';
						} else if (is_string($arg)) {
							$arg = substr($arg, 0, 15) . (strlen($arg) > 15 ? '...' : '');
							$args[] = "'$arg'";
						} else {
							$args[] = $arg;
						}
					}
					$ts .= implode(', ', $args);
				}
				$ts .= ')';
			}
			$traceLines[] = $ts;
			$i++;
		}
		$s .= implode(PHP_EOL, $traceLines);
		return $s;
	}
	
	public static function registerErrorHandler() {
		if (!self::$registered) {
			set_error_handler(array(get_class(), 'wrapError'), E_ALL & ~E_STRICT);
			self::$registered = true;
		}
	}
	
	public static function wrapError($code, $message, $file, $line) {
		if (error_reporting() !== 0) {
			$exception = new ErrorException($code, $message, $file, $line);
			if (self::$onErrorListeners) {
				foreach (self::$onErrorListeners as $listener) {
					call_user_func($listener, $exception);
				}
			}
			throw $exception;
		}
	}
	
	public static function onError($listener) {
		self::$onErrorListeners[] = $listener;
	}
}
