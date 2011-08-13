<?php
/**
 * @package PS-ORM-1
 * @author Éric Ortéga <eric@mail.com>
 * @copyright Copyright (c) 2010, Éric Ortéga
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

use eoko\cqlix\FieldMetadata;

class ModelColumn implements ModelField {

	/** @var string the name of the field in the database */
	public $name;
	/** @var string an alias that can be used to reference the field */
	private $alias;

	/** @var Const String */
	public $type;
	protected $sqlType;
	/** @var Int */
	public $length;
	/** @var <mixed> */
	public $default;
	/**
	 * @var <mixed>
	 * <p>NULL if autoValue if off for all operations.
	 * <p>May be an Array OPERATION => {$value | NULL} to precise different 
	 * autoValue for different operations.
	 * <p>May be a single scalar value if the the autoValue is the same for
	 * all operations.
	 */
	private $autoValue;
	/** @var bool */
	public $primary;
	/** @var bool */
	public $unique;
	/** @var bool */
	public $autoIncrement;
	/** @var bool */
	public $nullable;
	/** @var string */
	protected $header;
//	public $label;
	/** @var FieldMetadata */
	public $meta;
	/**
	 * Indicates that the columns can only be set once, when the row is first
	 * created.
	 * @var Bool
	 */
	protected $final;

	protected $foreignKeyToTable = null;

	protected $converter = null;

	const T_INT = 'int';
	const T_INTEGER = 'int';
	const T_STRING = 'string';
	const T_TEXT = 'text';
	const T_DATE = 'date';
	const T_TIME = 'time';
	const T_DATETIME = 'datetime';
	const T_BOOL = 'bool';
	const T_BOOLEAN = 'bool';
	const T_FLOAT = 'float';
	const T_DECIMAL = 'decimal';
	const T_ENUM = 'enum';

	const OP_CREATE = 1;
//	const OP_READ = 2;
	const OP_UPDATE = 3;

	const AUTO_NOW = 1;
	const AUTO_CURRENT_USER = 2;
	const AUTO_DELETED = 3;

	function __construct($columnName, $type, $length = null, $canNull = false,
			$default = null, $unique = false, $foreignKeyToTable = null,
			$primaryKey = false, $autoIncrement = false, $meta = null) {

		$this->name = $columnName;
		$this->meta = new FieldMetadata($meta);
//		$this->label = $this->meta->label;

		// TODO: move that logic to the model generation phase (this is static....)
		// Auto behavior
		$this->autoValue = array(
			self::OP_CREATE => null,
			self::OP_UPDATE => null
		);
		switch ($columnName) {
			case 'date_add':
				$this->final = true;
				$this->autoValue[self::OP_CREATE] = self::AUTO_NOW;
				break;
			case 'date_mod':
				$this->autoValue[self::OP_CREATE] = self::AUTO_NOW;
				$this->autoValue[self::OP_UPDATE] = self::AUTO_NOW;
				break;
			case 'usr_mod':
				$this->autoValue[self::OP_CREATE] = self::AUTO_CURRENT_USER;
				$this->autoValue[self::OP_UPDATE] = self::AUTO_CURRENT_USER;
				break;
			case 'deleted':
				$this->autoValue[self::OP_CREATE] = self::AUTO_DELETED;
				$this->autoValue[self::OP_UPDATE] = self::AUTO_DELETED;
				break;
			default:
				$this->autoValue = null;
		}

		$this->sqlType = $this->type = $type;
		$this->length = $length;

		$this->default = $default;

		$this->primary = $primaryKey;
		$this->unique = $unique;
		$this->autoIncrement = $autoIncrement;
		$this->nullable = $canNull;

		if (null !== $alias = $this->meta->alias) {
			$this->alias = $alias;
		} else {
			$this->alias = $this->createDefaultAlias();
		}
		
		if ($foreignKeyToTable instanceof ModelTableProxy) {
			$foreignKeyToTable->attach($this->foreignKeyToTable);
		} else {
			$this->foreignKeyToTable = $foreignKeyToTable;
		}
	}

	protected function createDefaultAlias() {
		return Inflector::camelCase($this->name);
	}

	function getVarName($capitalizeFirst = false) {
		return Inflector::camelCase($this->name, $capitalizeFirst);
	}

	public function getName() {
		return $this->name;
	}

	public function getAlias() {
		return $this->alias;
	}

	public function getType() {
		return $this->type;
	}

	public function getLength() {
		return $this->length;
	}

	/**
	 * Get the default field's value, as stored in the database.
	 * @return <mixed>
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * Whether the field has a default value <b>set in the database</b>.
	 * @return Bool
	 */
	public function hasDefault() {
		return $this->default !== null;
	}

	public function getAutoValue($operation) {
		switch ($this->getAutoValueId($operation)) {
			case self::AUTO_CURRENT_USER: 
				if (!UserSession::isIdentified()) {
					throw new UserSessionTimeout();
//					throw new UserException(
//						lang('Vous avez été déconnecté suite à une période '
//								. 'd\'inactivité ; veuillez vous identifier à nouveau '
//								. 'pour continuer votre travail (cliquez sur "Déconnexion" '
//								. 'ou rafraichissez la page).')
//						,lang('Session expirée')
//						,'Cannot set AUTO_CURRENT_USER if no one is connected!'
//					);
				}
				return UserSession::getUser()->getDisplayName(User::DNF_FORMAL);
			case self::AUTO_NOW: return Query::SqlFunction('NOW()');
			case self::AUTO_DELETED: return 0;
		}
		return null;
	}

	public function generateRandomValue(ModelTable $table) {

		if ($this->isForeignKey()) {
			Logger::dbg($this->name);
			if ($this->unique) {
				return Debug::getRandomExistingPrimaryKey($this->getForeignTable(), $table, $this->name);
			} else {
				return Debug::getRandomExistingPrimaryKey($this->getForeignTable());
			}
		}

		switch ($this->type) {
			case self::T_BOOL: return rand(0, 1);
			case self:T_TEXT:
			case self::T_STRING: return Debug::randomString(rand(
					max(1, $this->getLength()-5), min($this->getLength(), 20)));
			case self::T_DATE: return DateHelper::getTimeAs(time() + rand(0,50000), DateHelper::SQL_DATE);
			case self::T_DATETIME: return DateHelper::getTimeAs(time() + rand(0,50000), DateHelper::SQL_DATETIME);
			case self::T_FLOAT:
			case self::T_INT: return substr(rand(0,20000), $this->getLength());
			default: throw new IllegalStateException();
		}
	}

	/**
	 * @param Const $operation {ModelColumn::CREATE | ModelColumn::UPDATE }
	 */
	public function getAutoValueId($operation) {
		if ($this->autoValue === null) {
			return null;
		} else {
			if (is_array($this->autoValue)) {
				return $this->autoValue[$operation];
			} else {
				return $this->autoValue;
			}
		}
	}

	/**
	 * Whether the field is automatically set <b>by the model</b>.
	 * @return Bool
	 */
	public function isAuto($operation) {
		return $this->getAutoValueId($operation) !== null;
	}

	/**
	 * @return Bool TRUE if the field can be set only at the reccord creation
	 * time, else FALSE (the field can be modified anytime).
	 */
	public function isFinal() {
		return $this->final;
	}

	public function isPrimary() {
		return $this->primary;
	}

	public function isUnique() {
		return $this->unique;
	}

	public function isAutoincrement() {
		return $this->autoIncrement;
	}

	public function isNullable() {
		return $this->nullable;
	}

	public function isRequired($operation) {
		return !$this->isNullable() && !$this->hasDefault() 
				&& !$this->isAuto($operation) && !$this->primary;
		// <= TODO replace primary by autoincrement poperty?
	}

	function getPhpType() {
		return ucfirst($this->type);
	}
	
	public function getPhpConvertTypeString() {
		switch ($this->type) {
			case self::T_INT: return '(int)';
			case self::T_FLOAT: return '(float)';
			case self::T_STRING: return '(string)';
			case self::T_BOOL: return '(bool)';
			default: return '';
		}
	}
	
	public function getHeader() {
		return $this->name;
	}

	public function setConverter($converter) {
		$this->converter = $converter;
	}

	public function convertValueToSQL($value) {

		if ($this->converter !== null) {
			if ($this->converter->toSql($value)) {
				return $value;
			}
		}

		if ($value === '' || $value === null) return null;

		switch ($this->type) {
			case self::T_BOOL: return $value === null || $value === '' ? null : ($value ? 1 : 0);
			case self::T_DATE:
				dump_mark();
				return DateHelper::dateExtToSql($value);
			case self::T_INT: return $value === null || $value === '' ? null
				: (($value === 0 ? '0' : $value));
			case self::T_FLOAT: return $value === null || $value === '' ? null
				: (float) str_replace(',', '.', $value);
			case self::T_DECIMAL: return $value === null || $value === '' ? null
				: str_replace(',', '.', $value);
			default: return $value;
		}
	}

	/**
	 *
	 * @return Bool
	 */
	public function isForeignKey() {
		return $this->foreignKeyToTable !== null;
	}

	/**
	 *
	 * @return ModelTable
	 */
	public function getForeignTable() {
		return $this->foreignKeyToTable;
	}

	/**
	 *
	 * @param Query $query
	 */
	public function select(ModelTableQuery $query) {
		if ($this->type === self::T_DATETIME) {
			// select date time as valid ISO-8601
			$query->select(
				new QuerySelectFunctionOnField(
					$query, 
					$this->name, 
					'DATE_FORMAT({}, "%Y-%m-%dT%H:%m:%i")', 
					$this->name
				)
			);
		} else {
			$query->select($this->name);
		}
	}

	public static function buildColumnSelect($name, $tableName = null,
			$quoteTableName = true, $alias = null, $isAliasPrefix = false) {

		if ($tableName === null) {
			if ($alias === null) {
				return "`$name`";
			} else {
				if (!$isAliasPrefix) return "`$name` AS `$alias`";
				else return "`$name` AS `{$alias}_$name`";
			}
		} else {
			$qTable = $quoteTableName ? Query::quoteName($tableName) : $tableName;
			if ($alias === null) {
				return  "$qTable.`$name`";
			} else {
				if (!$isAliasPrefix) return "$qTable.`$name` AS `$alias`";
				else return "$qTable.`$name` AS {$alias}_$name";
			}
		}
	}

	public function buildSelect($tableName = null, $quoteTableName = true, 
			$alias = null, $isAliasPrefix = false) {

		return self::buildColumnSelect($this->name, $tableName, $quoteTableName, $alias, $isAliasPrefix);
	}

	public function orderClause($dir, $tableAlias = null, $asString = false) {
		if ($tableAlias === null) $r = "`$this->name` $dir";
		else $r ="`$tableAlias`.`$this->name` $dir";

		if ($asString) return $r;
		else return new SqlVariable($r);
	}

	public function isEnum() {
		return false;
	}

	public function createCqlixFieldConfig() {

		$r = array(
			'name' => $this->name,
			'alias' => $this->alias,
			'type' => $this->type,
			'allowNull' => $this->isNullable(),
			'hasDefault' => $this->hasDefault(),
			'allowBlank' => $this->isNullable() || $this->hasDefault(),
			'defaultValue' => $this->getDefault(),
			'length' => $this->length,
			'primaryKey' => $this->isPrimary(),
		);

		$this->meta->addCqlixFieldConfig($r);

		if (!array_key_exists('internal', $r)) {
//			$r['internal'] = $this->isPrimary() || $this->isForeignKey();
			$r['internal'] = $this->isPrimary();
		}

//		// TODO : BooleanColumn
//		if ($this->type === self::T_BOOL) {
//			$r['items'] = array(
//				array(
//					'label' => 'Oui', // i18n
//					'default' => $this->getDefault() === true,
//					'code' => 'YES',
//					'value' => 1,
//				),
//				array(
//					'label' => 'Non', // i18n
//					'default' => $this->getDefault() === false,
//					'code' => 'NO',
//					'value' => 0,
//				)
//			);
//		}

		return $r;
	}
}
