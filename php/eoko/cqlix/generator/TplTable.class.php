<?php

namespace eoko\cqlix\generator;

use UnsupportedOperationException, IllegalArgumentException, IllegalStateException;

class TplTable implements ConfigConstants {
	
	public $dbTable;
	public $tableName;
	public $modelName;

	/** @var array[TplField] */
	private $columns = null;

	/**
	 * @var array[$relationName => Relation] References to all this table's
	 * relations. This field is set <b>after the table has been configured</b>
	 * (in order to be able to correctly map relations' name which may be
	 * modified during the configuration step).
	 */
	private $relations = null;

	/**
	 * @var array[$referenceFieldDbName => TplRelation] References to direct
	 * (ie ReferencesOne) relations of which the reference field is owned by
	 * this table.
	 */
	private $directLocalRelations = array();
	/**
	 * @var array[$referenceFieldDbName => TplRelation] References to direct
	 * (ie ReferencesOne) relations of which the reference field is targetting
	 * one of this table's fields, but is owned by the foreign table.
	 */
	private $directForeignRelations = array();
	/**
	 * @var array[TplRelationByAssoc]
	 */
	private $indirectRelations = array();

	public function __construct($dbTableName) {

		NameMaker::generateTableEntries($dbTableName);

		$this->dbTable = $dbTableName;
		$this->modelName = NameMaker::modelFromDB($dbTableName);
		$this->tableName = NameMaker::tableFromDB($dbTableName);
	}

	public function __toString() {
		return $this->dbTable;
	}
	
	private $configured = false;
	private $config = null;

	public function configure($config) {

		if ($this->configured) {
			throw new IllegalStateException('Table already configured');
		} else {
			$this->configured = true;
		}

		$this->doConfigure($config);
	}

	public $defaultController = null;

	private function doConfigure($config) {

		$this->config = $config;

		if (isset($config['defaultController'])) {
			$this->defaultController = $config['defaultController'];
		}

		if ($config === null || !isset($config[self::CFG_COLUMNS])) {
			foreach ($this->columns as $name => $field) {
				$field->configure(null);
			}
		} else {
			$colConfig = $config[self::CFG_COLUMNS];
			foreach ($this->columns as $name => $field) {
				$field->configure(isset($colConfig[$name]) ? $colConfig[$name] : null);
			}
		}
//		if (isset($config[self::CFG_COLUMNS])) {
//			$this->configureColumns($config[self::CFG_COLUMNS]);
//		}
	}

	private function configureColumns($config) {
		foreach ($config as $field => $config) {
			$this->getField($field)->configure($config);
		}
	}

	public function configureRelations() {

		$this->mergeRelations();
		
		if (!$this->config) return;

		$colConfig = isset($this->config[self::CFG_COLUMNS]) 
				? $this->config[self::CFG_COLUMNS] : array();
		foreach ($this->directLocalRelations as $name => $relation) {
			if (isset($colConfig[$relation->referenceField]['relation'])) {
				$relation->configure(
					$colConfig[$relation->referenceField]['relation'],
					isset($this->config['relations'][$name])
							? $this->config['relations'][$name]
							: null
				);
			}
		}
		foreach ($this->relations as $name => $relation) {
			if (isset($this->directLocalRelations[$name])
					&& isset($colConfig[$relation->referenceField]['relation'])) {
				$relation->configure(
					$colConfig[$relation->referenceField]['relation'],
					isset($this->config['relations'][$name])
							? $this->config['relations'][$name]
							: null
				);
			} else {
				$relation->configure(
					null,
					isset($this->config['relations'][$name])
							? $this->config['relations'][$name]
							: null
				);
			}
		}
	}

	/**
	 * @param string $dbFieldName
	 * @return TplField
	 */
	public function getField($dbFieldName) {
		if (!isset($this->columns[$dbFieldName])) {
			throw new IllegalStateException("Table $this->dbTable has no field $dbFieldName");
		}
		return $this->columns[$dbFieldName];
	}
	
	private function addRelation(TplRelation $relation) {
		if (isset($this->relations[$relation->getName()])) {
			$prev = $this->relations[$relation->getName()];

			// TODO this line has been added to skip a crash with a mirror relation
			// WebsitePages->Parent, in Rhodia.Opence... this should be investigated
			// that this is OK, and implement a real way to handle mirror relation
			if ($prev->localDBTableName !== $relation->localDBTableName || $prev->referenceField !== $relation->referenceField)

					throw new IllegalStateException(
				"Relation with name {$relation->getName()} already exist in table "
				. "$this->tableName (in database: $this->dbTable).\n"
				. "Existing relation: " . $this->relations[$relation->getName()] . PHP_EOL
				. "Added relation: $relation"
			);
		} else {
			$this->relations[$relation->getName()] = $relation;
		}
	}

	private function mergeRelations() {
		$this->relations = array();
		foreach ($this->directLocalRelations as $referenceField => $relation) {
			$this->addRelation($relation);
		}
		foreach ($this->directForeignRelations as $foreignTable => $relations) {
			foreach ($relations as $relation) {
				$this->addRelation($relation);
			}
		}
//		if ($this->modelName === 'WebsitePage') dump($this->relations);

		// TODO indirect relations
//		foreach ($this->indirectRelations as $relation) {
//			$this->addRelation($relation);
//		}
	}

	public function setColumns($fields) {
		
		if ($this->columns !== null) {
			throw new IllegalStateException();
		}
		
		foreach ($fields as $field) {
			if (false == $field instanceof TplField) {
				throw new IllegalArgumentException('Field must implement TplField');
			}
			$field->setParentTable($this);
		}
		
		$this->columns = $fields;
	}

	public function addColumn(TplField $fields) {

		throw new UnsupportedOperationException('What is this useful for ??');

		if (is_array($field)) {
			foreach ($field as $field) $this->addColumn($field);
			return;
		}

		if (isset($this->columns[$field->getName()])) {
			throw new IllegalStateException("Table $this->dbTable already has a column {$field->getName()}");
		}

		$this->columns[$field->getName()] = $field;
	}

//	public function addRelation(TplRelation $relation) {
//		//$this->relations
//	}
	public function addDirectRelation(TplRelationReferencesOne $relation) {
		if (isset($this->directLocalRelations[$relation->referenceField])) {
			throw new IllegalStateException(
				"Relation from field $relation->referenceField in table $this->dbTable already set"
			);
		}
		$this->directLocalRelations[$relation->referenceField] = $relation;
	}

	public function addDirectRelations($relations) {
		foreach ($relations as $relation) {
			$this->addDirectRelation($relation);
		}
	}

	public function addDirectReciproqueRelation(TplRelationIsRefered $relation) {
		$this->directForeignRelations[$relation->localDBTableName][] = $relation;
	}

	public function addSecondaryRelation(TplRelationByAssoc $relation) {
		if (false == $relation instanceof TplRelationByAssoc) {
			throw new IllegalArgumentException();
		}
		$this->indirectRelations[] = $relation;
	}

	public function getRelation($relationName) {
		if ($this->relations === null) {
			throw new IllegalStateException(
				'Relations have not been configured yet'
			);
		}
	}

}