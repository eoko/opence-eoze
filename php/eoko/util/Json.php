<?php

namespace eoko\util;

use eoko\log\Logger;

class Json {

	private static function utf8_encode_array($in) {
		$out = array();
        if (is_array($in)) {
			foreach ($in as $key => $value) {
				$out[utf8_encode($key)] = self::utf8_encode_array($value);
			}
		} else if (is_string($in)) {
			if (mb_detect_encoding($in) != "UTF-8")
				return utf8_encode($in);
			else
				return $in;
		} else {
			return $in;
		}
		return $out;
	}

	public static function encode($data, $indent = null) {
//		return urlencode(json_encode(self::utf8_encode_array($data)));
//		return json_encode(self::utf8_encode_array($data));
		if ($indent === false) {
			return json_encode(self::utf8_encode_array($data));
		} else if ($indent === true) {
			return self::indent(
				self::encode($data, false)
			);
		} else if ($indent === null) {
			return self::encode($data, Logger::get(get_called_class())->isActive(Logger::DEBUG));
		} else {
			return json_encode(self::utf8_encode_array($data));
		}
	}

	public static function decode($json, $assoc = true) {
		return json_decode(urldecode($json), $assoc);
//		return json_decode($json, $assoc);
	}


	/**
	 * Indents a flat JSON string to make it more human-readable.
	 *
	 * @param string $json The original JSON string to process.
	 *
	 * @return string Indented version of the original JSON string.

	 * @author http://recursive-design.com/blog/2008/03/11/format-json-with-php/
	 */
	private static function indent($json) {

		$result = '';
		$pos = 0;
		$strLen = strlen($json);
		$indentStr = '  ';
		$newLine = "\n";
		$prevChar = '';
		$outOfQuotes = true;

		for ($i = 0; $i <= $strLen; $i++) {

			// Grab the next character in the string.
			$char = substr($json, $i, 1);

			// Are we inside a quoted string?
			if ($char == '"' && $prevChar != '\\') {
				$outOfQuotes = !$outOfQuotes;

				// If this character is the end of an element, 
				// output a new line and indent the next line.
			} else if (($char == '}' || $char == ']') && $outOfQuotes) {
				$result .= $newLine;
				$pos--;
				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element, 
			// output a new line and indent the next line.
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
				$result .= $newLine;
				if ($char == '{' || $char == '[') {
					$pos++;
				}

				for ($j = 0; $j < $pos; $j++) {
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
	}

}
