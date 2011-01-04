<?php

namespace eoko\cqlix\generator;
use eoko\script\Script;
use eoko\database\Query;
use eoko\database\ConnectionManager;
use eoko\template\Template;

use PDO;
use ModelColumn;
use ReflectionClass, ReflectionMethod;
use Logger, Debug;
use Exception, SystemException, IllegalStateException, IllegalArgumentException, UnsupportedOperationException;
use ConfigurationException;

use eoko\config\ConfigManager;
/**
 * Generates the data model information by reverse engineering the database
 *
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 * @package PS-ORM-1
 * @subpackage bin
 *
 * Relations
 *
 * The following types of relations are defined:<ul>
 * <li>Has one
 * <li>Has one reference
 * <li>Has many
 * <li>Has many to many
 * </ul>
 *
 * The relations are determined by the field names, and/or the FOREIGN KEY
 * CONSTRAINTs which are defined in the database.
 *
 * When searching by field names, if a table (table1) contains a field named
 * [xxx_]table2_id (where table2 is the name of one of the database tables, and
 * id the name of table2's primary key), then table1 is considered to have a
 * <u>HasOne</u> relationship to table2.
 *
 * If present, xxx will be taken as the <u>alias</u> of the relation; more
 * specifically, if xxx and table are separated by a double underscore
 * (xxx__table2_id), the alias will be only xxx, while if it is a single
 * underscore (xxx_table2_id), the alias will be xxx_table2. The alias is used
 * in the syntax element evoquing the relation(eg. hasOneXxxTable2,
 * getXxxTable2, ...).
 *
 * When searching by foreign key constraints, if table1 contains a field with
 * a FOREIGN KEY CONSTRAINT on field2 of table2, then table1 is considered to
 * have a HasOne relationship to table 2. If the referencing field name has
 * the form xxx_table2_id, then the same rules as for the search by name are
 * apply to decide the alias, else the whole field name is taken as the alias.
 *
 * Every other type of relationships are derived from all the HasOne relations
 * which are determined with the previously exposed rules.
 *
 * If tableA is referenced by tableB (ie. tableB <i>hasOne</i> tableA), then
 * tableA has either a <u>HasMany</u> or a <u>HasOne</u> relationship with
 * tableB. If there exists a UNIQUE index on the field of tableB referencing
 * tableA, then tableA will have a <u>HasOneReference</u> relationship to tableB.
 * If there is no such index, then tableA will have a <u>HasMany</u> relationship
 * to tableB.
 *
 */
class Generator extends Script {

	private $database;

	private $proxyTableMethods;
	private $proxyModelMethods;
	private $primaryKeys;
	private $tableFields;
	
	/** @var array[$dbTable => array[TplRelation]] */
	private $referencesOneRelations;
	private $hasOneReciproqueRelations;
	/** @var array[TplRelation] all models relations lookup */
	private $allRelations;

	/** @var Config */
	private $baseConfig;

	private $tables;
	private $modelsConfig;
	
	private $fileWritten = 0, $modelProcessed = 0;

	public function __construct() {

		// We want exceptions to show off at the face of the user, not being
		// wrapped in Ext compliant errors as done by the default application
		// exception handler!
		restore_exception_handler();

		// Includes all relation templates classes
		RelationTemplates::load();

		$this->tplPath = dirname(__FILE__) . DS . 'templates' . DS;

		// TODO
		// I think this is not really used, and its initial objective has been
		// superseeded by the new config implementation
		$this->baseConfig = new Config();

		// TODO make the config namespace configurable
		$this->modelsConfig = ConfigManager::get('rhodia/cqlix/models');
	}

	protected function run() {

		$startTime = microtime();

		$this->database = ConnectionManager::getDatabaseName();

		// Reverse engineer the ModelTable and Model classes, to discover the
		// proxy methods (protected methods prefixed with an underscore _) to
		// be added to the Base classes
		$this->proxyTableMethods = $this->generateProxyTableMethods('ModelTable');
		$this->proxyModelMethods = $this->generateProxyTableMethods('Model');

		// Reverse engineer table names from the database
		$this->discoverTables();

		// Deported in TplTable creation
		// Generate name maker entries
		//NameMaker::generateAllEntries($this->tables);
		
		// Reverse engineer tables columns informations from the database
		$this->discoverTablesFields();

		// Discover the primary for all tables
		// This must be done after the fields information have been retrieved (of course...)
		$this->discoverPrimaryKeys();

		$this->configureTables();
		
		// --- Relations ---------------------------------------------------------------

		$this->discoverDirectReferencingOneRelations();

		$this->discoverDirectReciproqueRelations();

		$this->discoverSecondaryRelations();

		// --- Configuration ---------------------------------------------------

//		$this->configureTables();

		// --- Mark foreign keys ---
		if (count($this->referencesOneRelations) > 0) {
			foreach ($this->referencesOneRelations as $table => $rels) {
				foreach ($rels as $r) {
					$r instanceof ModelRelationReferencesOne;
					$this->tableFields[$r->localDBTableName][$r->referenceField]
							->setForeignKeyToTable($r->targetDBTableName);
				}
			}
		}

		// --- Merge relations
		$this->allRelations = null;
		if (count($this->referencesOneRelations) > 0) {
			foreach ($this->referencesOneRelations as $table => $rels) {
				foreach ($rels as $r) {
					$this->allRelations[$table][] = $r;
				}
			}
		}
		if (count($this->hasOneReciproqueRelations) > 0) {
			foreach ($this->hasOneReciproqueRelations as $table => $rels) {
				foreach ($rels as $r) {
					if ($r instanceof TplRelationByReference)
						$this->allRelations[$table][] = $r;
				}
			}
		}
		// TODO secondary relations
		//if (count($secondaryRelations) > 0)
		//	foreach ($secondaryRelations as $table => $rels)
		//		foreach ($rels as $r) $allRelations[$table][] = $r;


		foreach ($this->tables as $table) {
			if ($this->allRelations !== null && !array_key_exists($table->dbTable, $this->allRelations)) {
				$this->allRelations[$table->dbTable] = array();
			}
		}

		//print_r($relations); die;



		// --- Process -----------------------------------------------------------------

		foreach ($this->tableFields as $table => $fields) {

			$modelName = NameMaker::modelFromDB($table);
			$tableName = NameMaker::tableFromDB($table);

			$modelFilename = MODEL_PATH . $modelName . '.class.php';
			$modelBaseFilename = MODEL_BASE_PATH . $modelName . 'Base' . '.class.php';

			$tableFilename = MODEL_PATH . $tableName . '.class.php';
			$tableBaseFilename = MODEL_BASE_PATH . $tableName . 'Base' . '.class.php';
			$tableProxyFilename = MODEL_PROXY_PATH . $tableName . 'Proxy' . '.class.php';

			$modelQueryFilename = MODEL_QUERY_PATH . "{$modelName}Query" . '.class.php';

			$params = array($table, $fields);

			$this->writeFile($tableProxyFilename, true, array($this, 'tplTableProxy'), $params);
			$this->writeFile($tableBaseFilename, true, array($this, 'tplTableBase'), $params);
			$this->writeFile($tableFilename, false, array($this, 'tplTable'), $params);
			$this->writeFile($modelBaseFilename, true, array($this, 'tplModelBase'), $params);
			$this->writeFile($modelFilename, false, array($this, 'tplModel'), $params);
			$this->writeFile($modelQueryFilename, true, array($this, 'tplModelQuery'), $params);

			$this->modelProcessed++;
		}

		$time = Debug::elapsedTime($startTime, microtime());
		echo PHP_EOL . sprintf('DONE ! (%d models processed, %d files written -- %.2fs)',
				$this->modelProcessed, $this->fileWritten, $time);
	}

	private function discoverTables() {

		$tables = Query::executeQuery("SHOW TABLES FROM `$this->database`;")
				->fetchAll(PDO::FETCH_COLUMN);

		$this->tables = array();
		foreach ($tables as $table) {
			$this->tables[$table] = new TplTable($table);
		}
	}

	private function discoverTablesFields() {

		$this->tableFields = array();

		foreach ($this->tables as $dbTable => $table) {

			echo "Reading: `$dbTable`" . PHP_EOL;
			$result = Query::executeQuery("SHOW COLUMNS FROM `$dbTable` FROM `$this->database`;");
			$result = $result->fetchAll(PDO::FETCH_NAMED);

			$fields = array();
			foreach ($result as $r) {

				$field = $r['Field'];

				// type
				preg_match('/([^(]+)(\(([\d,]+)\))?/', $r['Type'], $match);
				$type = $match[1];
				$length = isset($match[3]) ? $match[3] : null;

				$default = $r['Default'] != '' ? $r['Default'] : null;
				$primaryKey = stristr($r['Key'], 'PRI') !== false ? true : false;
				$autoIncrement = stristr($r['Extra'], 'auto_increment') !== false ? true : false;
				$canNull = $r['Null'] == 'YES' ? true : false;

				$fields[$field] = new TplField($field, $type, $length, $canNull, $default,
						false, null, $primaryKey, $autoIncrement);
			}

			// --- Index ---

			$createTable = Query::executeQuery('SHOW CREATE TABLE `' . $dbTable . '`;');
			$createTable = $createTable->fetchColumn(1);

			preg_match('/(?:,|\s)ENGINE\s*=\s*(\w+)\s+/', $createTable, $matches);
			$engine = $matches[1];

			if (strtolower($engine) !== 'innodb') {
				Logger::warn('Table {} engine is not InnoDB (it is {})', $dbTable, $engine);
			} else {
				// Foreign key constraints
				$pattern = '/(?:\s|,)CONSTRAINT `(\w+)` FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)/';
				preg_match_all($pattern, $createTable, $matches, PREG_SET_ORDER);

				foreach ($matches as $match) {
					list($ignore, $constraintName, $localKey, $constraintTable, $otherField) = $match;
					$fields[$localKey]->foreignConstraint = new ModelColumnForeignConstraint($constraintTable, $otherField, $constraintName);
				}
			}

			// Unique index
			$pattern = '/(?:\s|,)UNIQUE KEY `(?:\w+)` \(`(\w+)`\)/';
			preg_match_all($pattern, $createTable, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {
				$uniqueField = $match[1];
				$fields[$uniqueField]->unique = true;
			}

			// Comments
			preg_match_all("/`(\w+)`[^,]*\bCOMMENT\b\s*'([^']+)'/", $createTable, $matches, PREG_SET_ORDER);
			foreach ($matches as $m) {
				// This call configure the field with the commands extracted
				// from the comment in the db (in the form cqlix: ...).
				// Is this really a good place to do that? Shouldn't this
				// processing be centralized at the time of the whole table
				// configuration (time when some additionnal field configuration
				// may take place, and conflict with the one from the comments...)
				$fields[$m[1]]->setComment($m[2]);
			}

			// deported in table's configuration step
//			// Config
//			if (isset($this->modelsConfig[$dbTable])) {
////				if ($table === 'sm_opt_contingent') {
////				 dump_mark();
////				 dump($tablesConfig[$table]['columns']);
////				}
//				foreach ($this->modelsConfig[$dbTable]['columns'] as $field => $config) {
//					if (isset($fields[$field])) {
//						$fields[$field]->configure($config);
//					} else {
//						throw new ConfigurationException(
//							null,
//							'{APP_NS}/cqlix/models',
//							"Invalid configuration for table: $dbTable.\n"
//							. "The table $dbTable doest not contain any field $field"
//						);
//					}
//				}
//			}

			$table->setColumns($fields);
			$this->tableFields[$dbTable] = $fields;
		}
	}

	private function discoverDirectReferencingOneRelations() {

		$this->referencesOneRelations = array(
			self::GUESS_BY_NAME => array(),
			self::GUESS_BY_CONSTRAINT => array()
		);
		
		foreach ($this->tableFields as $table => $fields) {
			$this->discoverTableReferencesOneRelations($table, $fields);
		}

		$this->mergeRelationsFoundByNameAndByFK(true, true);
		
		foreach ($this->referencesOneRelations as $dbTable => $relation) {
			$table = $this->tables[$dbTable];
			$table->addDirectRelations($relation);
		}
	}

	private function discoverDirectReciproqueRelations() {
		$this->hasOneReciproqueRelations = array();
		foreach ($this->referencesOneRelations as $dbTable => $allRelations) {
			foreach ($allRelations as $relation) {
				$relation instanceof TplRelationReferencesOne;

				$targetTable = $relation->targetDBTableName;
				$field = $relation->getReferenceField();

				$this->tableFields[$dbTable][$field]->setForeignKeyToTable($targetTable);

				// --- has one reciproques
				$foreignConstraint = $this->tableFields[$dbTable][$field]->getForeignConstraint();
				$constraintName = $foreignConstraint !== null ?
						$foreignConstraint->constraintName
						: null;
		//		$alias = ModelRelationBackHasOne::makeAlias($constraintName, $table,
		//				$targetTable, $relation->getAlias());

				$alias = $this->tableFields[$dbTable][$field]->foreignRelationAlias;
				if ($this->tableFields[$dbTable][$field]->isUnique() || $this->tableFields[$dbTable][$field]->isPrimary()) {
					$reciproqueRelation = new TplRelationReferedByOne($relation->targetDBTableName, $dbTable,
							$alias, $relation, $relation->getReferenceField(), null);
				} else if (isset($relation->prefix)) {
					if (NameMaker::isSingular($relation->prefix)) {
						$reciproqueRelation = new TplRelationReferedByOne($relation->targetDBTableName, $dbTable,
								$alias, $relation, $relation->getReferenceField(), $relation->prefix);
					} else {
						$reciproqueRelation = new TplRelationReferedByMany($relation->targetDBTableName, $dbTable,
								$alias, $relation, $relation->getReferenceField(), $relation->prefix);
					}
				} else {
					$reciproqueRelation = new TplRelationReferedByMany($relation->targetDBTableName, $dbTable,
							$alias, $relation, $relation->getReferenceField(), null);
				}

				$reciproqueRelation->constraintName = $constraintName;
				$reciproqueRelation->referencingTableName = $dbTable;
				$reciproqueRelation->referencedTableName = $targetTable;
				$reciproqueName = $reciproqueRelation->referencingAlias =
						isset($relation->reciproqueName) ? $relation->reciproqueName
						: $relation->alias;

		//		$relation->setReciproqueFieldName($reciproqueRelation->getName());
				$relation->reciproque = $reciproqueRelation;

				Logger::dbg(
					"RECIPROQUE: Adding reciproque ({}) $reciproqueRelation->referencingTableName -> "
					. "$reciproqueRelation->referencedTableName as " .
					$reciproqueRelation->getName()
					, get_class($reciproqueRelation)
				);

				$this->hasOneReciproqueRelations[$targetTable][] = $reciproqueRelation;
				$this->tables[$targetTable]->addDirectReciproqueRelation($reciproqueRelation);
			}
		}
	}

	private function discoverSecondaryRelations() {

		$this->secondaryRelations = null;

		foreach ($this->tables as $dbTable => $table) {
			// --- many to many
			if (array_key_exists($dbTable, $this->referencesOneRelations) && count($this->referencesOneRelations[$dbTable]) > 1) {
		//	if (count($referencesOneRelations[$table]) > 1) {

		//    a b c
		// A  o o o		aa
		// B  x o o		ba bb
		// C  x x o		ca cb cc

				$oneRelationCount = count($this->referencesOneRelations[$dbTable]);

		////		if (count($referencesOneRelations[$table]) == 2) {
				if ($oneRelationCount >= 2) {
					for ($i=0,$l=$oneRelationCount-1; $i<$l; $i++) {
						for ($j=1,$l2=$oneRelationCount; $j<$l2; $j++) {

							$leftTable = $this->referencesOneRelations[$dbTable][$i]->targetDBTableName;
							$leftReferencingField = $this->referencesOneRelations[$dbTable][$i]->getReferenceField();
							$leftReciproque = $this->getReciproque($dbTable, $this->referencesOneRelations[$dbTable][$i]);

							$rightTable = $this->referencesOneRelations[$dbTable][$j]->targetDBTableName;
							$rightReferencingField = $this->referencesOneRelations[$dbTable][$j]->getReferenceField();
							$rightReciproque = $this->getReciproque($dbTable, $this->referencesOneRelations[$dbTable][$j]);

							$leftRelationType = null;
							$rightRelationType = null;

		//					if ($leftReciproque instanceof TplRelationReferedByMany
		//							&& $rightReciproque instanceof TplRelationReferedByMany) {
		//
		//						$leftRelationType = $rightRelationType = __NAMESPACE__ . 'TplRelationIndirectHasMany';
		//
		//		//			} else if ($leftReciproque instanceof TplRelationReferencingHasOne
		//		//					&& $rightReciproque instanceof TplRelationReferencingHasOne) {
		//					} else
								if ($leftReciproque instanceof TplRelationReferedByOne
									&& $rightReciproque instanceof TplRelationReferedByOne) {

								$leftRelationType = __NAMESPACE__ . '\TplRelationIndirectHasOne';
								$rightRelationType = __NAMESPACE__ . '\TplRelationIndirectHasOne';

		//		//			} else if ($leftReciproque instanceof TplRelationReferencingHasOne) {
		//					} else if ($leftReciproque instanceof TplRelationReferedByOne
		//							&& $rightReciproque instanceof TplRelationReferedByMany) {
		//
		//						$leftRelationType = __NAMESPACE__ . 'TplRelationIndirectHasOne';
		//						$rightRelationType = __NAMESPACE__ . 'TplRelationIndirectHasMany';
		//
		//					} else if ($leftReciproque instanceof TplRelationReferedByMany
		//							&& $rightReciproque instanceof TplRelationReferedByOne) {
		//
		//						$leftRelationType = __NAMESPACE__ . 'TplRelationIndirectHasMany';
		//						$rightRelationType = __NAMESPACE__ . 'TplRelationIndirectHasOne';
							}

							if ($leftRelationType !== null && $rightRelationType !== null) {
								$leftRelation = new $leftRelationType(
										null, $leftTable, $rightTable, null, $dbTable, $leftReferencingField,
										$rightReferencingField);

								$rightRelation = new $rightRelationType(
										null, $rightTable, $leftTable, $leftRelation, $dbTable, $rightReferencingField,
										$leftReferencingField);

								Logger::get('CQLIX')->info('Adding secondary relation: ' . $rightRelation);
								Logger::get('CQLIX')->warn('Automatic secondary relations handling is still EXPERIMENTAL');

								$this->secondaryRelations[$rightTable][] = $rightRelation;
								$leftRelation->setReciproque($rightRelation);
								$this->secondaryRelations[$leftTable][] = $leftRelation;
							}
						}
					}
				} else {
					// May only be a secondary assoc
				}
		//		for ($i = 0; $i < count($hasOneRelations[$table]) - 1; $i++) {
		//			for ($j = $i+1; $j < count($hasOneRelations[$table]); $j++) {
		//
		//				$leftTable = $hasOneRelations[$table][$i]->targetDBTableName;
		//				$leftReferencingField = $hasOneRelations[$table][$i]->getReferenceField();
		//				$leftAlias = $hasOneRelations[$table][$i]->getAlias();
		//				$leftReciproque = $this->getReciproque($table, $hasOneRelations[$table][$i]);
		//
		//				$rightTable = $hasOneRelations[$table][$j]->targetDBTableName;
		//				$rightReferencingField = $hasOneRelations[$table][$j]->getReferenceField();
		//				$rightAlias = $hasOneRelations[$table][$j]->getAlias();
		//				$rightReciproque = $this->getReciproque($table, $hasOneRelations[$table][$j]);
		//
		//				echo $leftReciproque . PHP_EOL;
		//				echo $rightReciproque . PHP_EOL;
		//
		////				if ($tableFields[$leftTable][$leftReferencingField]->isUnique()) {
		//				new ModelRelationManyToMany($tableName, $foreignKeyField, $table);
		//			}
		//		}
			}
		}

		foreach ($this->secondaryRelations as $dbTable => $relations) {
			foreach ($relations as $relation) {
				$table = $this->tables[$dbTable];
				$table->addSecondaryRelation($relation);
			}
		}
	}

	private function configureTables() {
		foreach ($this->tables as $dbTable => $table) {
			$config = isset($this->modelsConfig[$dbTable]) ?
					$this->modelsConfig[$dbTable] : null;
			$table->configure($config);
		}
	}

/**
	 * @param array $fields
	 * @return ModelColumn
	 */
	private static function getPrimaryField($fields) {
		foreach ($fields as $field) {
			if ($field->isPrimary()) return $field;
		}
		return null;
	}

	function tplModel($tableName, $fields) {
		$modelName = NameMaker::modelFromDB($tableName);
		$package = APP_NAME;
		ob_start();
		include $this->tplPath . 'Model.tpl.php';
		return ob_get_clean();
	}

	private static $excludedFields = array(
//		'deleted'
	);

	function tplModelBase($table, $fields) {

		$modelInfos = $this->baseConfig->buildModelInfo($table);
		extract($modelInfos);

//		$modelName = NameMaker::modelFromDB($table);
//		$tableName = NameMaker::tableFromDB($table);
		$primaryField = self::getPrimaryField($fields);
		$package = APP_NAME;

		$proxyMethods = $this->proxyModelMethods;
		foreach ($proxyMethods as &$method) {
			$method = str_replace('%%ModelTable%%', $tableName, $method);
			$method = str_replace('%%Model%%', $modelName, $method);
		} unset($method);

		foreach (array_keys($fields) as $name) {
			if (in_array($name, self::$excludedFields, true)) {
				unset($fields[$name]);
			}
		}

		$hasEnum = false;
		$enumLabels = array();
		foreach ($fields as $field) {
			if ($field->isEnum()) {
				$hasEnum = true;
				foreach ($field->enumLabels as $code => $label) {
					$enumLabels[$field->getName()][$code] = $label;
				}
			}
		}

		$relations = $this->allRelations[$table];

		if (!is_array($relations)) $relations = array();

		ob_start();
		include $this->tplPath . 'ModelBase.tpl.php';
		return ob_get_clean();
	}

	public static function makeEnumConstName($field, $code) {
		if ($field instanceof TplField) $field = $field->getName();
		return 'VE_' . strtoupper($field) . "_$code";
	}

	function tplTable($tableName, $fields) {
		$className = NameMaker::tableFromDB($tableName);
		$package = APP_NAME;
		ob_start();
		include $this->tplPath . 'ModelTable.tpl.php';
		return ob_get_clean();
	}

	function tplTableProxy($tableName, $fields) {
		$tpl = Template::create()->setFile($this->tplPath . 'ModelTableProxy.tpl.php');
		$this->tplSetTableBaseVars($tpl, $tableName, $fields);
		return $tpl->render(true);
	// <editor-fold defaultstate="collapsed" desc="REM">
	//	$modelName = NameMaker::modelFromDB($tableName);
	//	$className = NameMaker::tableFromDB($tableName);
	//	$primaryField = self::getPrimaryField($fields);
	//	$primaryColName = $primaryField !== null ? $primaryField->getName() : null;
	//
	//	g lobal $proxyTableMethods;
	//	$proxyMethods = $proxyTableMethods;
	//
	//	foreach ($proxyMethods as &$method) {
	//		$method = str_replace('%%ModelTable%%', $className, $method);
	//		$method = str_replace('%%Model%%', $modelName, $method);
	//	} unset($method);
	//
	//	g lobal $allRelations;
	//	$relations = $allRelations[$tableName];
	//	if (!is_array($relations)) $relations = array();
	//
	//	$package = APP_NAME;
	//	ob_start();
	//	include 'model_tpl' . DS . 'ModelTableProxy.tpl.php';
	//	return ob_get_clean();
	// </editor-fold>
	}

	function tplSetTableBaseVars(Template &$tpl, $tableName, $fields) {

		$modelInfos = $this->baseConfig->buildModelInfo($tableName);

//		$modelName = NameMaker::modelFromDB($tableName);
//		$className = NameMaker::tableFromDB($tableName);
		$modelName = $modelInfos['modelName'];
		$className = $modelInfos['tableName'];

		$tpl->merge(array(
//			'tableName' => $tableName,
			'fields' => $fields,
//			'modelName' => $modelName,
			'className' => $className,
			'primaryField' => self::getPrimaryField($fields),
			'primaryColName' => self::getPrimaryField($fields) !== null ? self::getPrimaryField($fields)->getName() : null
		));

		$tpl->merge($modelInfos);

		$tpl->proxyMethods = $this->proxyTableMethods;

		foreach ($tpl->proxyMethods as &$method) {
			$method = str_replace('%%ModelTable%%', $className, $method);
			$method = str_replace('%%Model%%', $modelName, $method);
		} unset($method);

		$tpl->relations = $this->allRelations[$tableName];
		if (!is_array($tpl->relations)) $tpl->relations = array();

		$tpl->package = APP_NAME;
	}

	function tplTableBase($tableName, $fields) {
		$tpl = Template::create()->setFile($this->tplPath . 'ModelTableBase.tpl.php');
		$this->tplSetTableBaseVars($tpl, $tableName, $fields);
		return $tpl->render(true);
	}

	function tplModelQuery($tableName, $fields) {
		$tpl = Template::create()->setFile($this->tplPath . 'ModelQuery.tpl.php');
		$this->tplSetTableBaseVars($tpl, $tableName, $fields);

		$parentClassReflection = new ReflectionClass('Query');

		$queryMethods = array();
		foreach ($parentClassReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			$method instanceof ReflectionMethod;
			if (preg_match('/@return\s+Query\s/', $method->getDocComment())) {

				$this->rebuildReflectionMethodParams($method, $paramsDeclaration);
				$queryMethods[] = $method->getName() . '(' . $paramsDeclaration . ')';
			}
		}

		$tpl->queryMethods = $queryMethods;

		return $tpl->render(true);
	}

	function rebuildReflectionMethodParams(ReflectionMethod $method,
			&$paramsDeclaration, &$paramsPass = null) {

		$passingParams = array();
		$paramsDeclaration = array();
		foreach ($method->getParameters() as $p) {
			$p instanceof ReflectionParameter;

			$passingParams[] = '$' . $p->getName();

			$s = '';
			$s .= $p->getClass() !== null ? $p->getClass()->getName() . ' ' : '';
			$s .= $p->isArray() ? 'array ' : null;
			$s .= $p->isPassedByReference() ? '&' : '';
			$s .= '$' . $p->getName();
			if ($p->isDefaultValueAvailable()) {
				$s .= ' = ';
				$v = $p->getDefaultValue();
				if ($v === null) $s .= 'null';
				else if (is_string($v)) $s .= "'" . addcslashes($v, "'") . "'";
				else if ($v === true) $s .= 'true';
				else if ($v === false) $s .= 'false';
				else if (is_array($v)) $s .= 'array()';
				else $s .= $v;
			}
			$paramsDeclaration[] = $s;
		}

		$paramsDeclaration = implode(', ', $paramsDeclaration);
		$paramsPass = implode(', ', $passingParams);
	}

	function generateProxyTableMethods($parentClassName) {

		$proxyTableMethods = array();

		$parentClassReflection = new ReflectionClass($parentClassName);

		foreach ($parentClassReflection->getMethods(ReflectionMethod::IS_PROTECTED) as $method) {
			if (!$method->isConstructor()
					&& !$method->isDestructor()
					&& (!$method->isStatic() || $parentClassName == 'Model')
					&& !$method->isDeprecated()
					&& (substr($method->getName(), 0, 1) == '_' && substr($method->getName(), 1, 1) != '_')) {

				$method instanceof ReflectionMethod;

				$this->rebuildReflectionMethodParams($method, $paramsDeclaration, $paramsPass);

				// doc
				$doc = $method->getDocComment();
				$docLines = explode(PHP_EOL, $doc);
				foreach ($docLines as $i => $line) {
					if (strstr($line, '@ignore') !== false) unset($docLines[$i]);
				}
				$doc = implode(PHP_EOL, $docLines);

				ob_start();
				include $this->tplPath . $parentClassName . 'ProxyMethod.tpl.php';
				$proxyTableMethods[] = ob_get_clean();
			}
		}

		return $proxyTableMethods;
	}

	const GUESS_BY_NAME = 0;
	const GUESS_BY_CONSTRAINT = 1;

	private function addHasOneRelation($table, TplRelationReferencesOne $relation, $method = self::GUESS_BY_NAME) {

		if (isset($this->referencesOneRelations[$method][$table][$relation->targetDBTableName][$relation->referenceField])) {
			$current = $this->referencesOneRelations[$method][$table][$relation->targetDBTableName][$relation->referenceField];
			if (false === $current instanceof TplRelation) {
				throw new IllegalStateException('Illegal type: ' . $current);
			} else if (!$relation->equals($current)) {
				$msg = 'Relation conflicts with: ' + $this->referencesOneRelations[$method][$table][$relation->targetDBTableName][$relation->referenceField];
				throw new IllegalStateException($msg);
			}
		} else {
			$this->referencesOneRelations[$method][$table][$relation->targetDBTableName][$relation->getReferenceField()] = $relation;
		}
	}

	private function mergeRelationsFoundByNameAndByFK($guessByName = true, $guessByConstraints = true) {

		if ($guessByName === false) {
			$this->referencesOneRelations = $this->referencesOneRelations[self::GUESS_BY_NAME];
		} else if ($guessByConstraints === false) {
			$this->referencesOneRelations = $this->referencesOneRelations[self::GUESS_BY_CONSTRAINT];
		} else {

			$tmp = array();

			foreach ($this->referencesOneRelations[self::GUESS_BY_NAME] as $table => $otherTableRelations) {
				foreach ($otherTableRelations as $otherTable => $localFieldsRelations) {
					foreach ($localFieldsRelations as $localField => $relation) {
						if (false == $relation instanceof TplRelationReferencesOne) throw new IllegalStateException();
						// Check table integrity
						if ($relation->targetDBTableName !== $otherTable
								|| $relation->getReferenceField() !== $localField) throw new IllegalStateException(
										sprintf('%s !== %s || %s !== %s', $relation->targetDBTableName, $otherTable,
												$relation->getReferenceField(), $localField)
										);
						// Check name/constraint integrity
						if (!isset($this->referencesOneRelations[self::GUESS_BY_CONSTRAINT][$table][$otherTable][$localField])) {
							Logger::warn('Missing foreign key constraint for relation between {}.{} and {}.{}',
									$table, $localField, $otherTable, $this->primaryKeys[$otherTable]);
						} else if (!$relation->equals($this->referencesOneRelations[self::GUESS_BY_CONSTRAINT][$table][$otherTable][$localField])) {
							$nameRelation = $relation;
							$constraintRelation = $this->referencesOneRelations[self::GUESS_BY_CONSTRAINT][$table][$otherTable][$localField];
							throw new IllegalStateException('Conflict between name and constraint relations: ' . PHP_EOL
									. "By name: $table.{$nameRelation->getReferenceField()} -> "
									. "{$nameRelation->targetDBTableName}.{$this->primaryKeys[$nameRelation->targetDBTableName]} "
									. "as {$nameRelation->getAlias()}" . PHP_EOL
									. "By constraint: $table.{$constraintRelation->getReferenceField()} -> "
									. "{$constraintRelation->targetDBTableName}.{$this->primaryKeys[$constraintRelation->targetDBTableName]} "
									. "as {$constraintRelation->getAlias()}");
						}

						$tmp[$table][$otherTable][$localField] = $relation;
					}
				}
			}

			foreach ($this->referencesOneRelations[self::GUESS_BY_CONSTRAINT] as $table => $otherTableRelations) {
				foreach ($otherTableRelations as $otherTable => $localFieldsRelations) {
					foreach ($localFieldsRelations as $localField => $relation) {
						if (false == $relation instanceof TplRelationReferencesOne) throw new IllegalStateException();
						// Check table integrity
						if ($relation->targetDBTableName !== $otherTable
								|| $relation->getReferenceField() !== $localField) throw new IllegalStateException();
						// Check name/constraint integrity
						if (!isset($this->referencesOneRelations[self::GUESS_BY_NAME][$table][$otherTable][$localField])) {
							Logger::info('Field names with foreign key constraint mismatch: {}.{} refering {}.{}',
									$table, $localField, $otherTable, $this->primaryKeys[$otherTable]);

							$tmp[$table][$otherTable][$localField] = $relation;
						} else if (!$relation->equals($this->referencesOneRelations[self::GUESS_BY_NAME][$table][$otherTable][$localField])) {
							throw new IllegalStateException('Conflict between name and constraint for relation between '
									. "$table.$localField and $otherTable." . $this->primaryKeys[$otherTable]);
						}
					}
				}
			}

			$this->referencesOneRelations = $tmp;
		}

		$tmp = array();

		foreach ($this->referencesOneRelations as $table => $otherTableRelations) {
			foreach ($otherTableRelations as $otherTable => $localFieldsRelations) {
				foreach ($localFieldsRelations as $localField => $relation) {
					$tmp[$table][] = $relation;
				}
			}
		}

		$this->referencesOneRelations = $tmp;
	}

	private function discoverPrimaryKeys() {
		
		$this->primaryKeys = array();

		foreach ($this->tableFields as $table => $fields) {

			$secondaryKeyPatterns[$table] = array();

			foreach ($fields as $field) {
				$field instanceof TplField;
				if ($field->isPrimary()) {
					if (array_key_exists($table, $this->primaryKeys)) throw new IllegalStateException('Multiple primary key in table ' . $table);
					$this->primaryKeys[$table] = $field->getName();
				}
			}
		}

		return $this->primaryKeys;
	}

	private function discoverTableReferencesOneRelations($tableName, $fields, $guessByColName = true,
			$guessByForeignKeys = true, $detectSecondaryRelations = false) {

		Logger::setDefaultContext($tableName);
		Logger::info('Search relations');

		static $primaryKeyPatterns = null, $secondaryKeyPatterns = null;

		if ($detectSecondaryRelations) throw new UnsupportedOperationException();

		// Generate primary keys
		if ($primaryKeyPatterns === null) {

			$primaryKeyPatterns = array();
			$secondaryKeyPatterns = array();

			foreach ($this->tableFields as $otherTable => $myFields) {

				$secondaryKeyPatterns[$otherTable] = array();

				foreach ($myFields as $field) {
					$field instanceof TplField;
					if ($field->isPrimary()) {
						$primaryKeyPatterns[$otherTable] = preg_quote($otherTable) . '_' . preg_quote($field->getName());
	//					$primaryKeyPatterns[$otherTable] = '/(?:^|_)' . $primaryKeyPatterns[$otherTable] . '$/';
						$primaryKeyPatterns[$otherTable] = '/^(?:(\w+)_)?' . $primaryKeyPatterns[$otherTable] . '$/';
					} else if ($detectSecondaryRelations) {
						$pattern = preg_quote($otherTable) . '_' . preg_quote($field->getName());
						$pattern = '/(?:^|_)' . $pattern . '$/';
						$secondaryKeyPatterns[$otherTable][$field->getName()] = $pattern;
					}
				}
			}
		}

		// Guess by column names
		foreach ($fields as $field) {

			$field instanceof TplField;
			$fieldName = $field->getName();

			if ($guessByColName) {

				$found = null;

				foreach ($primaryKeyPatterns as $otherTable => $pattern) {
	//				Logger::dbg('Trying {}.{} against {} for {}', $tableName, $fieldName, $pattern, $otherTable);
					if (preg_match($pattern, $fieldName, $match)) {

						$prefix = isset($match[1]) ? $match[1] : null;
						//print_r($match);

						// field is a foreignKey to $table
						$otherField = $this->primaryKeys[$otherTable];

						$rel = new TplRelationReferencesOne(
							$tableName,
							$otherTable,
							$field->localRelationAlias,
							null,
							$fieldName,
							$prefix
						);

						Logger::info('By name: found {}.{} as {} refers to {}.{}',
								$tableName, $fieldName, $rel->getName(), $otherTable, $this->primaryKeys[$otherTable]);

						$found[] = $rel;
					}
				}

				if ($found !== null) {
					$longest = array_pop($found);
					foreach ($found as $rel) {
						$l1 = strlen($rel->targetDBTableName);
						$l2 = strlen($longest->targetDBTableName);
						if ($l1 > $l2) {
							$longest = $rel;
						} else if ($l1 === $l2) {
							throw new IllegalStateException('Cannot decipher the target table');
						}
					}
					$this->addHasOneRelation($tableName, $longest, self::GUESS_BY_NAME);
				}

				if ($detectSecondaryRelations) {
					throw new UnsupportedOperationException();
					foreach ($secondaryKeyPatterns as $otherTable => $otherFields) {
						foreach ($otherFields as $otherField => $pattern) {
							if (preg_match($pattern, $field->getName())) {

							}
						}
					}
				}
			}
		}

		if ($guessByForeignKeys) {
			foreach ($this->tableFields[$tableName] as $field) {
				if ($field->hasForeignConstraint()) {

					$fieldName = $field->getName();
					$otherTable = $field->getForeignConstraint()->targetTable;
					$otherField = $field->getForeignConstraint()->targetField;

					$prefix = null;
					$alias = null;

					// Decide what is the alias
					if (preg_match($primaryKeyPatterns[$otherTable], $fieldName, $match)) {
						// If the referencing field is in the form xxx_table_id,
						// then xxx is the alias (this is congruent with the search
						// by name)
						if (isset($match[1])) $prefix = $match[1];
	//REM					if (isset($match[1])) $alias = ModelRelationReferencingHasOne::makeAlias($otherTable, $match[1]);
	//REM					else $alias = null;
					} else {
						// Else, the whole referencing field name should be
						// considered the alias...
						$alias = $fieldName;
					}

					if ($otherField !== $this->primaryKeys[$otherTable]) {
						Logger::warn('Foreign Key constraint found on non-primary key ' + $otherField + ' in table ' + $otherTable);
					} else {
						$rel = new TplRelationReferencesOne($tableName,
								$otherTable, $alias, null, $fieldName, $prefix);

						$this->addHasOneRelation($tableName, $rel, self::GUESS_BY_CONSTRAINT);

						Logger::info('By constraints: found {}.{} as {} refers to {}.{}',
								$tableName, $fieldName, $rel->getName(), $otherTable, $otherField);
					}
				}
			}
	// <editor-fold defaultstate="collapsed" desc="REM">
	//		$createTable = Query::executeQuery('SHOW CREATE TABLE `' . $tableName . '`;');
	//		$createTable = $createTable->fetchColumn(1);
	//
	//		preg_match('/\s+ENGINE\s*=\s*(\w+)\s+/', $createTable, $matches);
	//		$engine = $matches[1];
	//
	//		if (strtolower($engine) !== 'innodb') {
	//			Logger::warn('Table {} engine is not InnoDB (it is {})', $tableName, $engine);
	//		} else {
	//			$pattern = '/\sFOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)/';
	//			preg_match_all($pattern, $createTable, $matches, PREG_SET_ORDER);
	//
	//			foreach ($matches as $match) {
	//				list($ignore, $localKey, $otherTable, $otherField) = $match;
	//
	//				Logger::info('By constraints: found {}.{} refers to {}.{}',
	//						$tableName, $localKey, $otherTable, $otherField);
	//
	//				if ($otherField !== $primaryKeys[$otherTable]) {
	//					Logger::warn('Foreign Key constraint found on non-primary key ' + $otherField + ' in table ' + $otherTable);
	//				} else {
	//					$this->addHasOneRelation($tableName, new ModelRelationReferencingHasOne($otherTable, $localKey), self::GUESS_BY_CONSTRAINT);
	//				}
	//			}
	//		}
	// </editor-fold>
		}

		Logger::setDefaultContext('');
	}

	function getReciproque($table, TplRelationReferencesOne $relation) {

		foreach ($this->hasOneReciproqueRelations[$relation->targetDBTableName] as $r) {
			if ($r->targetDBTableName === $table
					&& $r->getReferenceField() === $relation->getReferenceField()
					) return $r;
		}

		throw new IllegalStateException();
	}

	function writeFile($filename, $replace, $callback, $params) {

		if (!$replace && file_exists($filename)) {
			echo 'Passing existing file: ' . $filename . PHP_EOL;
			return;
		}

		$file = fopen($filename, 'w');

		if ($file === false) throw new SystemException('Cannot open file "' . $filename . '" for writting');

		echo 'Creating file: "' . $filename . '" ... ';

		$content = "<?php\n\n" . call_user_func_array($callback, $params);
		fwrite($file, $content, strlen($content));

		fclose($file);

		global $fileWritten;
		$fileWritten++;
		echo 'OK' . PHP_EOL;
	}
}
