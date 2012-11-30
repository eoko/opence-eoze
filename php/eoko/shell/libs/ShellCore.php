<?php

/**
 * Base class for Shells
 *
 * PHP versions 5
 *
 * Eoze© Framework : Rapid Development Framework (http://eoko-lab.fr)
 * Copyright 2010-2011, eoCloud , (http://eocloud.fr)
 *
 * Licensed under The GPL3 and MIT licence License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010-2011, eoCloud , Inc. (http://eoko-lab.com)
 * @link http://eoko-lab.com Eoko© Project
 * @package eoze
 * @subpackage eoze.shell.libs
 * @since Eoze v 0.00
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Base class for command-line utilities for automating programmer chores.
 * 
 * @author Romain DARY
 * @version 0.01b
 */


namespace eoko\shell\libs;

use eoko\shell\libs\ShellLib;

class ShellCore extends ShellLib {

	/**
	 * The application name.
	 * @var String
	 */
	public $appName = "No application name";

	/**
	 * The application description
	 * @var String
	 */
	public $appDesc = "No description";

	public static $useReadline = FALSE;
	public static $lnOnRead = TRUE;
	public static $captureEOT = TRUE;
	/** Manage history file Require readline extension */
	public static $historyFile = null;
	public static $historyMaxLen = 1000;
	/** unknown given args */
	private $unknownArgs = array();
	/** setted args on command line */
	private $settedArgs = array();
	/** args known by this class */
	private $knownArgs = array();
	private $required_args = array();

	/**
	 * Remove tag, set FALSE by default
	 * @var type boolean
	 */
	protected static $noColorTag = FALSE;

	//TODO add a custom error handler
	const LEVEL_ERROR = 0;
	const LEVEL_WARNING = 1;
	const LEVEL_NOTICE = 2;
	const LEVEL_MESSAGE = 99;


	// TODO add a curtom error handler
	protected static $messageType = array(
		0 => 'error',
		1 => 'warning',
		2 => 'notice',
		99 => 'message'
	);

	/**
	 * Not use yet... It's a way to create differents set colors for
	 * light or dark shell.
	 * @var type boolean
	 */
	protected $darkDisplay = FALSE;
	/**
	 * Some usefull Style
	 * @var type Array
	 */
	protected static $common_styles = array(
		'notice' => array('bold|bg_blue', 'INFO ', ''),
		'warning' => array('bold|bg_magenta', 'WARNING ', ' '),
		'error' => array('bold|bg_red', 'ERROR ', ' '),
		'message' => array('', '', ' ')
	);

	protected static $TextForegroundColors = array(
		'black' => 30,
		'red' => 31,
		'green' => 32,
		'brown' => 33,
		'blue' => 34,
		'magenta' => 35,
		'cyan' => 36,
		'grey' => 37
	);
	/**
	 * Some text background colors
	 * @var type Array
	 */
	protected static $TextBackgroundColors = array(
		'bg_black' => 40,
		'bg_red' => 41,
		'bg_green' => 42,
		'bg_brown' => 43,
		'bg_blue' => 44,
		'bg_magenta' => 45,
		'bg_cyan' => 46,
		'bg_white' => 47
	);
	protected static $TextEffects = array(
		'reset' => 0,
		'bold' => 1,
		'underline' => 4,
		'nounderline' => 24,
		'blink' => 5,
		'reverse' => 7,
		'normal' => 22,
		'blinkoff' => 25,
		'reverseoff' => 27
	);
	public $codes;

	public function __construct() {
		$this->codes = array_merge(self::$TextBackgroundColors, self::$TextForegroundColors, self::$TextEffects);

		self::$common_styles['help']['width'] = $this->getTtyWidth();
	}


	/**
	 * <p>Permit to use tag define in TextBackgroundColors, TextForegroundColors and TextEffects.</p>
	 * 
	 * <p>Examples
	 * <ul>
	 * 		<li>tag_string("My first example","blink")</li>
	 * 		<li>tag_string("Another example with output","blink|blue",TRUE)</li>
	 * </ul>
	 * </p>
	 * 
	 * @param type $string String to return
	 * @param type $tag Tags separate by a |
	 * @param type $stdOut
	 * @return string 
	 */
	public function tag_string($string, $tag, $stdOut=FALSE) {

//		var_dump($codes);
//		die();
		if (self::$noColorTag || empty($tag)) {
			$str = $string;
		} else {
			if (substr_count($tag, '|')) {
				$tags = explode('|', $tag);
				$str = '';
				foreach ($tags as $tag) {
					$str[] = isset($this->codes[$tag]) ? $this->codes[$tag] : 30;
				}
				$str = "\033[" . implode(';', $str) . 'm' . $string . "\033[0m";
			} else {
				if (in_array($this->codes[$tag], array(4, 5, 7))) {
					$end = "\033[2" . $this->codes[$tag] . 'm';
				} else {
					$end = "\033[0m";
				}
				$str = "\033[" . (isset($this->codes[$tag]) ? $this->codes[$tag] : 30) . 'm' . $string . $end;
			}
		}
		if ($stdOut) {
			self::stdout($str);
		}

		return $str;
	}

	/**
	 * Read user entries in the phpSTDIN
	 * @param string $string String to print
	 * @param string $dflt   valeur par defaut optionnelle,
	 *               si l'utilisateur ne saisie rien alors c'est la valeur par defaut qui sera retournée.
	 * @param bool   $check_exit if the user input exit on a read request the program default behaviour is to exit itelf
	 *                           you can disablle this feature by setting this to FALSE.
	 * return string
	 */
	public function read($string='Wait for input:', $dflt=null, $check_exit=TRUE) {
		$lnOnRead = ShellCore::$lnOnRead;
		$useReadline = ShellCore::$useReadline;
		$captureEOT = ShellCore::$captureEOT;
		if ($lnOnRead)
			$string .= "\n";
		if ($useReadline) {
			$read = readline($string);
			if ($read === FALSE) { // >> capture EoT (CTRL+D)
				if ($captureEOT)
					exit();
				return FALSE;
			}
		}else {
			if ($string)
				$this->stdout($string);
			$read = fgets(STDIN, 4096);
			if (!strlen($read)) { // >> capture EoT (CTRL+D)
				if ($captureEOT)
					exit();
				else
					return FALSE;
			}
			# strip newline
			$read = preg_replace('![\r\n]+$!', '', $read);
		}

		if ($check_exit && $read == 'exit') {
			$this->msg_('Execution has been stopped by the user.', 2);
			exit(0);
		}

		# check default value 
		if ((!strlen($read)) && !is_null($dflt))
			return $dflt;
		if ($useReadline)
			readline_add_history($read);
		return strlen($read) ? $read : false;
	}

	/**
	 * This function will display a message error and stop it if necessary
	 * @param string $msg Message to put out
	 * @param bool   $fatal Stop the script if set to yes
	 * @param int    $exitcode Code to return on fatal error (-1 is default)
	 * @return $this
	 */
	public function msg_($msg, $type = 99, $newline = TRUE, $fatal=FALSE, $exitcode=-1) {

		list($tag, $prefx, $sufx) = self::$common_styles[self::$messageType[$type]];
		$out;

		switch ($type) {
			case 0 :
				$out = $this->tag_string("\n " . $prefx . ($fatal ? ' FATAL ' : ' ') . "| " . time() . ' ', $tag);
				$out .= $this->tag_string(' ' . $msg, '');
				break;
			case 1 :
				$out = $this->tag_string("\n " . $prefx . "| " . time() . ' ', $tag);
				$out .= $this->tag_string(' ' . $msg, '');
				break;
			case 2 :
				$out = $this->tag_string("\n " . $prefx . "| " . time() . ' ', $tag);
				$out .= $this->tag_string(' ' . $msg, '');
				break;
			case 99 :
				$out = $this->tag_string($msg, "");
				break;
			default :
				break;
		}


		if ($fatal)
			exit($exitcode);
		echo $out . ($newline ? " \n" : "");
		return $this;
	}

	/**
	 * This function will clear the current screen
	 */
	public function clear_screen() {
		$this->stdout("\033[2J");
	}

	public function display_help($exitcode=0) {
		$max_len = 0;
		$appname = ShellCore::tag_string(basename($_SERVER['SCRIPT_NAME']), 'bold|blue');
		if (strlen($this->appName))
			$this->stdout(wordwrap("$this->appDesc", self::$common_styles['help']['width']) . "\n");
		$this->stdout("-h,--help display this help\n");
		$i = 0;
		# Display help for args
		if (count($this->knownArgs)) {
			ksort($this->knownArgs);
			$rows[$i] = "\n### OPTIONS LIST FOR $appname ###\n";
			foreach ($this->_args as $argname => $arg) {
				$rows[++$i][0] = (isset($arg['short']) ? '-' . $arg['short'] . ', ' : '') . "--$argname";
				$rows[$i][1] = (isset($arg['dflt']) ? "(Default value: '$arg[dflt]') " : '') . $arg['desc']
						. ((isset($arg['delim']) && strlen($arg['delim'])) ? " multiple values can be separated by '$arg[delim]'" : '');
				if ((!is_array($this->required_args)) || (!in_array($argname, $this->required_args)))
					$rows[$i][0] = "[" . $rows[$i][0] . "]";
				$max_len = max($max_len, strlen($rows[$i][0]));
				$parsed_arg[$arg['longname']] = TRUE;
			}
		}
		$max_len +=4;
		$split = self::$common_styles['help']['width'];
		$blank = str_repeat(' ', $max_len);
		$desclen = max(10, $split - $max_len);


		foreach ($rows as $row) {
			if (is_string($row)) {
				$this->stdout($row);
				continue;
			}
			list($col1, $col2) = $row;

			$this->stdout($col1 . str_repeat(' ', max(0, strlen($blank) - strlen($col1))), FALSE);

			while (strlen($col2) > $desclen) {
				if (($lnpos = strpos($col2, "\n")) !== false && $lnpos < $desclen) {
					$this->stdout(substr($col2, 0, $lnpos) . "\n$blank");
					$col2 = substr($col2, $lnpos + 1);
					continue;
				}
				$last_isspace = (bool) preg_match('!\s!', $col2[$desclen - 1]);
				$next_isspace = (bool) preg_match('!\s!', $col2[$desclen]);
				$next2_isspace = (bool) (isset($col2[$desclen + 1]) ? preg_match('!\s!', $col2[$desclen + 1]) : TRUE);
				if ($last_isspace) {
					$this->stdout(substr($col2, 0, $desclen) . "\n$blank");
					$col2 = substr($col2, $desclen);
				} elseif ($next_isspace) {
					$this->stdout(substr($col2, 0, $desclen) . "\n$blank");
					$col2 = substr($col2, $desclen + 1);
				} elseif ($next2_isspace) {
					$this->stdout(substr($col2, 0, $desclen + 1) . "\n$blank");
					$col2 = substr($col2, $desclen + 2);
				} else {
					$this->stdout(substr($col2, 0, $desclen) . "-\n$blank");
					$col2 = substr($col2, $desclen);
				}
			}
			$this->stdout($col2 . "\n");
		}
		if ($exitcode !== false)
			exit($exitcode);
	}

	/**
	 * Outputs to the stdout php.
	 *
	 * @param string $s String to output.
	 * @param boolean $newLine If true, this will add a newline.
	 * @return $this
	 * @access public
	 */
	public function stdout($s, $newLine = FALSE) {
		if ($newLine) {
			fwrite(STDOUT, $s . "\n");
			return $this;
		} else {
			fwrite(STDOUT, $s);
			return $this;
		}
	}

	public static function moveUp($nbline=1, $clear=false) {
		if ($nbline > 1 && $clear) {
			# clear multiple lines one by one
			for ($i = 0; $i < $nbline; $i++)
				fwrite(STDOUT, "\033[1A\033[K");
		} else {
			fwrite(STDOUT, "\033[" . $nbline . 'A' . ($clear ? "\033[K" : ''));
		}
	}

	public static function moveDown($nbline=1, $clear=false) {
		if ($nbline > 1 && $clear) {
			# clear multiple lines one by one
			for ($i = 0; $i < $nbline; $i++)
				fwrite(STDOUT, "\033[1B\033[K");
		} else {
			fwrite(STDOUT, "\033[" . $nbline . 'B' . ($clear ? "\033[K" : ''));
		}
	}

	/**
	 * set and display a progress bar
	 * @param mixed  $val int/float value relative to $max
	 * @param string $msg message to display with the bar (you can either pass an array(string msg,string tag))
	 * @param int    $w   length of the bar (in character)
	 * @param mixed  $max int/float maximum value to display
	 * @param string $formatString is a string to custom the display of your progress bar
	 *                             this are the replacement made in the string before displaying:
	 *                             %V will be replaced by the value
	 *                             %S will be replaced by the msg
	 *                             %M will be replaced by the maxvalue
	 *                             %B will be replaced by the bar
	 *                             %P will be replaced by the percent value
	 * @param array $style   permit you to custom the bar style array(done_style,todo_style)
	 *                       where (todo|done)_style can be a string (character) or an array( (str) char,(str) tag as in tagged_string)
	 * @param bool  $refresh set this to true if you want to erase last printed progress 
	 *                       (you mustn't have any output between this call and the last one to get it work properly)
	 *                       normaly you won't have to use this, use the helper methods refresh_progress_bar().
	 */
	public function progress_bar($val, $msg=null, $max=100, $w=60, $formatString="%S\n\t%B %P %V/%M", $style=array(array('|', 'green'), array('•', 'red')), $refresh= TRUE) {
		static $pgdatas;
		$args = array('val', 'msg', 'w', 'max', 'formatString', 'style');
		foreach ($args as $arg) {
			if (is_null($arg)) # set null args to previous values 
				$arg = isset($pgdatas[$arg]) ? $pgdatas[$arg] : null;
			else # keep trace for next call
				$pgdatas[$arg] = $arg;
		}
		# clear previously displayed bar
		if ($refresh && isset($pgdatas['nbline'])) {
			self::moveUp($pgdatas['nbline'], true);
		}
		# calc some datas
		$good = max(0, round($val * $w / $max));
		$bad = max(0, $w - $good);

		# make some style
		list($done_chr, $todo_chr) = $style;
		if (is_array($done_chr))
			list($done_chr, $done_tag) = $done_chr;
		if (is_array($todo_chr))
			list($todo_chr, $todo_tag) = $todo_chr;
		$good = str_repeat($done_chr, $good);
		$bad = str_repeat($todo_chr, $bad);
		if (isset($done_tag))
			$good = $this->tag_string($good, $done_tag);
		if (isset($todo_tag))
			$bad = $this->tag_string($bad, $todo_tag);

		# then render the bar
		if (is_array($msg))
			$msg = $this->tag_string($msg[0], $msg[1]);
		$bar = '[' . $good . $bad . ']';
		$per = round($val / $max * 100, 1);
		$str = str_replace(array('%V', '%M', '%S', '%B', '%P'), array($val, $max, $msg, $bar, "$per%"), $formatString) . "\n";
		$pgdatas['nbline'] = substr_count($str, "\n");
		fwrite(STDOUT, $str);
	}

	/**
	 * helper methods to refresh a progress bar
	 * @param mixed  $value     int/float current value relative to $max
	 * @param string $msg       message to display if you want to replace the old one.
	 * @param bool   $dontclean if set to TRUE then won't replace previous displayed bar but just print at the end of the script
	 */
	public static function refresh_progress_bar($value, $msg=null, $dontclean=FALSE) {
		$this->progress_bar($value, $msg, null, null, null, null, !$dontclean);
	}

	/**
	 * Remove all tag in Shell
	 * @param type $noColorTag
	 * @return type 
	 */
	public static function discreetMode($noColorTag = TRUE) {
		self::$noColorTag = $noColorTag;
		return TRUE;
	}

	/**
	 * set possible arg for this application
	 * @param str    $lName    this is the name you will use to access this arg inside your application.
	 *                            the program may so have a --longname argument
	 * @param str    $sName   set an optionnal short name for arg, so your app will take a -S arg (S for shortname :))
	 * @param str    $default     optionnal default value to set this arg to, if not passed on the command line
	 * 														 Setting a default value (!== null) will mark this arg as optionnal else it will be a required arg
	 * @param str    $desc       set the description for this argument used for the --help command
	 * @param mixed  $valid_cb   You can use a callback function to check your argument at the start time
	 *                           such function will receive the value given to arg by user on the command line
	 *                           the callback func must return either: FALSE -> so program will display an error and exit
	 *                                                                 TRUE or null -> nothing happen all is ok
	 *                                                                 mixed -> the value will be replaced by the returned mixed
	 * @param str    $delim      delimiter used to explode multiple value agurment
	 */
	public function defineArg($lName, $sName=null, $default=null, $desc='** no description available **', $valid_cb=null, $delim=null) {
		$this->knownArgs['--' . $lName] = $lName;
		$this->_args[$lName] = array('longname' => $lName, 'desc' => $desc);
		if (!is_null($valid_cb))
			$this->_args[$lName]['validation_callback'] = $valid_cb;
		if (!is_null($delim))
			$this->_args[$lName]['delim'] = $delim;
		if (is_null($default)) {
			$this->required_args[] = $lName;
		} else {
			$this->_args[$lName]['dflt'] = $default;
			$this->settedArgs[$lName] = $default;
		}
		if (!is_null($sName)) {
			$this->_args[$lName]['short'] = $sName;
			$this->knownArgs['-' . $sName] = $lName;
		}
	}

	/**
	 * parse command line parameters.
	 * You must call this method after args definition and before any ShellCore::get_arg() call
	 */
	public function parseArgs() {
		$argv = $_SERVER['argv'];
		$argc = $_SERVER['argc'];
		# si pas d'argument on retourne 
		if (!$argc > 1)
			return FALSE;
		# we parse each args
		for ($i = 1; $i < $argc; $i++) {
			$arg = $argv[$i];
			if (in_array($arg, array('--help', '-h'))) # check for help 
				return $this->display_help(0);

			if (substr($arg, 0, 1) != '-') { # not a arg
				$this->unknownArgs[] = $arg;
				continue;
			}

			if (isset($this->knownArgs[$arg])) { # Known argument so we process it
				$name = $this->knownArgs[$arg]; # get arg name
				# get his value
				if (!isset($argv[$i + 1]))
					continue;
				if (!isset($this->_args[$name]['delim']))# unique value entry
					$this->settedArgs[$name] = isset($argv[++$i]) ? $argv[$i] : FALSE;
				else # multiple value argument 
					$this->settedArgs[$name] = split($this->_args[$name]['delim'], $argv[++$i]);
				if (isset($this->_args[$name]['validation_callback'])) { # run optionnal validation callback
					$cb_ret = call_user_func($this->_args[$name]['validation_callback'], $this->settedArgs[$name]);
					if ($cb_ret === FALSE) { # callback failed so display error message and then help
						ShellCore::tagged_string("** '" . $arg . ' ' . $argv[$i] . "' Invalid value given for $name **", 'red|bold', 1);
						return $this->display_help(-1);
					} elseif (!in_array($cb_ret, array(TRUE, NULL), TRUE)) { # callback returned a value so we override user value with this one
						$this->settedArgs[$name] = $cb_ret;  # get the args value
					}
				}
			} else { # unknown args
				$_arg = substr($arg, 1);
				if (strlen($_arg)) {
					ShellCore::tag_string("\n ** undefined parameter $arg **", 'red', 1);
					return $this->display_help(-1);
				}
			}
		}
		if (is_array($this->required_args))
			foreach ($this->required_args as $arg) {
				if (!isset($this->settedArgs[$arg]))
					$this->msg_(" ** Missing required $arg parameter (" . ($this->_args[$arg]['short'] ? '-' . $this->_args[$arg]['short'] . ', ' : '') . "--$arg)**", 0);
			}
	}

	public function get_arg($longname) {
		# try args
		if (isset($this->settedArgs[$longname]))
			return $this->settedArgs[$longname];
		if (isset($this->unknownArgs[$longname]))
			return $this->unknownArgs[$longname];
		return FALSE;
	}

	public function get_args() {
		return array_merge($this->settedArgs, $this->unknownArgs);
	}

	//TODO Add custom exec
	public $customCmd = array();

	public function addExec($name, $cmd, $description) {
		array_push($this->customCmd, array('name' => $name, 'command' => $cmd, 'description' => $description));
		return TRUE;
	}

	public function setAppName($appName) {
		$this->appName = $appName;
	}

	public function setAppDesc($appDesc) {
		$this->appDesc = $appDesc;
	}

}

$a = new ShellCore();

