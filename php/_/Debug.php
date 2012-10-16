<?php

use eoko\database\Query;

/**
 * @author Éric Ortéga <eric@mail.com>
 */
class Debug {

	static function valueToReadable($v) {
		if (is_string($v)) return $v;
		else if ($v === null) return 'null';
		else if ($v === true) return 'true';
		else if ($v === false) return 'false';
		else return $v;
	}

	static function elapsedTime($start, $end) {
		list($usec1, $sec1) = explode(" ", $start);
		list($usec2, $sec2) = explode(" ", $end);
		$t1 = (float)$usec1 + (float)$sec1;
		$t2 = (float)$usec2 + (float)$sec2;
		return ($t2 - $t1);
	}

	static function randomString($length) {
		$r = '';
		for ($i = 0; $i < $length; $i++) {
			$d = rand(1, 30) % 2;
			$r .= $d ? chr(rand(65, 90)) : chr(rand(48, 57));
		}
		return $r;
	}

	static function getRandomExistingPrimaryKey(ModelTable $table,
			ModelTable $freeInTable = null, $andField = null) {

		$tableName = Query::quoteName($table->getDBTable());
		$pK = Query::quoteName($table->getPrimaryKeyName());

		if ($freeInTable === null) {
			$sql = 'SELECT ' . $pK . 'FROM ' . $tableName . ' WHERE ' . $pK
				. ' >= (SELECT FLOOR( MAX(' . $pK . ') * RAND()) FROM '
				. $tableName . ') ORDER BY ' . $pK . ' LIMIT 1';
		} else {
			$sql = "SELECT $pK FROM $tableName WHERE $pK >= (SELECT FLOOR( MAX($pK) "
				. "* RAND()) FROM $tableName) AND $pK NOT IN (SELECT `$andField` "
				. "FROM `{$freeInTable->getDBTable()}`) ORDER BY $pK LIMIT 1";
		}

		Logger::dbg('{}', $sql);

		$r = Query::executeQuery($sql);
		$r = $r->fetchColumn();

		Logger::dbg('$r: {}', $r);

		return $r;
	}

}