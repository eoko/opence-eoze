<?php
/**
 * @package PS-ORM-1
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

/**
 * Represent the information about a concrete relation bound to a specific
 * data reccord. Generic informations about the relation category are
 * represented by {@link ModelRelationInfo}.
 */
abstract class ModelRelation {

	const HAS_ONE           = 1;
	const HAS_MANY          = 256;

	const E_ADDED = 'mr_add';
	const E_MODIFIED = 'mr_mod';
	const E_DELETED = 'mr_del';

	/** @var ModelRelationInfo */
	protected $info;

	/** @var Model */
	protected $parentModel = null;

	/** @var String */
	public $name;
	/** @var ModelTable */
	protected $localTable;
	/** @var ModelTable */
	protected $targetTable;

	/** @var ModelRelationCache */
	protected $cache;

	protected function __construct(ModelRelationInfo $info, Model $parentModel) {

		$this->parentModel = $parentModel;

		$this->cache = new ModelRelationCache($this->parentModel->context);

		$this->info = $info;
		$this->name = $info->getName();
		$info->getLocalTableProxy()->attach($this->localTable);
		$info->getTargetTableProxy()->attach($this->targetTable);
	}

	public function reset() {
		$this->cache = new ModelRelationCache($this->parentModel->context);
	}

	public function __toString() {
		return get_class($this) . '{' . $this->name . '}';
	}

	public function getName() {
		return $this->name;
	}

	/**
	 * Get the {@link Model} or {@link ModelResultSet} pointed to by this
	 * relation.
	 */
	public function get(array $overrideContext = null) {
		if ($this instanceof ModelRelationHasOne) {
			return $this->getAsModel(
				false,
				$overrideContext
			);
		} else {
			throw new UnsupportedOperationException("$this::get()");
		}
	}

	public function set($value, $forceAcceptNull = false) {
		// legacy
		if (func_num_args() === 3) {
			Logger::get($this)->warn('Deprecated usage');
			list($callingModel, $value, $forceAcceptNull) = func_get_args();
		} else if (func_num_args() === 2) {
			if (!is_bool($forceAcceptNull)) {
				Logger::get($this)->warn('Deprecated usage');
				$value = $forceAcceptNull;
				$forceAcceptNull = false;
			}
		}

		if ($value !== null // explode(',', null) => array(0 => 0) => problem!
				&& $this instanceof ModelRelationInfoHasMany
				&& !is_array($value)) {

			$value = explode(',', $value);
			foreach ($value as &$v) {
				$v = (int) trim($v);
			} unset($v);
		}

		$this->doSet($value, $forceAcceptNull);
	}

	// TODO: make these abstract
	protected function doSet($value, $forceAcceptNull = false) {
//	public function set(Model $callingModel, $value, $forceAcceptNull = false) {
		if ($this instanceof ModelRelationHasOne) {
			if ($value instanceof Model) {
				$this->setFromModel($value);
			} else if (is_array($value)) {
				if (count($value) === 1 
						&& array_key_exists($this->targetTable->getPrimaryKeyName(), $value)) {
					$this->setFromId($value[$this->targetTable->getPrimaryKeyName()]);
				} else {
					$this->setFromModel(
						$this->targetTable->createModel($value, false, $this->parentModel->context)
					);
				}
			} else {
				$this->setFromId($value, $forceAcceptNull);
			}
		} else if ($this instanceof ModelRelationHasMany) {
			throw new UnsupportedOperationException();
//			if ($value === null) {
//				throw new IllegalArgumentException('$value cannot be NULL');
//			} else if (!is_array($value)) {
//				throw new IllegalArgumentException('$value must be an array');
//			} else {
//
//			}
		} else {
			throw new UnsupportedOperationException("$this::set()");
		}
	}
	public function setFromId($id, $forceAcceptNull = false) {
		throw new UnsupportedOperationException("$this::setFromId()");
	}
	public function setFromModel(Model $model) {
		throw new UnsupportedOperationException("$this::setFromModel()");
	}
	public function getAsId() {
		throw new UnsupportedOperationException(
			get_class($this) . "::getAsId()"
		);
	}

	public final function getAsModel($createIfNone = false, array $overrideContext = null) {
		if ($overrideContext !== null) {
			ArrayHelper::applyIf($overrideContext, $this->parentModel->context);
		}
		return $this->doGetAsModel($createIfNone, $overrideContext);
	}
	protected function doGetAsModel($createIfNone = false, array $overrideContext = null) {
		throw new UnsupportedOperationException("$this::doGetAsModel()");
	}

	public function save() {
		throw new UnsupportedOperationException(
			get_class($this) . '::save()'
		);
	}
	public function setField($fieldName, $value, $forceAcceptNull = false) {
		throw new UnsupportedOperationException("$this::setField()");
	}

	/**
	 * @return myModelTable
	 */
	public function getLocalTable() {
		return $this->localTable;
	}

	/**
	 * @return myModelTable
	 */
	public function getTargetTable() {
		return $this->targetTable;
	}

	public function select(Query $query, $targetFields = null, $targetAliases = null) {
		throw new UnsupportedOperationException();
	}

}

/**
 * This utility class is used to cache relation's values retrieved fromt the
 * database on a per-context basis.
 */
class ModelRelationCache {

	/** @var array */
	protected $defaultContext;
	protected $defaultContextHash = null;
	protected $defaultContextValue = null;

	protected $cache = null;

	public function __construct(array $context = null) {
		$this->defaultContext = $context !== null ? $context : array();
	}

	public function &get(array $overrideContext = null) {
		if ($overrideContext === null) {
			return $this->defaultContextValue;
		}
		// Make/cache default context hash
		if ($this->defaultContextHash === null) {
			$this->defaultContext = ksort($this->defaultContext);
			$this->defaultContextHash = serialize($this->defaultContext);
		}
		// Compare with overriden context
		ksort($overrideContext);
		$hash = serialize($overrideContext);
		if ($hash === $this->defaultContextHash) {
			return $this->defaultContextValue;
		}
		if ($this->cache !== null && !array_key_exists($hash, $this->cache)) {
			// If the value is new, we set it to null, so that the returned
			// value can be updated by the caller
			$this->cache[$hash] = null;
		}
		return $this->cache[$hash];
	}

	public static function compareContext(array $c1 = null, array $c2 = null) {
		if ($c1 === null) {
			return $c2 === null;
		} else if ($c2 === null) {
			return false;
		}
	}

}

interface ModelRelationMarkerHasOne {}
interface ModelRelationMarkerHasMany {}

interface ModelRelationHasOne extends ModelRelationMarkerHasOne {
//	/** return Model */
//	function get();
}
interface ModelRelationHasMany extends ModelRelationMarkerHasMany {
//	/**
//	 * @param array $overrideContext
//	 * @return ModelSet
//	 */
//	public function get(array $overrideContext = null);
}

abstract class ModelRelationByReference extends ModelRelation {

	protected $referenceField;

	function __construct(ModelRelationInfoByReference $info, Model $parentModel) {
		parent::__construct($info, $parentModel);
		$this->referenceField = $info->referenceField;
	}

	public function getReferenceField() {
		return $this->referenceField;
	}
}

/**
 * @var ModelRelationInfoHasReference $info
 */
abstract class ModelRelationHasReference extends ModelRelationByReference {

//	public function onTargetDelete($targetModel) {
//		throw new UnsupportedOperationException(
//			get_class($this) . '::onTargetDelete()'
//		);
//	}
}

class ModelRelationReferencesOne extends ModelRelationHasReference
		implements ModelRelationHasOne {

	public function loadFromDB($result) {
		if (!array_key_exists($this->referenceField, $result)) {
			throw new InvalidArgumentException("Missing field in result: $this->referenceField");
		}
		$this->setFromId($result[$this->referenceField]);
	}

	public function setFromId($id, $forceAcceptNull = false) {
		$this->parentModel->setColumn($this->referenceField, $id, $forceAcceptNull);
	}

	public function saveModelCallback() {
		$this->parentModel->setColumn(
			$this->referenceField, 
			$this->getAsModel()->getPrimaryKeyValue()
		);
	}

	public function setFromModel(Model $model) {
		$modelCache =& $this->cache->get();
		$modelCache = $model;
		$this->parentModel->setFieldFromModelPk($this->referenceField, $model);
	}

	public function setField($fieldName, $value, $forceAcceptNull = false) {
		$this->getAsModel(true)->setField($fieldName, $value, $forceAcceptNull);
	}

	public function getAsId() {
		return $this->parentModel->getField($this->referenceField);
	}

	public function doGetAsModel($createIfNone = false, array $overrideContext = null) {

		if (null !== $model =& $this->cache->get($overrideContext)) return $model;

		$context = $overrideContext !== null ? $overrideContext : $this->parentModel->context;

		if (null !== $id = $this->getAsId()) {

			if (null !== $uniqueBy = $this->info->getUniqueBy()) {
//				dump($uniqueBy);
				$id = array(
					$this->targetTable->getPrimaryKeyName() => $id,
				);

				foreach ($uniqueBy as $foreign => $local) {
					if (is_array($local)) {
						if (isset($local['value'])) {
							$id[$foreign] = $local['value'];
						} else {
							throw new UnsupportedOperationException();
						}
					} else {
						$id[$foreign] = $this->parentModel->getField($local);
					}
				}
			}

			$model = $this->targetTable->loadModel($id, $context);
		}

		if ($model === null && $createIfNone) {
			if ($overrideContext !== null) {
				throw new UnsupportedOperationException('Cannot create model for overriden contexts');
			}

			$model = $this->targetTable->createNewModel(
				null, false,
				$context
			);

			$this->parentModel->setFieldFromModelPk($this->referenceField, $model);
		}

		return $model;
	}

	public function save() {
		$success = true;
		if (null !== $model = $this->getAsModel()) {
			if (!$model->save()) $success = false;
		}
		if ($this->parentModel->isModified()) {
			if (!$this->parentModel->save()) $success = false;
		}
		return $success;
	}

}

class ModelRelationReferedByOne extends ModelRelationByReference 
		implements ModelRelationHasOne {

	/**
	 * @internal
	 * Tries to load the target model from the datastore and, optionnaly,
	 * create it. This method is intended for subclassing.
	 * @param bool $createIfNone
	 * @param array $context 
	 * @return Model the model found or newly created, or NULL if none has been
	 * found and $createIfNone if FALSE
	 */
	protected function getStoredModel($createIfNone, array $overrideContext = null) {

		$model = null;
		$context = $overrideContext !== null ? $overrideContext : $this->parentModel->context;

		if (!$this->parentModel->isNew()) {
			$model = $this->targetTable->findFirstWhere(
				"`$this->referenceField`=?",
				$this->parentModel->getPrimaryKeyValue(),
				$context,
				array($this->targetTable, 'addAssocWhere')
			);
		}

		if ($model === null && $createIfNone) {
			$model = $this->targetTable->createNewModel(
				null, false,
				$context
			);
//REM			if ($this->info->initCreatedModel === null) {
				$model->setFieldFromModelPk($this->referenceField, $this->parentModel);
//REM			} else if ($this->info->initCreatedModel === false) {
//				throw new IllegalStateException();
//			} else {
//				call_user_func($this->info->initCreatedModel, $model, $this->parentModel, $this);
//			}
		}

		return $model;
	}

	public function doGetAsModel($createIfNone = false, array $overrideContext = null) {
		return $this->getModelReference($createIfNone, $overrideContext);
	}

	/**
	 * @return Model
	 */
	private function &getModelReference($createIfNone = false, array $overrideContext = null) {

		if (null !== $model =& $this->cache->get($overrideContext)) {
			return $model;
		}

		$model = $this->getStoredModel(
			$createIfNone, 
			$overrideContext
		);

//		if ($model !== null && $this->info->reciproqueName !== null) {
//			$relation = $this->targetTable
//					->getRelationInfo($this->info->reciproqueName)
//					->createRelation($model);
//			$relation->setFromModel($this->parentModel);
//			$model->getInternal()->setRelation($relation);
//		}

		return $model;
	}

	public function doSet($value, $forceAcceptNull = false) {
		if (is_array($value)) {
			$model =& $this->getModelReference(true);
			$model->setFields($value, $forceAcceptNull);
		} else {
			parent::doSet($value, $forceAcceptNull);
		}
	}

	public function setFromModel(Model $model) {
		// TODO investigate why the next line was here (commented out to prevent
		// a deadly warning "only variables should be assigned by reference")
		// $cache =& $this->getAsModel();
		$cache = $model;
	}

	public function setFromId($id, $forceAcceptNull = false) {
		if ($id !== null) {
			// TODO investigate why the next line was here (commented out to prevent
			// a deadly warning "only variables should be assigned by reference")
			// $model =& $this->getAsModel();
			$model = $this->targetTable->loadModel($id, $this->parentModel->context);
		}
	}

	public function save() {
		if (null !== $model = $this->getAsModel()) {
			return $model->save();
		} else {
			return true;
		}
	}

	public function setField($fieldName, $value, $forceAcceptNull = false) {
		$this->getAsModel(true)->setField($fieldName, $value, $forceAcceptNull);
	}

	public function notifyDeleteToTarget() {
		if (null !== $model = $this->getAsModel()) {
			$model->onTargetDelete($this->parentModel);
		}
	}
}

/**
 * @property @info ModelRelationInfoReferredByOneAssoc
 */
class ModelRelationReferredByOneAssoc extends ModelRelationReferedByOne {

	public function __construct(ModelRelationInfoReferredByOneAssoc $info, Model $parentModel) {
		parent::__construct($info, $parentModel);
	}

	protected function getStoredModel($createIfNone, array $overrideContext = null) {
		return $this->parentModel
				->getRelation($this->info->assocRelationInfo->name)
				->getAssocModel($createIfNone, $overrideContext);
	}

}

//class ModelRelationReferredByOneCustom extends ModelRelationReferedByOne {
//
//	public function __construct(ModelRelationInfoByReference $info, Model $parentModel) {
//		parent::__construct($info, $parentModel);
//	}
//
//	protected function getStoredModel($createIfNoe, array $context) {
//
//	}
//}
//
class ModelRelationReferedByOneOnMultipleFields extends ModelRelationReferedByOne {

	protected function getStoredModel($createIfNone, array $context) {

		$model = null;

		if (!$this->parentModel->isNew()) {

			$fields = array();
			$inputs = array();

			foreach ($this->referenceField as $f) {
				$fields[] = "`$f`=?";
				$inputs[] = $this->parentModel->getPrimaryKeyValue();
			}

			$fields = trim(implode(' OR ', $fields));

			$model = $this->targetTable->findFirstWhere(
				"($fields)",
				$inputs,
				$context,
				array($this->targetTable, 'addAssocWhere')
			);
		}

		if ($model === null && $createIfNone) {
			$model = $this->targetTable->createNewModel(
				null, false,
				$context
			);
			$model->setFieldFromModelPk($this->referenceField[0], $this->parentModel);
		}

		return $model;
	}
}

class ModelRelationReferedByMany extends ModelRelationByReference implements ModelRelationHasMany {

	/**
	 *
	 * @param Model $callingModel
	 * @return ModelResultSet
	 */
	public function get(array $overrideContext = null) {

		if (null !== $models =& $this->cache->get($overrideContext)) {
			return $models;
		} else if ($this->parentModel->isNew()) {
			return $models = array();
		}

		$context = $overrideContext !== null ? $overrideContext : $this->parentModel->context;

		$query = $this->targetTable
				->createLoadQuery(ModelTable::LOAD_NONE, $context)
				->applyAssocWhere(
					$this->targetTable, 
					"$this->referenceField=?", 
					$this->parentModel->getPrimaryKeyValue()
				);

		$models = $this->targetTable->createModelSet(
			$query, 
			ModelSet::RANDOM_ACCESS
		);

		if ($this->info->reciproqueName !== null) {
			foreach ($models as $i => $model) {
				$model instanceof Model;
				$relation = $this->targetTable
						->getRelationInfo($this->info->reciproqueName)
						->createRelation($model);
				$relation->setFromModel($this->parentModel);
				$model->getInternal()->setRelation($relation);
			}
		} else {
			Logger::get($this)->warn('Missing reciproque name for relation: ' . $this);
		}

		return $models;
//		return $this->targetTable->findWhere(
//			"$this->referenceField=?", $this->parentModel->getPrimaryKeyValue(),
//			$modelSetMode,
//			$overrideContext !== null ? $overrideContext : $this->parentModel->context,
//			array($this->targetTable, 'addAssocWhere')
//		);
	}
//	public function get(Model $callingModel, $modelSetMode = ModelSet::ONE_PASS, array $overrideContext = null) {
//
////		if ($this->foreignModel === null) $this->foreignModel = $callingModel->model;
////		else Logger::assertTrue($this->foreignModel === $callingModel->model);
//
//		return $this->targetTable->findWhere(
//			$this->referenceField . ' = ?',
//			$this->parentModel->getPrimaryKeyValue(),
//			$modelSetMode,
//			$overrideContext !== null ? $overrideContext : $this->parentModel->context,
//			array($this->targetTable, 'addAssocWhere')
//		);
//	}

	protected function doSet($value, $forceAcceptNull = false) {
		$id = $this->parentModel->getPrimaryKeyValue();

		$models =& $this->cache->get();

		$models = array();

		if ($value) {
			foreach ($value as $m) {
				if (false == $m instanceof Model) {
					if (is_array($m)) {
						$m = $this->targetTable->createModel($m, false, $this->parentModel->context);
					} else {
						$m = $this->targetTable->loadModel($m, $this->parentModel->context);
					}
				}
				$m->setFieldFromModelPk($this->referenceField, $this->parentModel);
				$models[] = $m;
			}
		}
	}

	protected function getExistingModels($modelSetMode = ModelSet::ONE_PASS, $overrideContext = null) {
		return $this->targetTable->findWhere(
			$this->referenceField . ' = ?',
			$this->parentModel->getPrimaryKeyValue(),
			$modelSetMode,
			$overrideContext !== null ? $overrideContext : $this->parentModel->context,
			array($this->targetTable, 'addAssocWhere')
		);
	}

	public function save() {
		if (null === $models = $this->cache->get()) {
			return;
		}

		$olds = array();
		foreach($this->getExistingModels() as $m) {
			$old = true;
			$oldId = $m->getPrimaryKeyValue();
			foreach ($models as $mm) {
				if ($mm->getPrimaryKeyValue() === $oldId) {
					$old = false;
					break;
				}
			}
			if ($old) $olds[] = $oldId;
		}

		if (count($olds) > 0) {
			$this->targetTable->deleteWherePkIn($olds, $this->parentModel->context);
		}

		$success = true;
		foreach ($models as $m) {
			if (!$m->save()) $success = false;
		}

		return $success;
	}

}

abstract class ModelRelationByAssoc extends ModelRelation {

	/** @var ModelTable */
	protected $assocTable;
	protected $localForeignKey;
	protected $otherForeignKey;

	function __construct(ModelRelationInfoByAssoc $info, Model $parentModel) {

		parent::__construct($info, $parentModel);

//		dumpl(array("$this", $this->parentModel->context));

		$info->getAssocTable()->attach($this->assocTable);

		$this->localForeignKey = $info->localForeignKey;
		$this->otherForeignKey = $info->otherForeignKey;
	}

	/**
	 * @return myModelTable
	 */
	public function getAssocTable() {
		return $this->assocTable;
	}

}

class ModelRelationHasOneByAssoc
		extends ModelRelationByAssoc
		implements ModelRelationHasOne {

	/**
	 * @return myModel
	 */
	public function getAssocModel($createIfNew = false, array $overrideContext = null) {
		throw new UnsupportedOperationException("$this::getAssocModel()");
	}
}

class ModelRelationIndirectHasMany extends ModelRelationByAssoc
		implements ModelRelationHasMany, IteratorAggregate {

	protected $assocModels = null;

	protected function doSet($value, $forceAcceptNull = false) {

		$id = $this->parentModel->getPrimaryKeyValue();

		$this->assocModels = array();

		if ($value) {
			foreach ($value as $v) {
				if (!is_array($v)) {
					$v = array(
						$this->localForeignKey => $id,
						$this->otherForeignKey => $v
					);
				} else {
					$childPkName = $this->assocTable->getPrimaryKeyName();
					if ($childPkName !== $this->otherForeignKey && !isset($v[$this->otherForeignKey])) {
						$v[$this->otherForeignKey] = $v[$childPkName];
						unset($v[$childPkName]);
					}
					$v[$this->localForeignKey] = $id;
				}
				$m = $this->assocTable->createModel(
					$v, false,
					$this->parentModel->context
				);

				if ($this->parentModel->isNew()) {
					$m->setFieldFromModelPk($this->localForeignKey, $this->parentModel);
				}

				$this->assocModels[] = $m;
			}
		}
	}

	/**
	 * @internal This method must not do anything other than merely relaying
	 * the call to ModelSet::push(). Children classes should always prefer to
	 * override the getAssocModels method, rather than touching to this one.
	 * Every add/remove additional processing should take place in the save()
	 * procedure, since the user can have modified the ModelSet itself.
	 * @param Model $model
	 */
	public final function add(Model $model) {
		$this->getAssocModels(ModelSet::RANDOM_ACCESS, true)->push($model);
	}

	/**
	 * @param mixed $value	the value of the primary key of the model to remove,
	 * or a Model from which the id wil be taken. If the Model is new (hence,
	 * has no id, an IllegalArgumentException will be thrown.
	 * @throws IllegalArgumentException
	 * @internal This method must not do anything other than merely relaying
	 * the call to ModelSet::push(). Children classes should always prefer to
	 * override the getAssocModels method, rather than touching to this one.
	 * Every add/remove additional processing should take place in the save()
	 * procedure, since the user can have modified the ModelSet itself.
	 */
	public final function removeById($value) {
		$this->getAssocModels(ModelSet::RANDOM_ACCESS, true)->removeById($value);
	}

	public function isModified() {
		return $this->assocModels !== null;
	}

//	public function deleteAll() {
//		// First load all model that will be delete to allow them to process
//		// and propagate their delete events
//		throw new IllegalStateException('Must be tested!!!');
//		foreach (
//			$this->assocTable->findWhere(
//				"`$this->localForeignKey`=?",
//				$this->parentModel->getPrimaryKeyValue(),
//				ModelSet::ONE_PASS,
//				$this->parentModel->context,
//				array($this->assocTable, 'addAssocWhere')
//			)
//			as $model
//		) {
//			$model->delete();
//		}
////		$finder = $this->assocTable->find();
////		$finder->where(
////			$this->assocTable->addAssocWhere(
////				QueryWhere::create(
////					$finder->query->getQualifiedName($this->localForeignKey) . ' = ?'
////					,$this->parentModel->getPrimaryKeyValue()
////				)
////			)
////		);
////
////		foreach ($finder->execute(ModelSet::ONE_PASS) as $model) {
////			$model->delete();
////		}
//	}

	public function save() {
		if ($this->isModified()) {

			$existingAssocModels = $this->getExistingAssocModels(ModelSet::RANDOM_ACCESS);

			// usage of existing assoc model as array is bad, it should be corrected...

			// it should however be possible to uniquely identify models only by
			// their local/other fk pair, since getExistingAssocModels should have
			// filtered out other rows based on their other properties (year)

			$success = true;
			foreach ($this->assocModels as $model) {
				foreach ($existingAssocModels as $i => $m) {
					if ($m->getField($this->localForeignKey) === $model->getField($this->localForeignKey)
							&& $m->getField($this->otherForeignKey) === $model->getField($this->otherForeignKey)) {

						$model->setPrimaryKeyValue($m->getField($this->assocTable->getPrimaryKeyName()));
						unset($existingAssocModels[$i]);

						break;
					}
				}

				$new = $model->isNew();
				if (!$model->save()) {
					$success = false;
				} else {
					if ($new) {
						// fire added child event
						$this->parentModel->events->fire(ModelRelation::E_ADDED, $this, $model);
					} else {
						// fire modified child event
						$this->parentModel->events->fire(ModelRelation::E_MODIFIED, $this, $model);
					}
				}
			}

			// Delete remaining unused assoc records
			foreach ($existingAssocModels as $model) {
				if ($model->delete()) {
					$this->parentModel->events->fire(ModelRelation::E_DELETED, $this, $model);
				} else {
					$success = false;
				}
			}

			return $success;
		}
		return true;
	}

	/**
	 * @return ModelSet
	 */
	public function getAssocModels($modelSetMode = ModelSet::RAW, $createIfNew = false) {
		if ($this->assocModels !== null) return $this->assocModels;
		if (null === $this->assocModels = $this->getExistingAssocModels($modelSetMode)) {
			if (!$createIfNew) return null;
			else return ModelSet::createEmpty($table);
		} else {
			return $this->assocModels;
		}
	}

	protected function getExistingAssocModels($modelSetMode = ModelSet::RAW) {
		if ($this->parentModel->isNew()) return null;

		return $this->assocTable->findWhere(
			"`$this->localForeignKey` = ?", $this->parentModel->getPrimaryKeyValue()
			,$modelSetMode
			,$this->parentModel->context
			,array($this->assocTable, 'addAssocWhere')
			,null // reciproque factory ?
		);
	}

	protected function doGetAsModel($createIfNone = false, array $overrideContext = null) {
		return $this->get($overrideContext);
	}

	public function getIterator() {
		return $this->get();
	}

	public function get(array $overrideContext = null) {
		if (null !== $models =& $this->cache->get($overrideContext)) {
			return $models;
		}

		$assocModels = $this->getAssocModels(ModelSet::RANDOM_ACCESS);

		if (count($assocModels) === 0) return $models = ModelSet::createEmpty($this->targetTable);

		$targetIds = array();
		foreach ($assocModels as $i => $assocModel) {
			$targetIds[$i] = $assocModel->getField($this->otherForeignKey);
		}

		$context = $overrideContext !== null ? $overrideContext : $this->parentModel->context;

		if (count($targetIds) == 0) {
			return ModelSet::createEmpty($this->targetTable, ModelSet::RANDOM_ACCESS);
		}

		$query = $this->targetTable->createLoadQuery(ModelTable::LOAD_NONE, $context);

		$this->targetTable->addAssocWhere(
			$query->createWhere()->whereIn($this->targetTable->getPrimaryKeyName(), $targetIds)
			,$query
		);

		if (!$where->isNull()) {
			$query->where($where);
		}

		$models = $this->targetTable->createModelSet(
			$query, 
			ModelSet::RANDOM_ACCESS
		);

		foreach ($models as $i => $model) {
			$model instanceof Model;
//TODO			if ($this->info->targetAssocName !== null) {
//				$relation = $this->assocTable
//						->getRelationInfo($this->info->targetAssocName)
//						->createRelation($model);
//				$relation->setFromModel($assocModels[$i]);
//				$model->getInternal()->setRelation($relation);
//			}
//TODO			// Associate parentModel to reciproque relation in the new Model
//			$relation = $this->targetTable
//					->getRelationInfo($this->info->reciproqueName)
//					->createRelation($model);
//			$relation->setFromModel($this->parentModel);
//			$model->getInternal()->setRelation($relation);
		}

		return $models;
	}
}

class ModelRelationIndirectHasOne extends ModelRelationHasOneByAssoc {

	protected static $dropWhenOneSideIsNull = true;

//	/** @var myModel */
//	protected $model = null;

	/** @var myModel */
	protected $assocModel = null;

	public function isModified() {
		return $this->assocModel !== null;
	}

	public function save() {
		if ($this->isModified()) {
			if ((null === $this->assocModel->getField($this->localForeignKey)
					|| null === $this->assocModel->getField($this->otherForeignKey))
					&& self::$dropWhenOneSideIsNull) {

				return $this->assocModel->discard();
			} else {
				$r = $this->assocModel->save();
				if (null !== $model = $this->getAsModel()) $r = $r && $model->save();
				return $r;
			}
		} else {
			return true;
		}
	}

	/**
	 * @return myModel
	 */
	public function getAssocModel($createIfNone = false, array $overrideContext = null) {

		if ($overrideContext === null) {
			// We are in parent model's default context, so
			// (1) we set $context to the parent model's one
			// (2) we put a ref of the assoc model in $assocModel to have
			// it updated
			if ($this->assocModel !== null) return $this->assocModel;
			$context = $this->parentModel->context;
			$assocModel =& $this->assocModel;
		} else {
			$context = $overrideContext;
			// We don't store value when we use overriden context
			$assocModel = null;
		}

		if (!$this->parentModel->isNew()) {
			$assocModel = $this->findAssocModel(
				$this->parentModel->getPrimaryKeyValue(),
				$context
			);
		}

		if ($assocModel === null) {
			if ($createIfNone) {
				$assocModel = $this->assocTable->createNewModel(
					null, // setters (empty model)
					false, // strict
					$context
//					$this->parentModel->context // model context
				);
				$assocModel->setFieldFromModelPk($this->localForeignKey, $this->parentModel);
				return $assocModel;
			} else {
				return null;
			}
		} else {
			return $assocModel;
		}
	}

	protected function findAssocModel($pk, array $context) {
		return $this->assocTable->findFirstWhere(
			"`$this->localForeignKey` = ?", $pk,
			$context,
//			$this->parentModel->context,
			array($this->assocTable, 'addAssocWhere')
		);
	}

	public function getTargetReferenceFieldName(array $overrideContext = null) {
		return $this->otherForeignKey;
	}

	public function setFromId($id, $forceAcceptNull = false) {
		if (null === $assocModel = $this->getAssocModel()) {
			if ($id !== null) {
				$assocModel = $this->assocModel = $this->assocTable->createModel(
					null, false,
					$this->parentModel->context
				);
				$assocModel->setFieldFromModelPk(
					$this->localForeignKey, $this->parentModel
				);
				$assocModel->setField($this->otherForeignKey, $id, $forceAcceptNull);
			}
		} else {
			if ($id === null) {
				$assocModel->markDeleted();
			} else {
				if ($assocModel->isDeleted()) {
					throw new IllegalStateException('The assoc model has already been programmed for deletion');
				}
				$assocModel->setField($this->getTargetReferenceFieldName(), $id, $forceAcceptNull);
			}
		}
	}

	/**
	 * @return myModel
	 */
	public function doGetAsModel($createIfNone = false, array $overrideContext = null) {

		if (null !== $model =& $this->cache->get($overrideContext)) return $model;

		if (null === $assocModel = $this->getAssocModel($createIfNone, $overrideContext)) return null;

		$context = $overrideContext !== null ? $overrideContext : $this->parentModel->context;

		if (null !== $targetFKValue = $assocModel->getField($this->getTargetReferenceFieldName($overrideContext))) {
			$model = $this->targetTable->findFirstByPrimaryKey($targetFKValue, $context);
		}

		if ($model === null) {
			if ($createIfNone) {
				return $model = $this->targetTable->createNewModel(
					null, false,
					$context
				);
			} else {
				return null;
			}
		} else {
			return $model;
		}
	}

	public function setField($fieldName, $value, $forceAcceptNull = false) {
		$this->getAsModel(true)->setField($fieldName, $value, $forceAcceptNull);
	}

//	public function getAsId() {
//
//		$this->assocModel = $this->getAssocModel();
//
//		$pkValue = $this->parentModel->getPrimaryKeyValue();
//
//		if ($pkValue === $this->assocModel()->getField($this->otherForeignKey))
//			return $this->otherForeignKey;
//		else if ($pkValue === $this->assocModel()->getField($this->localForeignKey))
//			return $this->localForeignKey;
//		else
//			throw new IllegalStateException('Wrong assoc row');
//
//
//	}

}

class ModelRelationIndirectHasOneMirror extends ModelRelationIndirectHasOne {

	public function  __construct(Model $parentModel, ModelRelationInfoIndirectHasOneMirror $info) {
		parent::__construct($info, $parentModel);
	}

	protected function findAssocModel($pk, array $context) {
		return $this->assocTable->findFirstWhere(
			"`$this->localForeignKey` = ? OR `$this->otherForeignKey` = ?"
			,array($pk, $pk)
			,$context
			,array($this->assocTable, 'addAssocWhere')
		);
	}

	public function getTargetReferenceFieldName(array $overrideContext = null) {
		if (null === $assocModel = $this->getAssocModel(false, $overrideContext)) {
			return null;
		} else {
			$parentPKVal = $this->parentModel->getPrimaryKeyValue();
			if ($assocModel->getField($this->localForeignKey) == $parentPKVal)
				return $this->otherForeignKey;
			else if ($assocModel->getField($this->otherForeignKey) == $parentPKVal)
				return $this->localForeignKey;
			else
				throw new IllegalStateException("Wrong assoc model: $assocModel");
		}
	}

}
