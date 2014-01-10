<?php

namespace eoko\cqlix\VirtualField;

use eoko\cqlix\Aliaser;
use VirtualFieldBase;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 22 déc. 2011
 */
abstract class AbstractVirtualField extends VirtualFieldBase {

	protected $alias = true;

	protected function doGetClause(\QueryAliasable $aliaser) {
		if (null !== $sql = $this->getSql($aliaser)) {
			return $aliaser->aliases($sql);
		} else {
			/** @noinspection PhpParamsInspection */
			return parent::doGetClause($aliaser);
		}
	}

	/**
	 * This method can be implemented instead of {@link doGetClause()}, and offers
	 * a more concise syntax for virtual field that just returns some SQL statement.
	 *
	 * The statement can contains Cqlix field names relative to the model owning the
	 * virtual field. These names will automatically be converted to fully qualified
	 * names; the `Aliaser` is provided only for more complex operations.
	 *
	 * E.g.
	 *
	 *     protected function getSql() {
	 *         return 'CONT(`ChildModel->id`);
	 *     }
	 *
	 * @param Aliaser $aliaser
	 * @return string
	 */
	protected function getSql(/** @noinspection PhpUnusedParameterInspection */
			Aliaser $aliaser) {
		return null;
	}
}
