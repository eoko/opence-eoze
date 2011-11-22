<?php

use eoko\config\ConfigManager;
use eoko\lang\Inflect;

/**
 * @author Éric Ortéga <eric@planysphere.fr>
 */
class Inflector {

	public static function explodeCamelCase($string, $split = '_') {
		if ($split) {
			$string = implode(explode($split, $string));
		}
		return preg_split('/(?<=\\w)(?=[A-Z])/', $string);
	}
	
	/**
	 * Converts a camelCasedString to an underscored_separated_string.
	 * 
	 * @param string $string
	 * @param string $glue
	 * @param string $split
	 * @return string
	 */
	public static function camelCaseToUnderscored($string, $glue = '_') {
		return strtolower(implode($glue, self::explodeCamelCase($string, false)));
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
		$plural = Inflect::pluralize($word);
		if (strtolower($word) === $word) {
			return $plural;
		} else if (ucfirst($word) === $word) {
			return ucfirst($plural);
		} else {
			// TODO
			throw new UnsupportedOperationException('TODO');
			dump($word);
			dump_trace();
		}
	}

	public static function pluralizeModel($modelName) {
		// TODO
//		if (substr($modelName, -2) == 'ys') return $modelName;
//		return $modelName . 's';
		return self::plural($modelName);
	}
	
	private static $modelsConfig = null;
	
	private static function getTableConfig($table, $key = null, $default = null) {
		if (self::$modelsConfig === null) {
			self::$modelsConfig = ConfigManager::get(
				ConfigManager::get('eoze/application/namespace') . '/cqlix/models'
			);
		}
		if (isset(self::$modelsConfig[$table][$key])) {
			return self::$modelsConfig[$table][$key];
		} else {
			return $default;
		}
	}

	public static function modelFromDB($dbTableName) {
		if (null !== $name = self::getTableConfig($dbTableName, 'modelName')) {
			return $name;
		} else {
			$string = str_replace('_' , ' ', $dbTableName);
			return self::camelCase(Inflect::singularize($string), true, ' ');
		}
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
