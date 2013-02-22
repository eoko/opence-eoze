<?php

namespace eoko\cqlix\generator;

use ModelColumn;
use IllegalStateException, IllegalArgumentException, ConfigurationException;

use eoko\log\Logger;
use eoko\util\YmlReader, eoko\util\Language, eoko\util\Arrays;
use eoko\cqlix\EnumColumn;

function setConfig(&$var, $config, $key) {
	if (isset($config[$key])) {
		if ($var !== null) {
			Logger::get('CQLIX')->error('Configuration conflict on key: ' . $key
				. '. Value already set: ' . $var);
		} else {
			$var = $config[$key];
		}
	}
}

class TplField extends ModelColumn implements ConfigConstants {

	public $foreignConstraint = null;

	public $columnName;
	private $comment = null;

	/** @var TplTable */
	private $parentTable = null;

	public $localRelationAlias = null;
	public $foreignRelationAlias = null;

	private $commentConfig = null;
	private $configured = false;

	public $enum = false, $enumLabels = null;
	private $enumConfig;
	private $enumCodeValues = null;

	public $unsigned = false;

	/**
	 * @var ClassLookup
	 */
	private $classLookup;

	function __construct(ClassLookup $classLookup, $field, $type, $length = null, $canNull = false,
			$default = null, $unique = false, $foreignKeyToTable = null,
			$primaryKey = false, $autoIncrement = false) {

		$meta = null;
		$this->classLookup = $classLookup;

		// Length
		if ($length !== null && preg_match('/(\d+),(\d+)/', $length, $m)) {
			$length = (int) $m[1];
			$meta['decimals'] = (int) $m[2];
		}

		// Type
		switch ($type) {
			case 'date': $type = self::T_DATE; break;
			case 'datetime': $type = self::T_DATETIME; break;
			case 'time': $type = self::T_TIME; break;
			case 'bigint':
			case 'int': // TODO DBG => int(1) == bool
//				if ($length == null || $length != 1) { $type = self::T_INT; break; }
//				if ($length == null || $length != 1) { $type = self::T_INT; break; }
				$type = self::T_INT; break;
			case 'tinyint':
				if ($length != null && $length != 1) $type = self::T_INT;
				else $type = self::T_BOOL; break;
			case 'bool': $type = self::T_BOOL; break;
			case 'decimal':
				$type = self::T_DECIMAL; break;
			case 'tinytext':
			case 'text': $type = self::T_TEXT; break;
			case 'varchar':
			case 'blob':
			case 'char': $type = self::T_STRING; break;
			case 'double':
			case 'float': $type = self::T_FLOAT; break;
			default: throw new IllegalStateException('Unrecognized type: "' . $type . '"');
		}

		$this->columnName = $field;

		parent::__construct($field, $type, $length, $canNull, $default,
				$unique, $foreignKeyToTable, $primaryKey, $autoIncrement);

		$this->meta = $meta;
	}

	static $tplTypes = array(
		self::T_INT => 'ModelColumn::T_INT',
		self::T_STRING => 'ModelColumn::T_STRING',
		self::T_TEXT => 'ModelColumn::T_TEXT',
		self::T_DATE => 'ModelColumn::T_DATE',
		self::T_DATETIME => 'ModelColumn::T_DATETIME',
		self::T_TIME => 'ModelColumn::T_TIME',
		self::T_BOOL => 'ModelColumn::T_BOOL',
		self::T_FLOAT => 'ModelColumn::T_FLOAT',
		self::T_DECIMAL => 'ModelColumn::T_DECIMAL',
		self::T_ENUM => 'ModelColumn::T_ENUM', // useless
	);

	public function hasForeignConstraint() {
		return $this->foreignConstraint !== null;
	}

	/**
	 *
	 * @return ModelColumnForeignConstraint
	 */
	public function getForeignConstraint() {
		return $this->foreignConstraint;
	}

	public function setComment($comment) {

		if ($this->comment !== null) {
			throw new IllegalStateException('Comment already set for field ' . $this->columnName);
		}

		// local: Module, foreign: OptContingent
		$this->comment = $comment;

		if (preg_match('/\bcqlix\s*:(.*)$/', $comment, $m)) {
			$config = YmlReader::load("cqlix: { $m[1] }");
			$this->commentConfig = $config['cqlix'];
//REM			$this->configure($config['cqlix']);
		} else if (preg_match('/\bcqlix\.(\w+)\s*:(.*)$/', $comment, $m)) {
			$config = YmlReader::load("$m[1]: { $m[2] }");
			$this->commentConfig = $config;
//REM			$this->configure($config);
		}
	}

	private $uniqueByConfig = null;

	public function isUnique() {
		if ($this->uniqueByConfig !== null) {
			return $this->uniqueByConfig;
		} else {
			return parent::isUnique() || $this->isPrimary();
		}
	}

	public function configure($config = null) {

		if (isset($config['unique'])) {
			$this->uniqueByConfig = $config['unique'];
		}

		if ($this->configured) {
			throw new IllegalStateException("Field $this->columnName already configured");
		} else {
			$this->configured = true;
		}

		if ($config !== null) {
			if (!is_array($config)) {
				throw new ConfigurationException(
					'*Config* Field "' . $this->getName() . "\"\t",
					'{APP_NS}/cqlix/models',
					"In config: array expected, but found " . Language::typeof($config)
					. " (value: '$config')"
				);
			}
		}

		if ($this->commentConfig) {

			Logger::get($this)->info('Comment configuration may be overriden '
					. 'by configuration files for field ' . $this->columnName);

			$config = Arrays::apply($this->commentConfig, $config, false);
		}

		if (count($config)) {
			$this->doConfigure($config);
		}
	}

	private function doConfigure($config) {

		if (isset($config[self::CFG_RELATION])) {
			$this->configureRelations($config[self::CFG_RELATION]);
			unset($config[self::CFG_RELATION]);
		}

		if (isset($config[self::CFG_ENUM])) {
			$this->configureEnum($config[self::CFG_ENUM]);
			unset($config[self::CFG_ENUM]);
		}

		$this->meta = Arrays::apply($this->meta, $config);
//		$this->meta = count($config) ? $config : null;
	}

	public function getConfiguredRelation() {
		if (isset($this->relationConfig['foreignModel'])) {
			return new TplRelationReferencesOne(
				$this->parentTable->dbTable,
				NameMaker::dbFromModel($this->relationConfig['foreignModel']),
				$this->localRelationAlias,
				null, 
				$this->getName(), 
				null
			);
		}
	}

	private $relationConfig;

	private function configureRelations($config) {
		$this->relationConfig = $config;
		setConfig($this->localRelationAlias, $config, 'local');
		setConfig($this->localRelationAlias, $config, 'localAlias');
		setConfig($this->foreignRelationAlias, $config, 'foreign');
		setConfig($this->foreignRelationAlias, $config, 'foreignAlias');
	}

	private function replaceEnumValue($code, $value) {

		if ($this->type === self::T_STRING) {
			throw new IllegalStateException('Value of enum item cannot be set'
					. ' for columns of type T_STRING. Config option '
					. EnumColumn::CFG_VALUE . ' for field '
					. "{$this->parentTable->dbTable}.$this->columnName"
					. ' is illegal.');
		}

		if (!array_key_exists($code, $this->enumCodeValues)) {
			throw new IllegalStateException();
		} else {
			$currentValue = $this->enumCodeValues[$code];
		}

		foreach ($this->enumCodeValues as $c => $v) {
			if ($value === $v) {
				$this->enumCodeValues[$c] = $currentValue;
				break;
			}
		}

		$this->enumCodeValues[$code] = $value;
	}

	private function configureEnum($config) {

		if ($this->enum) {
			$msg = 'Enum already configured.' . PHP_EOL;
			$msg .= 'Additionnal config submitted: ' . print_r($config, true);
			throw new IllegalStateException($msg);
		}

		$type = $this->type;
		$this->enum = true;

		if ($type === self::T_INT) {
			$nextFreeEnumValue = 1;
			if (!is_array($config)) {
				dump_trace();
				dump($config);
			}
			foreach ($config as $code => $label) {
				if ($code === '') {
					$this->enumCodeValues[null] = null;
				} else {
					$value = $nextFreeEnumValue++;
					$this->enumCodeValues[$code] = $value;
				}
			}
		} else if ($type === self::T_STRING) {
			foreach ($config as $code => $label) {
				if ($code === '') {
					$this->enumCodeValues[null] = null;
				} else {
					$this->enumCodeValues[$code] = $code;//"''";
				}
			}
		} else {
			throw new IllegalStateException('Virtual Enum columns must either be of type INT or STRING');
		}

//		dump($this->enumCodeValues);
//		dump($config);

		$enumConfig = array();
		$hasDefault = false;
		foreach ($config as $code => $label) {
//			$cfg =& $this->enumConfig[$this->enumCodeValues[$code]];
			if ($code === '') {
				$code = null;
			}
			$cfg =& $enumConfig[$code];
			if (is_array($label)) {

				$itemConfig = $label;

				$label = $this->enumLabels[$code] =
						(isset($itemConfig[EnumColumn::CFG_LABEL]) ? $itemConfig[EnumColumn::CFG_LABEL] : null);

				$default = false;
				if (isset($itemConfig[EnumColumn::CFG_DEFAULT]) && $itemConfig[EnumColumn::CFG_DEFAULT]) {
					$this->default = $this->enumCodeValues[$code];
					$default = true;
					$hasDefault = true;
				}

				if (isset($itemConfig[EnumColumn::CFG_VALUE])) {
					$this->replaceEnumValue($code, $itemConfig[EnumColumn::CFG_VALUE]);
				}

				$cfg = array(
					EnumColumn::CFG_LABEL => $label,
					EnumColumn::CFG_DEFAULT => $default,
					EnumColumn::CFG_CODE => $code,
				);
			} else if (is_string($label)) {
				$this->enumLabels[$code] = $label;
				$cfg = array(
					EnumColumn::CFG_LABEL => $label,
					EnumColumn::CFG_DEFAULT => false,
					EnumColumn::CFG_CODE => $code,
				);
			} else {
				throw new IllegalStateException("Invalid enum configuration item "
						. '"' . $code . '" for field '
						. "{$this->parentTable->dbTable}.$this->columnName. "
						. "Expects string|array, but found: " . Language::typeof($label)
						. " (value: $label).");
			}
			unset($cfg);
		}

		// Guess default
		if (!$hasDefault) {
			$default = $this->getDefault();
			foreach ($enumConfig as $code => &$cfg) {
				if ($code === '') {
					if ($this->isNullable() && $default === null) {
						$cfg[EnumColumn::CFG_DEFAULT] = true;
						break;
					} 
				} else {
					if ($code === $default) {
						$cfg[EnumColumn::CFG_DEFAULT] = true;
						break;
					}
				}
			}
			unset($cfg);
		}

		$this->enumConfig = array();
		foreach ($enumConfig as $code => $cfg) {
			$this->enumConfig[$this->enumCodeValues[$code]] = $cfg;
		}
	}

	public function setForeignKeyToTable($tableName) {
		return $this->foreignKeyToTable = $tableName;
	}

	function getDeclaration($tableName) {

		$primary = $this->isPrimary() ? 'true' : 'false';
		$nullable = $this->isNullable() ? 'true' : 'false';
		$unique = $this->isUnique() ? 'true' : 'false';
		$autoIncrement = $this->isAutoIncrement() ? 'true' : 'false';

//		return "new ModelColumn('{$this->getName()}', {$this->getTplType()}, {$this->getTplLength()}, "
//			. "$nullable, {$this->getTplDefault()}, $primary)";

		if ($this->foreignKeyToTable !== null) {
//			$name = NameMaker::tableFromDB($this->foreignKeyToTable);
//			if ($name === $tableName) {
//				$foreignKeyToTable = '$this';
//			} else {
//				$foreignKeyToTable = $name . 'Proxy::get()';
//			}
			$proxyClass = $this->classLookup->proxyFromDb($this->foreignKeyToTable);
			$foreignKeyToTable = $proxyClass . '::get()';
		} else {
			$foreignKeyToTable = 'null';
		}
//		$foreignKeyToTable = $this->foreignKeyToTable === null ? 'null' :
//				($tableName === $this->foreignKeyToTable ? '$this'
//				: NameMaker::tableFromDB($this->foreignKeyToTable) . '::getInstance()');

		if ($this->enum) {
			$ModelColumn = '\eoko\cqlix\EnumColumn';
			$enumConfig = Language::varExportClean($this->enumConfig);
//			$enumConfig = str_replace("\n", ' ', var_export($this->enumConfig, true));
//			while (strstr($enumConfig, '  ')) $enumConfig = str_replace('  ', ' ', $enumConfig);
//			$enumConfig = str_replace('( ', '(', $enumConfig);
//			$enumConfig = str_replace(' )', ')', $enumConfig);
//			$enumConfig = str_replace(',)', ')', $enumConfig);
//			$enumConfig = str_replace('array (', 'array(', $enumConfig);
		} else if (isset($this->meta['class'])) {
			$ModelColumn = $this->meta['class'];
		} else {
			$ModelColumn = 'ModelColumn';
		}

		$metaConfig = Language::varExportClean($this->meta);

		ob_start();
?>
new <?php echo $ModelColumn ?>('<?php
echo $this->getName() ?>', <?php
echo $this->getTplType() ?>, <?php
echo $this->getTplLength() ?>, <?php
echo $nullable ?>, <?php
echo $this->getTplDefault() ?>, <?php
echo $unique ?>, <?php
echo $foreignKeyToTable ?>, <?php
echo $primary ?>, <?php
echo $autoIncrement ?>, <?php ?>
<?php if ($this->meta): ?>

						<?php echo $metaConfig ?>
<?php else: echo 'null'; ?>
<?php endif ?>
<?php if ($this->enum): ?>
,
						<?php echo $enumConfig ?>
<?php endif ?>
)<?php

		return ob_get_clean();
	}

	function getTplType($type = null) {
		if ($type === null) $type = $this->getType();

		if (!isset(self::$tplTypes[$type]))
			throw new IllegalArgumentException('Incorrect type: ' . $type);

		return self::$tplTypes[$type];
	}

	function getTplLength() {
		return var_export($this->getLength(), true);
//		$length = $this->getLength();
//		return $length === null ? 'null' : '"$length"';
//		if () {
//			return 'null';
//		} else if (preg_match('/\d+/', $this->getLength())) {
//			return $this->getLength();
//		} else if (preg_match('\(d+),(\d+)/', $this->getLength(), $m)) {
//
//			return $m[1];
//		} else {
//			throw new IllegalStateException();
//		}
	}

	function getTplDefault() {
		switch ($this->type) {
			case self::T_INT:
			case self::T_FLOAT:
				return ($this->default !== null ? $this->default : "null");
			case self::T_BOOL: return ($this->default !== null ? ($this->default ? 'true' : 'false') : "null");
			default:
			case self::T_STRING: return ($this->default !== null ? '"' . $this->default . '"' : "null");
		}
	}

	public function isEnum() {
		return $this->enum;
	}

	public function setParentTable(TplTable $table) {
		if ($this->parentTable === null) {
			$this->parentTable = $table;
		} else {
			throw new IllegalStateException('Parent table already set for field'
					. " $this->columnName: {$this->parentTable->dbTable} "
					. "(new table: $table->dbTable)");
		}
	}

	private function ensureConfigured() {
		if (!$this->configured) {
			$trace = debug_backtrace();
			$function = isset($trace[1]['function']) ? $trace[1]['function'] . '()' : 'UNKNOWN';
			$class = get_class($this);
			throw new IllegalStateException("The method $class.$function must not "
					. 'be called before the Field has been configured');
		}
	}

	public function getEnumValues() {
		$this->ensureConfigured();
		return $this->enumCodeValues;
	}
}
