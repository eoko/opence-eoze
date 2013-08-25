<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

namespace eoko\i18n;

use \Logger;
use \IllegalArgumentException;
use eoko\util\Arrays;

class FillersReplacer {

	protected $fillers;

	function __construct($fillers) {
		if (!is_array($fillers)) throw new IllegalArgumentException();
		$this->fillers = $fillers;
	}
}

class NamedFillersReplacer extends FillersReplacer {

	function __construct($fillers) { parent::__construct($fillers); }

	function replace($match) {

		if (!isset($this->fillers[$match[1]])) {

			Logger::getLogger('Language')->warn('Missing filler {} in translated string "{}"',
					$match[0], $text);

			return $match[0];
		} else {
			return $this->fillers[$match[1]];
		}
	}
}

class OrderedFillersReplacer extends FillersReplacer {

	private $i = 0;

	function __construct($fillers) { parent::__construct($fillers); }

	function replace($match) {

		if (!isset($this->fillers[$this->i])) {

			$this->i++;

			Logger::getLogger('Language')->warn('Missing filler {} in translated string "{}"',
					$match[0], $text);

			return $match[0];
		} else {
			return $this->fillers[$this->i++];
		}
	}
}

class PluralizerReplacer {

	protected $plural;

	function __construct($plural) {
		$this->plural = $plural;
	}

	function replace($match) {
		if (preg_match('@::([^:]+)/([^:]+):@', $match[0], $m)) {
			if ($this->plural) return $m[2];
			else return $m[1];
		} else {
			if ($this->plural) return $match[1];
			else return null;
		}
	}
}

class Language {

	private static function translateNamed($text, $fillers) {
		$replacer = new NamedFillersReplacer($fillers);
		$text = self::getTranslatedString($text);
		return preg_replace_callback('/%([^%]+)%/', array($replacer, 'replace'), $text);
	}

	private static function translateOrdered($text, $fillers) {
		if (preg_match('/::([^:]+):/', $text)) {
			$reference = count($fillers) == 1 ? $fillers : array_shift($fillers);
			if (is_array($reference)) $reference = count($reference);
			$replacer = new PluralizerReplacer($reference > 1);
			$text = preg_replace_callback('/::([^:]+):/', array($replacer, 'replace'), $text);
		}
		$replacer = new OrderedFillersReplacer($fillers);
		$text = self::getTranslatedString($text);
		return preg_replace_callback('/%([^%]+)%/', array($replacer, 'replace'), $text);
	}

	private static function getTranslatedString($key) {
		return $key;
	}

	public static function callTranslate($args) {
		$text = \array_shift($args);
		$n = count($args);
		if ($n > 1) {
			return self::translateOrdered($text, $args);
		} else if ($n === 1) {
			$arg = $args[0];
			if (is_array($arg) && Arrays::isAssocArray($arg)) {
				return self::translateNamed($text, $args);
			} else {
				return self::translateOrdered($text, $args);
			}
		} else if ($n === 0) {
			return self::getTranslatedString($text);
		} else {
			throw new IllegalStateException('Unreachable code');
		}
	}

	public static function importFunctions($ns) {
		eval(
<<<CODE
namespace $ns {
	if (!function_exists('lang')) {
		function lang() { return eoko\i18n\Language::callTranslate(func_get_args()); }
	}
}
CODE
		);
	}

	public static function translate($text, $___ = null) {

		if (func_num_args() > 2) {
			$fillers = array_splice(func_get_args(), 1);
			return Language::translateOrdered($text, $fillers);
		} else if (is_array($___) && ArrayHelper::isAssoc($___)) {
			$fillers = $___;
			return Language::translateNamed($text, $fillers);
		} else if ($___ === null) {
			return Language::getTranslatedString($text);
		} else {
			$fillers = array($___);
			return Language::translateOrdered($text, $fillers);
		}

		return Language::translate($text, $fillers);
	}

}

//function lang($text, $___ = null) {
//
//	if (func_num_args() > 2) {
//		$fillers = array_splice(func_get_args(), 1);
//		return Language::translateOrdered($text, $fillers);
//	} else if (is_array($___) && ArrayHelper::isAssoc($___)) {
//		$fillers = $___;
//		return Language::translateNamed($text, $fillers);
//	} else if ($___ === null) {
//		return Language::getTranslatedString($text);
//	} else {
//		$fillers = array($___);
//		return Language::translateOrdered($text, $fillers);
//	}
//
//	return Language::translate($text, $fillers);
//}

//// Test
//echo "\n";
//echo lang('test %num% et %num2%', 11);
//echo lang('test %num% et %num2%', 11, 22);
//echo lang('test %num% et %num2%', array('num' => 333, 'num2' => 'myNUM2'));
//echo lang('test %num%', array('num' => 333));
//echo "\n\n";
