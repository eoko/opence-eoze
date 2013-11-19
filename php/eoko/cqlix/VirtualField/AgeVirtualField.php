<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\cqlix\VirtualField;

use eoko\cqlix\Aliaser;
use eoko\database\Database;
use ModelColumn;
use QueryAliasable;
use VirtualFieldBase;

/**
 *
 * @since 2013-10-02 13:01
 */
class AgeVirtualField extends VirtualFieldBase {

	protected $dateField, $alias;

	function __construct($dateField, $alias = 'age') {
		parent::__construct($alias);
		$this->dateField = $dateField;
	}

	public function getType() {
		return ModelColumn::T_STRING;
	}

	public function isNullable() {
		return true;
	}

	public function getSortClause($dir, Aliaser $aliaser) {
		// Invert dir, because date is growing in the opposite direction compared to age
		$dir = $dir === 'DESC' ? 'ASC' : 'DESC';
		return parent::getSortClause($dir, $aliaser);
	}

	public function getDateField(Aliaser $aliaser) {
		return $aliaser->alias($this->dateField);
	}

	// Age virtual field is just for display... Sorting on this field would
	// result in sorting alphabetically, not chronologically.
	// ... So let's sort on the real date field, instead.
	protected function doGetSortClause(Aliaser $aliaser) {
		return $aliaser->alias($this->dateField);
	}

	protected function doGetClause(QueryAliasable $aliasable) {
		$dateField = $aliasable->getQualifiedName($this->dateField);

		$context = $aliasable->getQuery()->getContext();
		$now = !empty($context['date'])
			? Database::getDefaultConnection()->quote($context['date'])
			: 'CURRENT_DATE()';

		return <<<SQL
CONVERT(IF($now < $dateField, "Pas encore né",
CONCAT(IF((@years := (YEAR($now) - YEAR($dateField)
- (@postBD := (DATE_FORMAT($now, '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(0, '', CONCAT(
IF((@months := FLOOR((@days := DATEDIFF($now,DATE_FORMAT($dateField,
CONCAT(YEAR($now) - @postBD,'-%m-%d')))) / 30.4375)) > 0
,CONCAT(' ',@months,' mois'),''),IF((@days := FLOOR(MOD(@days, 30.4375))) < 1, '', CONCAT(' '
,@days,CONCAT(' jour',IF(@days>1,'s',''))
)))))) USING utf8)
SQL;
//		return <<<SQL
//(SELECT( CONVERT(CONCAT(IF((@years := (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')
//- (@postBD := (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
//)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(@years > 18, '', CONCAT(
//IF((@months := FLOOR((@days := DATEDIFF(NOW(),DATE_FORMAT($dateField,
//CONCAT(YEAR(CURRENT_DATE()) - @postBD,'-%m-%d')))) / 30.4375)) >= 0
//,CONCAT(' ',@months,' mois'),''),IF(@years >= 3, '', CONCAT(' '
//,(@days := FLOOR(MOD(@days, 30.4375))),CONCAT(' jour',IF(@days>0,'s',''))
//))))) USING utf8) ))
//SQL;
//		return <<<SQL
//(SELECT( CONVERT(CONCAT(IF((@years := (DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT($dateField, '%Y')
//- (@postBD := (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT($dateField, '00-%m-%d')))
//)) > 0,CONCAT(@years,' an',IF(@years>1,'s','')),''),IF(@years >= 21, '', CONCAT(
//IF((@months := FLOOR((@days := DATEDIFF(NOW(),DATE_FORMAT($dateField,
//CONCAT(YEAR(CURRENT_DATE()) - @postBD,'-%m-%d')))) / 30.4375)) >= 0
//,CONCAT(IF(@year>1,' ',''),@months,' mois'),''),IF(@years >= 3, '', CONCAT(' '
//,(@days := FLOOR(MOD(@days, 30.4375))),CONCAT(' jour',IF(@days>0,'s',''))
//))))) USING utf8) ))
//SQL;
	}
}
