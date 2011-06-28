<?php

/**
 * @package PS-LOG-1
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/psopence.txt
 */

use eoko\shell\ShellEoze;
use \IllegalArgumentException;

/**
 * Logging utility class.
 *
 * Configuration: use {@link Logger::addAppender()} to add {@link LoggerAppender appenders},
 * that is destinations for the log.
 *
 * Usage: <ol>
 * <li>Get a logger with the {@link Logger::getLogger() getLogger() method}:
 * Logger::getLogger() to get the default Logger, or Logger::getLogger('ClassName')
 * to get a named Logger (there isn't much difference between the two, as of
 * now... but the second form is preferred, as it will allow for fine
 * configuration of log levels later).
 * <li>Use one of the log methods (debug, info, warn, error) to output a message
 * if the corresponding log level is enabled.
 * </ol>
 *
 * All log methods accepts arguments as follows:
 *
 * {method}($msgFormatString, [$arg0, ...])
 * 
 * ...where $msgFormatString is a string which can contains '{}' placeholders,
 * which will themself be replaced by arguments $args0, etc.
 * 
 * Arguments will be casted to strings by the logger only <b>after</b> it has
 * determined whether or not the log entry should be processed, in order to avoid
 * unnecessary casting in lower log levels. For best performance, arguments
 * should therefore be passed to the log methods as they are, without casting
 * them, and as much of the message formatting as possible should be done in
 * the given message format string.
 */
class Logger {

	/**
	 * @var Logger
	 */
	private static $defaultLogger = null;
	private static $defaultContext = 'ROOT';
	
	/**
	 * Shortcut for {@link Logger::getLogger()}
	 * 
	 * Get the Logger for the given context, or get the default Logger, if no
	 * context is given.
	 * @param String $context
	 * @return Logger
	 */
	public static function get($context = null) {
		return self::getLogger($context);
	}

	/**
	 * Get the Logger for the given context, or get the default Logger, if no
	 * context is given.
	 * @param String $context
	 * @return Logger
	 */
	public static function getLogger($context = null) {
		
		if (self::$defaultLogger === null)
			self::$defaultLogger = new \eoko\log\Logger();

		if ($context !== null) {
			if (is_object($context))
				$context = get_class($context);
			if (preg_match('/\\\\([^\\\\]+)$/', $context, $m))
				$context = $m[1];
			self::$defaultLogger->setContext($context);
		}
		else
			self::$defaultLogger->setContext(self::$defaultContext);

		return self::$defaultLogger;
	}

	public static function setDefaultContext($context) {
		$old = self::$defaultContext;
		self::$defaultContext = $context;
		$logger = self::getLogger();
		$logger->logLevel[self::$defaultContext] = $logger->logLevel[$old];
		unset($logger->logLevel[$old]);
	}

	const ERROR = 0;
	const WARNING = 5;
	const ASSERTION = 6;
	const INFO = 7;
	const DEBUG = 10;
	const ALL = 100;

	private static $levelNames = array(
		self::ERROR => 'ERROR',
		self::WARNING => 'WARN',
		self::ASSERTION => 'ASSERT',
		self::INFO => 'INFO',
		self::DEBUG => 'DEBUG'
	);
	protected $context = 'ROOT';
	private $logLevel = array(
		'ROOT' => self::ALL
	);
	private static $appenders = array();
	protected static $buffer = null;

	public static function addAppender(LoggerAppender $appender) {
		self::$appenders[] = $appender;
	}

	public static function removeAllAppender($class = null) {
		foreach (self::$appenders as $i => $a) {
			if ($class === null || get_class($a) === $class)
				unset(self::$appenders[$i]);
		}
	}

	static function getLevelName($level) {
		return self::$levelNames[$level];
	}

	public static function startBuffer() {
		if (self::$buffer === null)
			self::$buffer = array();
	}

	public static function flush() {
		if (self::$buffer !== null) {
			$buffer = self::$buffer;
			self::$buffer = null;
			foreach ($buffer as $entry) {
				list($level, $msg, $args) = $entry;
				self::getLogger()->logImpl($level, $msg, $args);
			}
		}
	}

	public function setLevel($level) {
		$this->logLevel[$this->context] = $level;
	}
	
	public static function setLevels($levels) {
		$logger =& self::getLogger();
		foreach ($levels as $context => $level) {
			$logger->logLevel[$context] = constant("Logger::$level");
		}
	}

	/**
	 *
	 * @param int $level
	 * @param boolean $testHasAppender If TRUE, will also test if the Logger has
	 * at least one appender and, if not the case, will return FALSE even if the
	 * given $level is active.
	 * @return boolean
	 */
	public function isActive($level, $testHasAppender = true) {
		if (isset($this->logLevel[$this->context])) {
			$logLevel = $this->logLevel[$this->context];
		} else {
			$logLevel = $this->logLevel[self::$defaultContext];
		}
		return $level <= $logLevel && (!$testHasAppender || self::isLogSomewhere());
	}

	private function setContext($context) {
		//$this->context = strtoupper($context);
		$this->context = $context;
	}

	private static function isLogSomewhere() {
		return count(self::$appenders) > 0;
	}

	private static function formatException(Exception $ex) {
		if ($ex instanceof OpenceException) {
			return $ex->__toString();
		} else {
			return OpenceException::formatPhpException($ex);
		}
	}

	private $replaceArgs = null;

	function replaceCallback() {
		return array_pop($this->replaceArgs);
	}

	private function getTraceString() {

		$thisBasename = basename(__FILE__);

		foreach (debug_backtrace() as $trace) {
			if (basename($trace['file']) != $thisBasename) {
				$filename = substr(realpath($trace['file']), strlen(ROOT));
				return $filename . ':' . $trace['line'];
			}
		}
	}

	protected function logImpl($level, $msg, $args = array()) {

		if ($this->isActive($level)) {

			if (self::$buffer !== null) {
				self::$buffer[] = array($level, $msg, $args);
				return;
			}

			$exception = null;

			if (count($args) > 0) {

				if (count($args) == 1 && $args[0] instanceof Exception) {
					$exception = $args[0];
				} else {
					foreach ($args as &$v) {
						if (is_array($v)) {
							$v = 'Array[' . count($v) . ']';
//							$args[$i] = print_r($v, true);
						} else if (is_object($v)) {
							if (!method_exists(get_class($v), '__toString')) {
								$v = get_class($v);
							} else {
								$v = $v->__toString();
							}
						} else {
							$v = Debug::valueToReadable($v);
						}
					}
					unset($v);

					// Replace {} placeholders with values
					$this->replaceArgs = array_reverse($args);
					$msg = preg_replace_callback('/{}/', array($this, 'replaceCallback'), $msg);
				}
			}

//            $msg = sprintf("[%-5s %s] %s: %-90s %s",
//                            $this->getLevelName($level),
//                            date('Y/m/d h:i:s'),
//                            $this->context,
//                            '$msg',
//							$this->getTraceString());

			if ($exception !== null) {
				$msg .= PHP_EOL . self::formatException($exception);
			}

			$date = date('Y/m/d h:i:s');
			$infoLine = $this->getTraceString();

			$entry = new LogEntry($msg, $level, $date, $this->context, $infoLine);

			foreach (self::$appenders as $appender) {
				$appender->process($entry);
			}
		}
	}

	/**
	 * Log the given message with the specified log level
	 * 
	 * The $msg string parameter can contains placeholders of the form {} which
	 * will be replaced in order by the optional arguments.
	 *
	 * This method can be called either from an instanciated Logger obtained
	 * with the Logger::getLogger('ContextName') method, or statically from the
	 * Logger class. However, the first form should be preferred for log entries
	 * which which are intended to be kept in production code, in order to
	 * enable fine-grained configuration of context-dependant log levels.
	 *
	 * For best performance, as much as possible of the message formatting
	 * should be left to the logger (that is, should be done with the $msg
	 * param), and given arguments casting to string should be left to the
	 * logger (that is, being passed as-is).
	 *
	 * @param Const $level	the level at which the message will be logged
	 * @param String $msg	the formatted message string
	 * @param mixed $args,... optional arguments to sequently replace the {}
	 * placeholders
	 */
	public static function log($level, $msg) {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive($level)) {
			$args = func_get_args();
			$logger->logImpl($level, $msg, array_slice($args, 2));
		}
	}

	/**
	 * Log the given message with the DEBUG log level
	 *
	 * The $msg string parameter can contains placeholders of the form {} which
	 * will be replaced in order by the optional arguments.
	 *
	 * This method can be called either from an instanciated Logger obtained
	 * with the Logger::getLogger('ContextName') method, or statically from the
	 * Logger class. However, the first form should be preferred for log entries
	 * which which are intended to be kept in production code, in order to
	 * enable fine-grained configuration of context-dependant log levels.
	 *
	 * For best performance, as much as possible of the message formatting
	 * should be left to the logger (that is, should be done with the $msg
	 * param), and given arguments casting to string should be left to the
	 * logger (that is, being passed as-is).
	 *
	 * @param Const $level	the level at which the message will be logged
	 * @param String $msg	the formatted message string
	 * @param mixed $args,... optional arguments to sequently replace the {}
	 * placeholders
	 */
	public function debug($msg) {
//		$logger = isset($this) && get_class($this) == __CLASS__ ? $this : Logger::getLogger();
		$logger = isset($this) && $this instanceof Logger ? $this : Logger::getLogger();
		if ($logger->isActive(self::DEBUG)) {
			if (func_num_args() == 1 && !is_string($msg))
				return $logger->debug('{}', $msg);
			$args = func_get_args();
			$logger->logImpl(self::DEBUG, $msg, array_slice($args, 1));
		}
	}

	public static function dbg($msg) {
		$logger = Logger::getLogger();
		if ($logger->isActive(self::DEBUG)) {
			if (func_num_args() == 1 && !is_string($msg))
				return $logger->debug('{}', $msg);
			$args = func_get_args();
			$logger->logImpl(self::DEBUG, $msg, array_slice($args, 1));
		}
	}

	public static function tmp($msg) {
		if (isset($this))
			throw new IllegalStateException('Only use static call with this, to help cleaning afterward!');
		$logger = Logger::getLogger();
		if ($logger->isActive(self::DEBUG)) {
			if (func_num_args() == 1 && !is_string($msg))
				return $logger->debug('{}', $msg);
			$args = func_get_args();
			$logger->logImpl(self::DEBUG, $msg, array_slice($args, 1));
		}
	}

	/**
	 * Log the given message with the WARNING log level
	 *
	 * The $msg string parameter can contains placeholders of the form {} which
	 * will be replaced in order by the optional arguments.
	 *
	 * This method can be called either from an instanciated Logger obtained
	 * with the Logger::getLogger('ContextName') method, or statically from the
	 * Logger class. However, the first form should be preferred for log entries
	 * which which are intended to be kept in production code, in order to
	 * enable fine-grained configuration of context-dependant log levels.
	 *
	 * For best performance, as much as possible of the message formatting
	 * should be left to the logger (that is, should be done with the $msg
	 * param), and given arguments casting to string should be left to the
	 * logger (that is, being passed as-is).
	 *
	 * @param Const $level	the level at which the message will be logged
	 * @param String $msg	the formatted message string
	 * @param mixed $args,... optional arguments to sequently replace the {}
	 * placeholders
	 */
	public static function warn($msg) {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive(self::WARNING)) {
			$args = func_get_args();
			$logger->logImpl(self::WARNING, $msg, array_slice($args, 1));
		}
	}

	/**
	 * Log the given message with the ERROR log level
	 *
	 * The $msg string parameter can contains placeholders of the form {} which
	 * will be replaced in order by the optional arguments.
	 *
	 * This method can be called either from an instanciated Logger obtained
	 * with the Logger::getLogger('ContextName') method, or statically from the
	 * Logger class. However, the first form should be preferred for log entries
	 * which which are intended to be kept in production code, in order to
	 * enable fine-grained configuration of context-dependant log levels.
	 *
	 * For best performance, as much as possible of the message formatting
	 * should be left to the logger (that is, should be done with the $msg
	 * param), and given arguments casting to string should be left to the
	 * logger (that is, being passed as-is).
	 *
	 * @param Const $level	the level at which the message will be logged
	 * @param String $msg	the formatted message string
	 * @param mixed $args,... optional arguments to sequently replace the {}
	 * placeholders
	 */
	public function error($msg) {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive(self::ERROR)) {
			$args = func_get_args();
			$logger->logImpl(self::ERROR, $msg, array_slice($args, 1));
		}
	}

	/**
	 * Log the given message with the INFO log level
	 *
	 * The $msg string parameter can contains placeholders of the form {} which
	 * will be replaced in order by the optional arguments.
	 *
	 * This method can be called either from an instanciated Logger obtained
	 * with the Logger::getLogger('ContextName') method, or statically from the
	 * Logger class. However, the first form should be preferred for log entries
	 * which which are intended to be kept in production code, in order to
	 * enable fine-grained configuration of context-dependant log levels.
	 *
	 * For best performance, as much as possible of the message formatting
	 * should be left to the logger (that is, should be done with the $msg
	 * param), and given arguments casting to string should be left to the
	 * logger (that is, being passed as-is).
	 *
	 * @param Const $level	the level at which the message will be logged
	 * @param String $msg	the formatted message string
	 * @param mixed $args,... optional arguments to sequently replace the {}
	 * placeholders
	 */
	public static function info($msg) {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive(self::INFO)) {
			$args = func_get_args();
			$logger->logImpl(self::INFO, $msg, array_slice($args, 1));
		}
	}

	private function getAssertionLevel() {
		return self::ERROR;
	}

	public static function assert($observed, $expected, $msg = 'Expected: {}, Observed: {}') {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive(self::ASSERTION)) {
			if ($observed !== $expected) {
				$msg = 'ASSERTION FAILED -- ' . $msg;
				$args = func_get_args();
				$args = array_merge(array($expected, $observed), array_slice($args, 3));
				$logger->logImpl($logger->getAssertionLevel(), $msg, $args);
			}
		}
	}

	public static function assertTrue($expression, $msg = '') {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive(self::ASSERTION)) {
			if ($expression !== true) {
				$msg = 'ASSERTION FAILED -- ' . $msg;
				$args = func_get_args();
				$logger->logImpl($logger->getAssertionLevel(), $msg, array_slice($args, 2));
			}
		}
	}

	public static function assertFalse($expression, $msg = '') {
		$logger = isset($this) ? $this : Logger::getLogger();
		if ($logger->isActive(self::ASSERTION)) {
			if ($expression !== false) {
				$msg = 'ASSERTION FAILED -- ' . $msg;
				$args = func_get_args();
				$logger->logImpl($logger->getAssestionLevel(), $msg, array_slice($args, 2));
			}
		}
	}

}

// <!-- Logger

class LogEntry {

	public $msg, $level, $date, $context, $fileLine;

	function __construct($msg, $level, $date, $context, $fileLine) {
		$this->msg = $msg;
		$this->level = $level;
		$this->date = $date;
		$this->context = $context;
		$this->fileLine = $fileLine;
	}

	public function getLevelName() {
		return Logger::getLevelName($this->level);
	}

	public function formatDefaultLine() {
		if (strstr($this->msg, PHP_EOL) !== false) {
			// Multiple line message
			$lines = explode(PHP_EOL, $this->msg);
			if (substr($this->msg, -1) == PHP_EOL)
				$lines[] = PHP_EOL;
			$msgLength = 95 - strlen($this->context);
			$lines[0] = sprintf("[%-5s %s] %s: %-{$msgLength}s %s", self::getLevelName($this->level), $this->date, $this->context, $lines[0], $this->fileLine);
			return implode(PHP_EOL, $lines);
		} else {
			// Single line message
			$msgLength = 95 - strlen($this->context);
			return sprintf("[%-5s %s] %s: %-{$msgLength}s %s", self::getLevelName($this->level), $this->date, $this->context, $this->msg, $this->fileLine);
		}
	}

	public function __toString() {
		return $this->formatDefaultLine();
	}

}

/**
 * Interface to define an output destination for log entries.
 */
interface LoggerAppender {
	function process(LogEntry $entry);
}

/**
 * Appender writting entries to a log file.
 */
class LoggerFileAppender implements LoggerAppender {
	const MAX_LOG_FILE_SIZE = '5MB';

	private $logFile = null;
	private $filename;
	private $failedOpenFile = false;

	function __construct($filename = 'log.txt', $directory = null) {

		// Default directory
		if ($directory === null)
			$directory = LOG_PATH;
		$this->filename = $directory . $filename;
	}

	function __destruct() {
		$this->closeLogFile();
	}

	private function closeLogFile() {
		if ($this->logFile !== null && $this->logFile !== false) {
			fclose($this->logFile);
			$this->logFile = null;
		}
	}

	private function getLogFile() {
		if ($this->failedOpenFile)
			return false;
		if ($this->logFile === null) {

			if (file_exists($this->filename) && filesize($this->filename) 
					> FileHelper::filesizeToBytes(self::MAX_LOG_FILE_SIZE)) {
				// why I am authorized to delete the file, but not open it for 
				// writting ???
				@unlink($this->filename);
			}

			$this->logFile = @fopen($this->filename, 'a');

			if ($this->logFile === false) {
				$this->failedOpenFile = true;
				Logger::getLogger($this)->error(
						'Cannot open log file for writting: {}', $this->filename);
			}
		}
		return $this->logFile;
	}

	function process(LogEntry $entry) {
		if ($this->getLogFile() !== false) {
			fwrite($this->getLogFile(), $entry . PHP_EOL);
		}
	}

}

/**
 * Appender writting to the default output (console for CLI scripts, or the html
 * page).
 */
class LoggerOutputAppender implements LoggerAppender {

	const FORMAT_VOID  = 0;
	const FORMAT_HTML  = 1;
	const FORMAT_SHELL = 2;
	
	private $format;
	/** @var ShellEoze */
	private $shell;

	function __construct($format = self::FORMAT_HTML, ShellEoze $shell = null) {
		$this->shell = $shell;
		
		if ($format >= 0 && $format <= 2) {
			$this->format = $format;
		} else {
			throw new IllegalArgumentException(
				"Illegal value for \$format: $format"
			);
		}
	}

	function process(LogEntry $entry) {
		if ($this->format === self::FORMAT_HTML) {
			echo "<pre>$entry</pre>";
		} else if ($this->format === self::FORMAT_SHELL && $this->shell) {
			echo $this->shell->tag_string($entry, 'red');
		} else {
			echo $entry . PHP_EOL;
		}
	}

}

/**
 * Writter sending its log entries to the FirePHP console.
 */
class LoggerFirePHPAppender implements LoggerAppender {

	private $firephp;

	function __construct() {

		ob_start();

		$found = false;
		foreach (explode(':', get_include_path()) as $dir) {
			$filename = LIBS_PATH . 'FirePHPCore/FirePHP.class.php';
			if (file_exists($filename)) {
				require_once($filename);
				$found = true;
				break;
			}
		}

		if ($found) {
			$this->firephp = FirePHP::getInstance(true);
			$this->firephp->setOptions(array('includeLineNumbers' => false));
		} else {
			Logger::getLogger('LoggerFirePHPAppender')->error('FirePHP.class.php '
					. 'cannot be found in include path');
		}
	}

	function process(LogEntry $entry) {

		switch ($entry->level) {
			case Logger::INFO:
				$this->firephp->info("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
			case Logger::WARNING:
				$this->firephp->warn("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
			case Logger::ASSERTION:
			case Logger::ERROR:
				$this->firephp->error("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
			default:
				$this->firephp->log("{$entry->getLevelName()} {$entry->fileLine} -- {$entry->msg}");
				break;
		}
	}

}
