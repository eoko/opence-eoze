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
	 * CometEvent instance. Can be set to false (in model child classes) to disable comet notifications
	 * for this model.
	 *
	 * @var CometEvents|bool
	 */
	protected $comet = null;

	/**
	 * @var CometEvents
	 */
	private static $defaultCometEvents = null;

	protected function __construct(&$fields, array $initValues = null, $strict = false, array $context = null) {
		parent::__construct($fields, $initValues, $strict, $context);

		if ($this->comet === null) {
			$this->comet = self::$defaultCometEvents;
		}
	}

	public static function setDefaultCometEvents(CometEvents $comet) {
		self::$defaultCometEvents = $comet;
	}

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
		if ($this->comet) {
			$id = $this->hasPrimaryKey() ? $this->getPrimaryKeyValue() : null;
			$origin = isset($this->context['keplerOrigin']) ? $this->context['keplerOrigin'] : null;
			$this->comet->publish($this->table, 'dataChanged', array($id), $origin);
			$this->comet->publish($this, 'removed', $origin);
			$this->comet->publish($this->table, 'removed', array($id), $origin);
		}
	}

	protected function afterSave($new) {
		parent::afterSave($new);
		if ($this->comet) {
			$id = $this->hasPrimaryKey() ? $this->getPrimaryKeyValue() : null;
			$origin = isset($this->context['keplerOrigin']) ? $this->context['keplerOrigin'] : null;
			$this->comet->publish($this->table, 'dataChanged', array($id), $origin);
			if ($new) {
				$this->comet->publish($this, 'created', $origin);
				$this->comet->publish($this->table, 'created', array($id), $origin);
			} else {
				$this->comet->publish($this, 'modified', $origin);
				$this->comet->publish($this->table, 'modified', array($id), $origin);
			}
		}
	}
}
