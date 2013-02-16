<?php

namespace eoko\cqlix;

use eoko\modules\Kepler\CometEvents;
//use eoko\modules\Kepler\Observable as CometObservable;

/**
 * Intermediary {@link Model} class, intended to hold overridings that should
 * be plugins... 'till a good plugin system for Models exists.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
abstract class ExtendedModel extends Model {
	// ExtendedModel implements eoko\modules\Kepler\Observable, but we don't declare it
	// because some model may be created before the module class loader is set

	protected $cometEvents = true;

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

	protected function onDelete($isSaving) {
		parent::onDelete($isSaving);
		if ($this->cometEvents) {
			$id = $this->hasPrimaryKey() ? $this->getPrimaryKeyValue() : null;
			$origin = isset($this->context['keplerOrigin']) ? $this->context['keplerOrigin'] : null;
			CometEvents::publish($this->table, 'dataChanged', array($id), $origin);
			CometEvents::publish($this, 'removed', $origin);
			CometEvents::publish($this->table, 'removed', array($id), $origin);
		}
	}

	protected function afterSave($new) {
		parent::afterSave($new);
		if ($this->cometEvents) {
			$id = $this->hasPrimaryKey() ? $this->getPrimaryKeyValue() : null;
			$origin = isset($this->context['keplerOrigin']) ? $this->context['keplerOrigin'] : null;
			CometEvents::publish($this->table, 'dataChanged', array($id), $origin);
			if ($new) {
				CometEvents::publish($this, 'created', $origin);
				CometEvents::publish($this->table, 'created', array($id), $origin);
			} else {
				CometEvents::publish($this, 'modified', $origin);
				CometEvents::publish($this->table, 'modified', array($id), $origin);
			}
		}
	}
}
