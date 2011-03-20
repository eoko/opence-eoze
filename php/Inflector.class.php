<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

class Inflector {

	public static function explodeCamelCase($string, $split = '_') {
		if ($split != '') $string = implode(explode($split, $string));
		return preg_split('/(?<=\\w)(?=[A-Z])/', $string);
	}

	public static function capitalizeWords($source, $split = '_', $newSplit = '_') {

		if (is_array($source)) {
			$parts = $source;
		} else {
			$parts = explode($split, $source);
		}

		$r = '';
		foreach ($parts as $s) {
			if (strlen($s) > 0) {
				$r .= ucfirst($s) . $newSplit;
			}
		}
		return substr($r,0,-strlen($newSplit));
	}

	public static function camelCase($source, $capitalizeFirst = false, $split = '_') {
		$r = '';

		if (!is_array($source)) {
			$source = explode($split, $source);
		}

		if (count($source) < 1) throw new IllegalArgumentException('$source must contains at least one element');

		$r = $capitalizeFirst ? ucfirst($source[0]) : strtolower($source[0]);
		for ($i=1; $i<count($source); $i++) {
			$r .= ucfirst($source[$i]);
		}

		return $r;
	}

	public static function capitalizeCamelCase($string, $split = '_', $newSplit = '_') {
		return self::capitalizeWords(self::explodeCamelCase($string, $split),
				$split, $newSplit);
	}

	public static function plural($word) {
		$e1 = substr($word, -1);
		if ($e1 !== 's')
			return $word . 's';
		else {
			return $word;
		}
	}

	public static function pluralizeModel($modelName) {
		// TODO
//		if (substr($modelName, -2) == 'ys') return $modelName;
//		return $modelName . 's';
		return self::plural($modelName);
	}

	public static function modelFromDB($dbTableName) {

		if (substr($dbTableName, -2, 2) == 'ys') return self::camelCase($dbTableName, true);
		else return self::camelCase(rtrim($dbTableName, 's'), true);
	}

	public static function modelFromController($controller) {
		return self::modelFromDB($controller);
	}

	public static function tableFromDB($dbTableName) {
		return self::modelFromDB($dbTableName) . 'Table';
	}

	public static function tableFromModel($modelName) {
		return $modelName . 'Table';
	}

}

//echo Inflector::capitalizeWords('je_suis_le_roi_du_monde') . "\n";
//echo Inflector::capitalizeWords('je_suis_le_roi__du_monde', '_', ' ') . "\n";
//echo Inflector::capitalizeCamelCase('jeSuisLeRoiDuMondeA') . "\n";
//echo Inflector::capitalizeCamelCase('jeSuis_leRoiDu_mondeA') . "\n";
//echo Inflector::capitalizeCamelCase('jeSuis_leRoiDu__monde_A', '_', ' ') . "\n";
