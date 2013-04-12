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

namespace eoko\cqlix\Model\Relation\Info;

use IllegalStateException;
use ModelRelationInfo;
use ModelRelationInfoHasMany;
use ModelRelationInfoHasOne;
use ModelRelationInfoHasReference;
use ModelRelationInfoIsRefered;
use ModelRelationInfoReferedByMany;
use ModelRelationInfoReferencesOne;
use ModelRelationReferencesOne;
use ModelTable;
use ModelTableProxy;

/**
 *
 * @category Opence
 * @package
 * @subpackage
 * @since 2013-04-16 12:14
 */
class Factory {

	/**
	 * @param ModelTable $table
	 * @param $spec
	 * @throws \IllegalArgumentException
	 * @throws \UnsupportedOperationException
	 * @return ModelRelationInfo
	 */
	public static function fromSpec(ModelTable $table, $spec) {

		$regex = implode('', array(
			'/',
			'^(?<name>[^\s]+)',
			'\s*:\s*',
			'(?<assoc>[^\s]+)',
			'\s*->\s*',
			'(?<target>[^\s]+)',
			'/',
		));

		if (preg_match($regex, $spec, $matches)) {

			$assocRelation = $table->getRelationInfoDeclaration($matches['assoc'], true);

			$assocTable = $assocRelation->getTargetTable();
			$assocTargetRelation = $assocTable->getRelationInfoDeclaration($matches['target'], true);

			if ($assocRelation instanceof ModelRelationInfoReferedByMany) {
				if ($assocTargetRelation instanceof ModelRelationInfoReferencesOne) {
					return BridgeManyToOne::create(array(
						'name' => $matches['name'],
						'localTable' => $table,
						'targetTable' => $assocTargetRelation->getTargetTableProxy(),
						'assocTable' => $assocRelation->getTargetTableProxy(),
						'localForeignKey' => $assocRelation->getReferenceFieldName(),
						'targetForeignKey' => $assocTargetRelation->getReferenceFieldName(),
						'assocRelation' => $assocRelation,
					));
				} else {
					throw new \UnsupportedOperationException('TODO');
				}
			} else {
				throw new \UnsupportedOperationException('TODO');
			}
		} else {
			throw new \IllegalArgumentException();
		}
	}
}
