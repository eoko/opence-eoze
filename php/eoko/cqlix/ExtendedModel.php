<?php

namespace eoko\cqlix;

use eoko\modules\Kepler\CometEvents;
use eoko\modules\Kepler\Observable as CometObservable;

/**
 * Intermediary {@link Model} class, intended to hold overridings that should
 * be plugins... 'till a good plugin system for Models exists.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
abstract class ExtendedModel extends Model implements CometObservable {
	
	/**
	 * Will return the model name, with the model id appended in the form:
	 * 
	 * `ModelName#id`
	 * 
	 * If the model is new, the hash character `#` will still be appended (e.g. 
	 * `ModelName#`).
	 * 
	 * If the model does not have a primary key, then the id part, including
	 * the hash will be omitted (e.g. `ModelName`).
	 * 
	 * @return string
	 */
	public function getCometObservableName() {
		$name = get_class($this);
		if ($this->hasPrimaryKey()) {
			$name .= '#';
			if (!$this->isNew()) {
				$name .= $this->getPrimaryKeyValue();
			}
		}
		return $name;
	}
}
