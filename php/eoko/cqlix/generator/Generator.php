<?php

namespace eoko\cqlix\generator;

use eoko\script\Script;
use eoko\database\Database;
use eoko\template\Template;
use eoko\plugin\PluginManager;
use eoko\config\ConfigManager;
use eoko\config\Paths;

use PDO;
use ModelColumn;
use ReflectionClass, ReflectionMethod;
use Logger, Debug;
use SystemException, IllegalStateException, IllegalArgumentException, UnsupportedOperationException;
use ConfigurationException;

use eoko\cqlix\Exception;

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

	const EVT_BEFORE = 'before';

	/**
	 * @var Database
	 */
	protected $database;

	private $databaseName;

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

	private $modelProcessed = 0,
		$fileCreated = 0,
		$fileSkipped = 0;

	public $addTimeVersionInGeneratedFiles = false;

	private $config;

	protected $databaseProxyName = null;

	/**
	 * @var \eoko\util\FilePathResolver
	 */
	protected $paths = array(
		'base' => ':model/Base',
		'proxy' => ':model/Proxy',
	);

	/**
	 * @var ClassLookup
	 */
	protected $classLookup;

	/**
	 * @var string
	 */
	protected $modelNamespace = null;

	public function __construct() {

		// We want exceptions to show off at the face of the user, not being
		// wrapped in Ext compliant errors as done by the default application
		// exception handler!
		restore_exception_handler();

		// Includes all relation templates classes
		RelationTemplates::load();

		if (!$this->database) {
			$this->database = Database::getDefault();
		}

		$this->classLookup = new ClassLookup($this->modelNamespace);

		$this->config = ConfigManager::get('eoko/cqlix/Generator');

		$this->tplPath = __DIR__ . DS . 'templates' . DS;

		// --- Paths

		$this->paths = new Paths($this->paths);


		// TODO
		// I think this is not really used, and its initial objective has been
		// superseeded by the new config implementation
		$this->baseConfig = new Config();

		if (defined('APP_NAMESPACE')) {
			throw new \DeprecatedException('Set the namespace in eoze/application/namespace');
		}
//		$ns = defined(APP_NAMESPACE) ? APP_NAMESPACE : ConfigManager::get($node, $key);

		$this->modelsConfig = ConfigManager::get(
			ConfigManager::get('eoze/application/namespace') . '/cqlix/models'
		);
	}

	protected function run() {

		$startTime = microtime();

		// --- Configure

		// Database
		$this->databaseName = $this->database->getDatabaseName();

		// Prepare paths
		$this->prepareOutputDirectories();

// REM
////		 Guess namespaces
//		$this->guessNamespaces();
//		$this->classLookup->setNamespace(array(
//			'model' => $this->modelNamespace,
//			'modelBase' => $this->baseNamespace,
//			'table' => $this->modelNamespace,
//			'tableBase' => $this->baseNamespace,
//			'proxy' => $this->proxyNamespace,
//		));


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

		$this->configureRelations();

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

		list($modelPath, $modelBasePath, $proxyPath) = $this->paths->resolve(array('model', 'base', 'proxy'));

		foreach ($this->tableFields as $table => $fields) {

			$modelName = NameMaker::modelFromDB($table);
			$tableName = NameMaker::tableFromDB($table);

			$modelFilename = $modelPath . $modelName . '.php';
			$modelBaseFilename = $modelBasePath . $modelName . 'Base' . '.php';

			$tableFilename = $modelPath . $tableName . '.php';
			$tableBaseFilename = $modelBasePath . $tableName . 'Base' . '.php';
			$tableProxyFilename = $proxyPath . $tableName . 'Proxy' . '.php';

			$params = array($table, $fields);

			$this->writeFile($tableProxyFilename, true, array($this, 'tplTableProxy'), $params);
			$this->writeFile($tableBaseFilename, true, array($this, 'tplTableBase'), $params);
			$this->writeFile($tableFilename, false, array($this, 'tplTable'), $params);
			$this->writeFile($modelBaseFilename, true, array($this, 'tplModelBase'), $params);
			$this->writeFile($modelFilename, false, array($this, 'tplModel'), $params);

			$this->modelProcessed++;
		}

		$time = Debug::elapsedTime($startTime, microtime());
		$msg = sprintf('DONE -- %.2fs', $time) . PHP_EOL
			. "$this->fileCreated files created, $this->fileSkipped existing files have been skipped";

		echo PHP_EOL . $msg . PHP_EOL . PHP_EOL;
	}

	private $outputDirectoriesPermissions = 0744;

	/**
	 * Creates output directories as needed, ensures that existing output directory are actual directories
	 * (not plain files), and empties cache output directories (i.e. base & proxy).
	 *
	 * @throws \eoko\cqlix\Exception\RuntimeException
	 */
	private function prepareOutputDirectories() {

		$modelPath = $this->paths->resolve('model');

		if (file_exists($modelPath)) {
			if (!is_dir($modelPath)) {
				throw new Exception\RuntimeException("Model output directory is a plain file: $modelPath");
			}
		} else {
			mkdir($modelPath, $this->outputDirectoriesPermissions);
		}

		// Clean base & proxy paths
		foreach ($this->paths->resolve(array('base', 'proxy')) as $dir) {
			if (file_exists($dir)) {
				if (is_dir($dir)) {
					foreach (glob($dir . '*.php') as $file) {
						unlink($file);
					}
				} else {
					throw new Exception\RuntimeException("Output directory is a plain file: $modelPath");
				}
			} else {
				mkdir($dir, $this->outputDirectoriesPermissions);
			}
		}
	}

// REM
//	/**
//	 * Guess namespaces based on {@link modelNamespace} and {@link paths}.
//	 *
//	 * Must be called *after* all output directories are resolvable.
//	 */
//	private function guessNamespaces() {
//		if (isset($this->modelNamespace)) {
//
//			$this->paths->resolve('model');
//			$this->paths->resolve('proxy');
//
//			list($modelPath, $basePath, $proxyPath) = $this->paths->resolveReal(array('model/', 'base', 'proxy'));
//
//			$modelPath = $this->paths->resolveReal('model/');
//			$modelPathLength = strlen($modelPath);
//
//			foreach (array('proxy', 'base') as $dir) {
//				$property = $dir . 'Namespace';
//				$path = $this->paths->resolveReal($dir);
//
//				if (!isset($this->$property) && $path) {
//					if (substr($path, 0, $modelPathLength) === $modelPath) {
//						$this->$property = $this->modelNamespace . '\\' . substr($path, $modelPathLength);
//					}
//				}
//			}
//		}
//	}

	private function discoverTables() {

		$tables = $this->database->query("SHOW TABLES FROM `$this->databaseName`;")
				->fetchAll(PDO::FETCH_COLUMN);

		$this->tables = array();
		foreach ($tables as $table) {
			$this->tables[$table] = new TplTable($this->classLookup, $table);
		}
	}

	private function discoverTablesFields() {

		$this->tableFields = array();

		foreach ($this->tables as $dbTable => $table) {

			echo "Reading: `$dbTable`" . PHP_EOL;
			$result = $this->database->query("SHOW COLUMNS FROM `$dbTable` FROM `$this->databaseName`;");
			$result = $result->fetchAll(PDO::FETCH_NAMED);

			$fields = array();
			foreach ($result as $r) {

				$field = $r['Field'];

				// type
				$regex = '/^' 
						. '(?P<type>[^\s(]+)' 
						. '\s*'
						. '(?:\((?P<length>[\d,]+)\))?'
						. '(?P<unsigned>\s*unsigned)?'
						. '$/';
				if (!preg_match($regex, $r['Type'], $matches)) {
					throw new IllegalStateException("Cannot parse type: '$r[Type]'");
				}
				$type = $matches['type'];
				$length = isset($matches['length']) ? $matches['length'] : null;

				$default = $r['Default'] != '' ? $r['Default'] : null;
				$primaryKey = stristr($r['Key'], 'PRI') !== false ? true : false;
				$autoIncrement = stristr($r['Extra'], 'auto_increment') !== false ? true : false;
				$canNull = $r['Null'] == 'YES' ? true : false;

				$tplField = new TplField($this->classLookup, $field, $type, $length, $canNull, $default,
						false, null, $primaryKey, $autoIncrement);

				if (isset($matches['unsigned'])) {
					$tplField->unsigned = true;
				}

				$fields[$field] = $tplField;
			}

			// --- Index ---

			$createTable = $this->database->query('SHOW CREATE TABLE `' . $dbTable . '`;');
			$createTable = $createTable->fetchColumn(1);

			if (!preg_match('/(?:,|\s)ENGINE\s*=\s*(\w+)\s+/', $createTable, $matches)) {
				throw new IllegalStateException('Cannot parse ENGINE');
			}
			$engine = $matches[1];

			if (strtolower($engine) !== 'innodb') {
				Logger::get($this)->warn('Table {} engine is not InnoDB (it is {})', $dbTable, $engine);
			} else {
				// CONSTRAINT `contact_phone_numbers_ibfk_1` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE

				$table->setEngineAutomaticCascade(true);

				// Foreign key constraints
				$pattern = '/\bCONSTRAINT `(\w+)` FOREIGN KEY \(`(\w+)`\) '
						. 'REFERENCES `(\w+)` \(`(\w+)`\)'
						. '(?: ON DELETE (?P<onDelete>\w+))?'
						. '(?: ON UPDATE (?P<onUpdate>\w+))?'
						. '/';
				$pattern = '/'
					. '\bCONSTRAINT `(\w+)` FOREIGN KEY \(`(\w+)`\) '
					. 'REFERENCES `(\w+)` \(`(\w+)`\)'
					. '(?: ON DELETE (?P<onDelete>CASCADE|SET NULL|RESTRICT|NO ACTION))?'
					. '(?: ON UPDATE (?P<onUpdate>CASCADE|SET NULL|RESTRICT|NO ACTION))?'
					. '/';

				preg_match_all($pattern, $createTable, $matches, PREG_SET_ORDER);

				foreach ($matches as $matches) {
					list($ignore, $constraintName, $localKey, $constraintTable, $otherField) = $matches;
					$constraint = new ModelColumnForeignConstraint($constraintTable, $otherField, $constraintName);
					$fields[$localKey]->foreignConstraint = $constraint;

					$constraint->onDelete = isset($matches['onDelete']) 
							? $matches['onDelete']
							: 'RESTRICT'; // default action, not shown in SHOW CREATE TABLE
					$constraint->onUpdate = isset($matches['onUpdate'])
							? $matches['onUpdate']
							: 'RESTRICT';
				}
			}

			// Unique index
			$pattern = '/(?:\s|,)UNIQUE KEY `(?:\w+)` \(`(\w+)`\)/';
			preg_match_all($pattern, $createTable, $matches, PREG_SET_ORDER);

			foreach ($matches as $matches) {
				$uniqueField = $matches[1];
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

	private function getTableConfig($table, $key = null, $default = null) {
		if (isset($this->modelsConfig[$table][$key])) {
			return $this->modelsConfig[$table][$key];
		} else {
			return $default;
		}
	}

	private function discoverDirectReferencingOneRelations() {

		$this->referencesOneRelations = array(
			self::GUESS_BY_NAME => array(),
			self::GUESS_BY_CONSTRAINT => array()
		);

		foreach ($this->tableFields as $table => $fields) {
			$this->discoverTableReferencesOneRelations(
				$table, 
				$fields, 
				$this->getTableConfig($table, 'guessByName', true),
				$this->getTableConfig($table, 'guessByConstraint', true)
			);
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

				if (isset($relation->reciproqueName)) {
					$alias = $relation->reciproqueName;
				} else {
					$alias = $this->tableFields[$dbTable][$field]->foreignRelationAlias;
				}

				if ($alias === false) {
					continue;
				}

				if (isset($relation->reciproqueConfig['unique'])) {
					$unique = $relation->reciproqueConfig['unique'];
				}
				else if (isset($relation->reciproqueConfig['uniqueBy'])) {
					$unique = true;
				}
				else {
					// TplField will return true for isUnique if the field is a primary key
					// (unless overriden by config or else)
						// Primary fields cannot be considered unique safely because of 
						// multiple fields primary keys...
					$unique = $this->tableFields[$dbTable][$field]->isUnique();
				}

				if ($unique) {
					$reciproqueRelation = new TplRelationReferedByOne($this->classLookup, $relation->targetDBTableName, $dbTable,
							$alias, $relation, $relation->getReferenceField(), null);
				} else if (isset($relation->prefix)) {
					if (NameMaker::isSingular($relation->prefix)) {
						$reciproqueRelation = new TplRelationReferedByOne($this->classLookup, $relation->targetDBTableName, $dbTable,
								$alias, $relation, $relation->getReferenceField(), $relation->prefix);
					} else {
						$reciproqueRelation = new TplRelationReferedByMany($this->classLookup, $relation->targetDBTableName, $dbTable,
								$alias, $relation, $relation->getReferenceField(), $relation->prefix);
					}
				} else {
					$reciproqueRelation = new TplRelationReferedByMany($this->classLookup, $relation->targetDBTableName, $dbTable,
							$alias, $relation, $relation->getReferenceField(), null);
				}

				$reciproqueRelation->constraintName = $constraintName;
				$reciproqueRelation->referencingTableName = $dbTable;
				$reciproqueRelation->referencedTableName = $targetTable;

//				$reciproqueName = isset($relation->reciproqueName) 
//						? $relation->reciproqueName 
//						: $relation->alias;
				$reciproqueRelation->setReferencingAlias($alias);

				if (isset($relation->reciproqueConfig)) {
					$reciproqueRelation->config = $relation->reciproqueConfig;
				}

//				if ($reciproqueName !== false) {
					$relation->reciproque = $reciproqueRelation;

					Logger::dbg(
						"RECIPROQUE: Adding reciproque ({}) $reciproqueRelation->referencingTableName -> "
						. "$reciproqueRelation->referencedTableName as " .
						$reciproqueRelation->getName()
						, get_class($reciproqueRelation)
					);

					$this->hasOneReciproqueRelations[$targetTable][] = $reciproqueRelation;
					$this->tables[$targetTable]->addDirectReciproqueRelation($reciproqueRelation);
//				}
			}
		}
	}

	private function discoverSecondaryRelations() {

		$this->secondaryRelations = array();

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
										$this->classLookup, null, $leftTable, $rightTable, null, $dbTable, $leftReferencingField,
										$rightReferencingField);

								$rightRelation = new $rightRelationType(
										$this->classLookup, null, $rightTable, $leftTable, $leftRelation, $dbTable, $rightReferencingField,
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

	private function configureRelations() {
		foreach ($this->tables as $table) {
			$table->configureRelations();
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

	/**
	 * @param string $tableName
	 * @return TplField
	 */
	public function getTablePrimaryField($tableName) {
		return self::getPrimaryField($this->tableFields[$tableName]);
	}

	private function getVersionString() {
		if ($this->addTimeVersionInGeneratedFiles) {
			return date('Y-m-d h:i:s');
		} else {
			return null;
		}
	}

	/**
	 * @var string
	 */
	protected $baseNamespace = null;
	/**
	 * @var string
	 */
	protected $proxyNamespace = null;

	// Used in the templates
	/**
	 * @var string
	 */
	protected $modelCategory = APP_NAME;
	/**
	 * @var string
	 */
	private $modelPackage = 'Model';
	/**
	 * @var string
	 */
	private $tableSubPackage = 'Table';
	/**
	 * @var string
	 */
	private $baseSubPackage = 'Base';
	/**
	 * @var string
	 */
	private $proxySubPackage = 'Proxy';

	function tplModel($dbTableName, $fields) {
		return $this->createTemplate($dbTableName, 'Model')->render(true);
	}

	private static $excludedFields = array(
//		'deleted'
	);

	function tplModelBase($table, $fields) {

		// -- Proxy methods

		$proxyMethods = $this->proxyModelMethods;
		foreach ($proxyMethods as &$method) {
			$method = str_replace('%%ModelTable%%', $tableName, $method);
			$method = str_replace('%%Model%%', $modelName, $method);
		}
		unset($method);

		// -- Fields

		// Excluded fields
		foreach (array_keys($fields) as $name) {
			if (in_array($name, self::$excludedFields, true)) {
				unset($fields[$name]);
			}
		}

		// Enum fields
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

		// -- Relations

		$relations = $this->allRelations[$table];

		// TODO
		// removing duplicates caused by mirror relations
		if ($relations) {
			foreach ($relations as $i => $rel) {
				foreach ($relations as $i2 => $rel2) {
					if (($i !== $i2) && ($rel->getName() === $rel2->getName())) {
						$yes = 1;
						unset($relations[$i]);
						break;
					}
				}
			}
		} else {
			$relations = array();
		}

		// -- Return

		$variables = array_merge(
			// modelName & tableName
			$this->baseConfig->buildModelInfo($table),
			array(
				'fields' => $fields,
				'primaryField' => self::getPrimaryField($fields),
				'hasEnum' => $hasEnum,
				'enumLabels' => $enumLabels,
				'relations' => $relations,
				'proxyMethods' => $proxyMethods,
			)
		);

		return $this->createTemplate($table, 'ModelBase', $variables)->render(true);
	}

	public static function makeEnumConstName($field, $code) {
		if ($field instanceof TplField) {
			$field = $field->getName();
		}
		if ($code == null) {
			$code = 'NULL';
		}
		return 'VE_' . strtoupper($field) . "_$code";
	}

	private function tplTable($dbTable, $fields) {
		$variables = array(
			'className' => NameMaker::tableFromDB($dbTable),
		);
		return $this->createTemplate($dbTable, 'ModelTable', $variables)->render(true);
	}

	private function tplTableProxy($tableName, $fields) {
		$tpl = $this->createTemplate($tableName, 'ModelTableProxy');
		$this->tplSetTableBaseVars($tpl, $tableName, $fields);
		return $tpl->render(true);
	}

	/**
	 * @param string $name
	 * @param array|null $variables
	 * @return \eoko\template\Template
	 */
	private function createTemplate($dbTable, $name, $variables = null) {

		// --- Create template

		$file = $this->tplPath . $name . '.tpl.php';
		$template = Template::create()->setFile($file);

		// --- Set default variables

		// Classes
		$template->set(array(
			'modelName' => NameMaker::modelFromDB($dbTable),
			'modelClass' => $this->classLookup->modelFromDb($dbTable),
			'modelBaseClass' => $this->classLookup->modelBaseFromDb($dbTable),
			'tableClass' => $this->classLookup->tableFromDb($dbTable),
			'tableBaseClass' => $this->classLookup->tableBaseFromDb($dbTable),
			'proxyClass' => $this->classLookup->proxyFromDb($dbTable),
			'proxyName' => $this->classLookup->proxyFromDb($dbTable, false),
		));

		// Default
		$template->set(array(
			'version' => $this->getVersionString(),

			'modelCategory' => $this->modelCategory,
			'modelPackage' => $this->modelPackage,
			'baseSubPackage' => $this->baseSubPackage,
			'proxySubPackage' => $this->proxySubPackage,
		));

		$template->set('makeEnumConstName', array($this, 'makeEnumConstName'));

		if (isset($this->modelNamespace)) {
			foreach (array('model', 'modelBase', 'table', 'tableBase', 'proxy') as $ns) {
				$template->set($ns . 'Namespace', $this->classLookup->resolveNamespace($ns));
			}
		}

		// Arguments
		$template->set($variables);

		// Proxy
		if (isset($this->databaseProxyName)) {
			$databaseProxyName = str_replace("'", "\\'", $this->databaseProxyName);
			$template->set('databaseProxyName', $databaseProxyName);
		}

		// --- Return

		return $template;
	}

	private function tplSetTableBaseVars(Template &$tpl, $tableName, $fields) {

		$modelInfos = $this->baseConfig->buildModelInfo($tableName);

//		$modelName = NameMaker::modelFromDB($tableName);
//		$className = NameMaker::tableFromDB($tableName);
		$modelName = $modelInfos['modelName'];
		$className = $modelInfos['tableName'];
		$pkName = self::getPrimaryField($fields) !== null ? self::getPrimaryField($fields)->getName() : null;

		$tpl->merge(array(
//			'tableName' => $tableName,
			'table' => $this->tables[$tableName],
			'fields' => $fields,
//			'modelName' => $modelName,
			'className' => $className,
			'primaryField' => self::getPrimaryField($fields),
			'primaryColName' => $pkName,
			'primaryKeyName' => $pkName,
		));

		$tpl->merge($modelInfos);

		$tpl->proxyMethods = $this->proxyTableMethods;

		foreach ($tpl->proxyMethods as &$method) {
			$method = str_replace('%%ModelTable%%', $className, $method);
			$method = str_replace('%%Model%%', $modelName, $method);
		} unset($method);

		$relations = $this->allRelations[$tableName];

		// TODO
		// removing dupplicates caused by mirror relations
		if ($relations) foreach ($relations as $i => $rel) {
			foreach ($relations as $i2 => $rel2) {
				if (($i !== $i2) && ($rel->getName() === $rel2->getName())) {
					unset($relations[$i]);
				}
			}
		}

		$tpl->relations = $relations;

		if (!is_array($tpl->relations)) {
			$tpl->relations = array();
		}
	}

	private function tplTableBase($dbTable, $fields) {
		$tpl = $this->createTemplate($dbTable, 'ModelTableBase');
		$this->tplSetTableBaseVars($tpl, $dbTable, $fields);
		return $tpl->render(true);
	}

	private function rebuildReflectionMethodParams(ReflectionMethod $method,
			&$paramsDeclaration, &$paramsPass = null) {

		$passingParams = array();
		$paramsDeclaration = array();
		foreach ($method->getParameters() as $p) {

			$passingParams[] = '$' . $p->getName();

			$class = $p->getClass();

			$s = '';
			$s .= $class !== null ? '\\' . $class->getName() . ' ' : '';
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

				/** @var ReflectionMethod $method */

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

	const GUESS_BY_NAME       = 0;
	const GUESS_BY_CONSTRAINT = 1;
	const BY_CONFIG           = 3;

	private function addHasOneRelation($table, TplRelationReferencesOne $relation, $method = self::GUESS_BY_NAME) {

		if ($method === self::BY_CONFIG) {
			$this->referencesOneRelations[$method][] = $relation;
		}

		else if (isset($this->referencesOneRelations
				[$method][$table][$relation->targetDBTableName][$relation->referenceField])) {
			$current = $this->referencesOneRelations
					[$method][$table][$relation->targetDBTableName][$relation->referenceField];
			if (false === $current instanceof TplRelation) {
				throw new IllegalStateException('Illegal type: ' . $current);
//			} else if ($method === self::BY_CONFIG) {
//				$r =& $this->referencesOneRelations
//						[$method][$table][$relation->targetDBTableName]
//						[$relation->getReferenceField()];
//				if (!is_array($r)) {
//					$r = array($r);
//				}
//				return $r[] = $relation;
//			} else if (!$relation->equals($current)) {
			} else {
				$msg = 'Relation conflicts with: ' 
						. $this->referencesOneRelations
						[$method][$table][$relation->targetDBTableName][$relation->referenceField];
				throw new IllegalStateException($msg);
			}
		} else {
			return $this->referencesOneRelations
					[$method][$table][$relation->targetDBTableName][$relation->getReferenceField()] 
					= $relation;
		}
	}

	private function mergeRelationsFoundByNameAndByFK($guessByName = true, $guessByConstraints = true) {

		$configRelations = isset($this->referencesOneRelations[self::BY_CONFIG])
				? $this->referencesOneRelations[self::BY_CONFIG] : null;

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
							Logger::get($this)->warn('Missing foreign key constraint for relation between {}.{} and {}.{}',
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

						$tmp[$table][$localField] = $relation;
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
							Logger::get($this)->info('Field names with foreign key constraint mismatch: {}.{} refering {}.{}',
									$table, $localField, $otherTable, $this->primaryKeys[$otherTable]);

							$tmp[$table][$localField] = $relation;
						} else if (!$relation->equals($this->referencesOneRelations[self::GUESS_BY_NAME][$table][$otherTable][$localField])) {
							throw new IllegalStateException('Conflict between name and constraint for relation between '
									. "$table.$localField and $otherTable." . $this->primaryKeys[$otherTable]);
						} else {
							// Guesses by constraint are better than guesses by name, so let's
							// override!
							// (In particular, they may deliver ON_DELETE informations...)
							$tmp[$table][$localField] = $relation;
						}
					}
				}
			}

			$this->referencesOneRelations = $tmp;
		}

		$tmp = array();

		if (isset($configRelations)) {
			foreach ($configRelations as $relation) {
				$tmp[$relation->localDBTableName][] = $relation;
			}
		}

		foreach ($this->referencesOneRelations as $table => $localFieldsRelations) {
			foreach ($localFieldsRelations as $localField => $relation) {
				$tmp[$table][] = $relation;
			}
		}

		$this->referencesOneRelations = $tmp;
	}

	private function discoverPrimaryKeys() {

		$this->primaryKeys = array();

		foreach ($this->tableFields as $table => $fields) {

			$secondaryKeyPatterns[$table] = array();

//			dump($fields);
			foreach ($fields as $field) {
				$field instanceof TplField;
				if ($field->isPrimary()) {
					if (array_key_exists($table, $this->primaryKeys)) {
						if (!is_array($this->primaryKeys[$table])) {
							$this->primaryKeys[$table] = array($this->primaryKeys[$table]);
						}
						$this->primaryKeys[$table][] = $field->getName();
//						throw new IllegalStateException('Multiple primary key in table ' . $table);
					} else {
						$this->primaryKeys[$table] = $field->getName();
					}
				}
			}
		}

		return $this->primaryKeys;
	}

	private function discoverTableReferencesOneRelations($tableName, $fields, $guessByColName = true,
			$guessByForeignKeys = true, $detectSecondaryRelations = false) {

		Logger::setDefaultContext($tableName);
		Logger::get($this)->info('Search relations');

		static $primaryKeyPatterns = null, $secondaryKeyPatterns = null;

		if ($detectSecondaryRelations) throw new UnsupportedOperationException();

		// Generate primary keys
		if ($primaryKeyPatterns === null) {

			$primaryKeyPatterns = array();
			$secondaryKeyPatterns = array();

			foreach ($this->tableFields as $otherTable => $myFields) {

				$secondaryKeyPatterns[$otherTable] = array();

				$quotedOtherTable = '(?:' . preg_quote($otherTable)
						. '|' . NameMaker::singular($otherTable) . ')';

				foreach ($myFields as $field) {
					$field instanceof TplField;
					if ($field->isPrimary()) {
						$primaryKeyPatterns[$otherTable] = $quotedOtherTable . '_' . preg_quote($field->getName());
	//					$primaryKeyPatterns[$otherTable] = '/(?:^|_)' . $primaryKeyPatterns[$otherTable] . '$/';
						$primaryKeyPatterns[$otherTable] = '/^(?:(\w+)_)?' . $primaryKeyPatterns[$otherTable] . '$/';
					} else if ($detectSecondaryRelations) {
						$pattern = $quotedOtherTable . '_' . preg_quote($field->getName());
						$pattern = '/(?:^|_)' . $pattern . '$/';
						$secondaryKeyPatterns[$otherTable][$field->getName()] = $pattern;
// Modified on 20/11/11 03:12 to add singular table name support
//						$primaryKeyPatterns[$otherTable] = preg_quote($otherTable) . '_' . preg_quote($field->getName());
//	//					$primaryKeyPatterns[$otherTable] = '/(?:^|_)' . $primaryKeyPatterns[$otherTable] . '$/';
//						$primaryKeyPatterns[$otherTable] = '/^(?:(\w+)_)?' . $primaryKeyPatterns[$otherTable] . '$/';
//					} else if ($detectSecondaryRelations) {
//						$pattern = preg_quote($otherTable) . '_' . preg_quote($field->getName());
//						$pattern = '/(?:^|_)' . $pattern . '$/';
//						$secondaryKeyPatterns[$otherTable][$field->getName()] = $pattern;
					}
				}
			}
		}

		$excludedFields = array();
		foreach ($this->tables[$tableName]->getConfiguredRelations($excludedFields) as $relation) {
			$this->addHasOneRelation($tableName, $relation, self::BY_CONFIG);
		}

		// Guess by column names
		foreach ($fields as $field) {

			if (in_array($field->getName(), $excludedFields)) {
				continue;
			}

			$field instanceof TplField;
			$fieldName = $field->getName();

			if (null !== $rel = $field->getConfiguredRelation()) {

				$relation = $this->addHasOneRelation($tableName, $rel, self::GUESS_BY_CONSTRAINT);

				// cannot be overriden by guesses...
				continue;
			}

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
							$this->classLookup,
							$tableName,
							$otherTable,
							$field->localRelationAlias,
							null,
							$fieldName,
							$prefix
						);

						Logger::get($this)->info('By name: found {}.{} as {} refers to {}.{}',
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

					$quotedTable = preg_quote($fieldName, '/');
					$quotedOtherTable = preg_quote($otherTable, '/');
					$quotedOtherId = preg_quote(
						$this->getTablePrimaryField($otherTable)->getName(), '/'
					);


					$prefix = null;
					$alias = null;

					// Decide what is the alias
					if (preg_match($primaryKeyPatterns[$otherTable], $fieldName, $match)) {
						// If the referencing field is in the form xxx_table_id,
						// then xxx is the alias (this is congruent with the search
						// by name)
						if (isset($match[1])) {
							$prefix = $match[1];
						}

					} else if (preg_match("/^(?P<alias>.+)(?:$quotedOtherTable)?_$quotedOtherId$/", $fieldName, $matches)) {
						$alias = $matches['alias'];
						$alias = NameMaker::camelCase($alias, true);
					}

					if ($otherField !== $this->primaryKeys[$otherTable]) {
						Logger::get($this)->warn('Foreign Key constraint found on non-primary key ' + $otherField + ' in table ' + $otherTable);
					} else {
						$rel = new TplRelationReferencesOne(
								$this->classLookup,
								$tableName,
								$otherTable,
								$field->localRelationAlias ? $field->localRelationAlias : $alias,
								null,
								$fieldName,
								$prefix);

						$relation = $this->addHasOneRelation($tableName, $rel, self::GUESS_BY_CONSTRAINT);

						if ($field->foreignConstraint) {
							switch ($field->foreignConstraint->onDelete) {
								case 'CASCADE':
									$relation->onDeleteAction = 'DELETE';
									break;
								case 'SET NULL':
									$relation->onDeleteAction = 'SET_NULL';
									break;
								case 'RESTRICT':
									$relation->onDeleteAction = 'RESTRICT';
									break;
								default:
									// TODO
									dump($field->foreignConstraint->onDelete);
									dump_trace();
								case null:
							}
							switch ($field->foreignConstraint->onUpdate) {
								case 'CASCADE':
									$relation->onUpdateAction = 'UPDATE';
									break;
								case 'SET NULL':
									$relation->onUpdateAction = 'SET_NULL';
									break;
								case 'RESTRICT':
									$relation->onUpdateAction = 'RESTRICT';
									break;
								default:
									// TODO
									dump($field->foreignConstraint->onDelete);
									dump_trace();
								case null:
							}
						}

						Logger::get($this)->info('By constraints: found {}.{} as {} refers to {}.{}',
								$tableName, $fieldName, $rel->getName(), $otherTable, $otherField);
					}
				}
			}
		}

		Logger::setDefaultContext('');
	}

	function getReciproque($table, TplRelationReferencesOne $relation) {

		foreach ($this->hasOneReciproqueRelations[$relation->targetDBTableName] as $r) {
			if ($r->targetDBTableName === $table 
					&& $r->getReferenceField() === $relation->getReferenceField()) {
				return $r;
			}
		}

		throw new IllegalStateException();
	}

	function writeFile($filename, $replace, $callback, $params) {

		if (!$replace && file_exists($filename)) {
			echo 'Passing existing file: ' . $filename . PHP_EOL;
			$this->fileSkipped++;
			return;
		}

		$file = fopen($filename, 'w');

		if ($file === false) throw new SystemException('Cannot open file "' . $filename . '" for writting');

		echo 'Creating file: "' . $filename . '" ... ';

		$content = "<?php\n\n" . call_user_func_array($callback, $params);
		fwrite($file, $content, strlen($content));

		fclose($file);

		$this->fileCreated++;
		echo 'OK' . PHP_EOL;
	}
}
