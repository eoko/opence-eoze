<?php

namespace eoko\cqlix\generator;
use \Inflector;
use \IllegalArgumentException, \IllegalStateException;

class NameMaker extends Inflector {

	private static $cache = array();
	private static $modelDB = array();
	private static $tableDB = array();

	public static function generateTableEntries($dbTableName) {

		$model = parent::modelFromDB($dbTableName);
		$table = parent::tableFromDB($dbTableName);

		self::$cache[$dbTableName] = array(
			'model' => $model,
			'plural_model' => parent::pluralizeModel($model),
			'table' => $table
		);

		self::$modelDB[$model] = $dbTableName;
		self::$tableDB[$table] = $dbTableName;
	}

	public static function modelFromDB($dbTableName) {
		if (!isset(self::$cache[$dbTableName])) {
			self::generateTableEntries($dbTableName);
		}
		return self::$cache[$dbTableName]['model'];
	}

	public static function pluralizeModel($modelName) {
		$dbTableName = self::dbFromModel($modelName);
		if (!isset(self::$cache[$dbTableName])) {
			self::generateTableEntries($dbTableName);
		}
		return self::$cache[self::dbFromModel($modelName)]['plural_model'];
	}
	
	public static function singular($name) {
		if (substr($name, -1) === 's') {
			return substr($name, 0, -1);
		} else if (preg_match('/\d$/', $name)) {
			return $name;
		} else {
			$knownSingulars = \eoko\config\ConfigManager::get('eoko\lang\Inflector\singulars');
			foreach ($knownSingulars as $word => $singular) {
				$word = preg_quote($word, '/');
				if (preg_match("/(?:\b|_)$word$/", $name)) {
					return $singular;
				}
			}
			throw new \Exception('Not implemented, singularize for: ' . $name);
		}
	}

	public static function isSingular($name) {
		if (preg_match('/(?:^|_)([^0-9_]+)\d*_?$/', $name, $matches) === 0) {
			throw new IllegalArgumentException("Invalid pattern: '$name'");
		} else {
			$word = $matches[1];
			$ad1 = substr($word, -1);
			if ($ad1 === 'x') {
				return false;
			} else if ($ad1 === 's') {
				return false;
			} else {
				return true;
			}
		}
	}

	public static function tableFromDB($dbTableName) {
		if (!isset(self::$cache[$dbTableName])) {
			self::generateTableEntries ($dbTableName);
		}
		return self::$cache[$dbTableName]['table'];
	}

	public static function tableFromModel($modelName) {
		$dbTableName = self::dbFromModel($modelName);
		if (!isset(self::$cache[$dbTableName])) {
			throw new IllegalStateException("$modelName => $dbTableName");
		}
		return self::$cache[$dbTableName]['table'];
	}

	public static function dbFromModel($modelName) {
		if (!isset(self::$modelDB[$modelName])) {
			throw new IllegalArgumentException($modelName);
		}
		return self::$modelDB[$modelName];
	}

	public static function dbFromTable($tableName) {
		if (!isset(self::$tableDB[$tableName])) {
			throw new IllegalArgumentException($tableName);
		}
		return self::$tableDB[$tableName];
	}

	public static function modelFromTable($tableName) {
		$dbTableName = self::dbFromTable($tableName);
		if (!isset(self::$cache[$dbTableName])) {
			throw new IllegalStateException("$tableName => $dbTableName");
		}
		return self::$cache[$dbTableName]['model'];
	}

	public static function generateAllEntries($dbTableNames) {
		foreach ($dbTableNames as $dbTableName => $table) self::generateTableEntries($dbTableName);
//		print_r(self::$cache);
//		print_r(self::$modelDB);
//		print_r(self::$tableDB);
//		die;
	}
}