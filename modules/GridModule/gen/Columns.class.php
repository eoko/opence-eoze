<?php

namespace eoko\modules\GridModule\gen;

use eoko\config\Config;
use \ConfigInheritor;
use \ModelColumn;
use \ModelTable;

use eoko\util\Arrays;

use \IllegalStateException, \InvalidConfigurationException;

class Columns {

	public $columnsConfig;
	/** @var Config */
	protected $config;
	protected $templates;
	protected $columns;
	protected $defaults;
	protected $templateByKeyName;
	/** @var ModelTable */
	protected $table;

	protected static $specialStringPrefixes = array(
		'__'
	);

	protected static $specialStringKeys = array(
		'renderer', 'regex'
	);
	protected static $specialStringVals = array(
	);

	public function  __construct($moduleName, Config $config, Config $parentConfig, ModelTable $table) {

		$this->table = $table;
		$this->config = $config;

		$this->templateByKeyName = isset($config['autoTemplate']) ?
				$config['autoTemplate'] : true;

		if (isset($parentConfig['columns'])) {
			$this->columnsConfig = $parentConfig->node('columns')
					->apply($config->columns, false);
		} else {
			$this->columnsConfig = $config->columns;
		}

		$this->templates = array(
			'$super' => $parentConfig->node('columns-templates'),
			$moduleName => $config->node('columns-templates'),
		);
		$this->templates['$this'] =& $this->templates[$moduleName];
//		$this->templates['$super'] =& $this->templates['GridModule'];

//		dumpl($parentConfig);
//		dump_trace(false);

		$this->defaults = $parentConfig->node('columns-defaults')
				->apply($config->node('columns-defaults'), false)
				->applyIf(array('extra' => array()), false)
				;

		$this->init();
	}

	/**
	 *
	 * @param string $tpl
	 * @param bool $require
	 * @return array
	 */
	protected function findTemplate($tpl, $require = false) {
		if ($tpl === 'checkbox_YesNo') {
			dump_mark();
		}
		if ($tpl === false) return null;
		$tplElt = explode('.', $tpl);
		$n = count($tplElt);
		if ($n == 1) {
			// form 'xxx' => find default tpl
			if (isset($this->templates['$this'][$tpl])) {
				return $this->extendTemplate($this->templates['$this'][$tpl]);
			} else if (isset($this->templates['$super'][$tpl])) {
				return $this->extendTemplate($this->templates['$super'][$tpl]);
			}
		} else if ($n == 2) {
			// form 'xxx.yy' => find qualified tpl
			list($module, $name) = $tplElt;
			if (isset($this->templates[$module][$name])) {
				return $this->extendTemplate($this->templates[$module][$name]);
			}
		} else {
			// wtf ? maybe later...
			throw new InvalidConfigurationException();
		}

		if ($require) {
			throw new InvalidConfigurationException(null, $tpl, "Cannot find template '$tpl'");
		} else {
			return null;
		}
	}
	
	private function extendTemplate($config) {
		
		$tpl = Arrays::pickOneOf($config, array('tpl', 'template'));
		
		if ($tpl === null) {
			return $config;
		} else {
			$template = $this->findTemplate($tpl, true);
			return Arrays::apply($template, $config, false);
		}
	}
	
	protected static function parseSelfTemplates(&$config) {
		foreach ($config as $name => &$colConfig) {
			if (!is_array($colConfig)) {
				if (!isset($config[$name]))
						throw new IllegalStateException('Missing template: "{}"', $name);
				$colConfig = $config[$name];
			} else {
				if (($tpl = Arrays::pickOneOf($colConfig, array('tpl', 'template'))) !== null) {
					if (!isset($config[$tpl]))
							throw new IllegalStateException("Missing template: $tpl");
					
					$config[$name] = Arrays::applyIf($colConfig, $config[$tpl], false);
				}
			}
		}
	}

	protected function init() {

		self::parseSelfTemplates($this->templates['$super']);

		$this->columns = array();

		foreach ($this->columnsConfig as $name => $colConfig) {

			//if ($name === 'password2') dump_mark();

			if (isset($this->columns[$name]))
				throw new IllegalStateException("Cannot redefine column $name");

			if (!is_array($colConfig)) {
				if ($colConfig != '' && null !== $col = $this->findTemplate($colConfig, false)) {
				} else if (null !== $col = $this->findTemplate($name, false)) {
				} else {
//					dump_trace(true);
					throw new IllegalStateException("Missing template: $colConfig|$name");
				}
				$colConfig = $col;
			}


			ConfigInheritor::process($colConfig, $this);

			$tpl = Arrays::pickOneOf($colConfig, array('tpl', 'template'));

			if ($tpl !== null) {
				$col = $this->findTemplate($tpl, true);
			} else if ($this->templateByKeyName) {
				if (null === $col = $this->findTemplate($name, false)) {
					$col = array();
				}
			} else {
				$col = array();
			}

			Arrays::apply($col, $colConfig, false);


			if (!isset($col['name'])) $col = array_merge(array('name'=>$name),$col);

			if (null !== $f = $this->table->getField($col['name'], false)) {
				switch ($f->getType()) {
					case ModelColumn::T_INT:
						if (!isset($col['renderer'])) {
							$col['renderer'] = 'Oce.ext.Renderer.integer';
						}
						$this->setColFormItemif($col, 'vtype', 'numInt');
						self::setColStoreItemIf($col, array(
							'type' => 'int',
							'useNull' => true,
						));
						break;
					case ModelColumn::T_FLOAT:
					case ModelColumn::T_DECIMAL:
						if (!isset($col['renderer'])) {
							$col['renderer'] = 'Oce.ext.Renderer.float_fr_2';
						}
						$this->setColFormItemif($col, 'vtype', 'numFloat');
						self::setColStoreItemIf($col, array(
							'type' => 'float',
							'useNull' => true,
						));
						break;
					case ModelColumn::T_DATETIME:
					case ModelColumn::T_DATE:
						self::setColStoreItemIf($col, 'type', 'date');
						break;
					case ModelColumn::T_BOOL:
						self::setColStoreItemIf($col, 'type', 'boolean');
						break;
					case ModelColumn::T_TEXT:
					case ModelColumn::T_STRING:
						self::setColStoreItemIf($col, 'type', 'string');
						break;
				}
				
				// auto set col.filter[type === list].options
				if (isset($col['filter']) && isset($col['filter']['type'])
						&& $col['filter']['type'] === 'list'
						&& (
							!isset($col['filter']['options'])
							|| ($col['filter']['options'] === 'auto')
						)
				) {
					$col['filter']['options'] = $this->getDefaultFilterListOptions($col);
				}

				if (isset($col['formField'])) {
					if (!isset($col['formField']['allowBlank'])) {
						$col['formField']['allowBlank'] = $f->isNullable();
					}
				} else if (!isset($col['allowBlank'])) {
					$col['allowBlank'] = $f->isNullable();
				}
			}

			// Grid field
			if (isset($col['formField'])) {
				$ff = &$col['formField'];
				if (isset($ff['xtype'])) {
					if ($ff['xtype'] == 'gridfield') {
						if (isset($ff['fields'])) foreach ($ff['fields'] as &$c) {
							if (is_array($c)
									&& null !== $tpl = Arrays::pickOneOf($c, array('tpl', 'template'))) {

								Arrays::applyIf($c, $this->findTemplate($tpl, true));
							}
						}
					} else if ($ff['xtype'] == 'oce.foreigncombo') {
						if (!isset($ff['column'])) $ff['column'] = $col['name'];
//					} else if ($ff['xtype'] == 'htmleditor' || $ff['textfield']) {
					} else {
						if (!isset($ff['name'])) $ff['name'] = $col['name'];
					}
				}
			} else {
//				if ($this->config->node('/options/formPropsFromDB')) {
//					Arrays::applyIf($col['form'], self::formConfigFromModel($col['name']));
//					Arrays::applyIf($col['add'], self::formConfigFromModel($col['name'], 'add'));
//					Arrays::applyIf($col['edit'], self::formConfigFromModel($col['name'], 'edit'));
//				}
			}

			foreach ($this->defaults as $k => $v) {
				if (!isset($col[$k])) $col[$k] = $v;
			}

			$this->columns[$name] = $col;
		}
	}

	private function setColFormItemIf(&$col, $name, $value) {
		if (isset($col['form'])) {
			if ($col['form'] !== false) {
				if (!isset($col['form']['name'])) {
					$col['form'][$name] = $value;
				}
			}
		} else {
			$col['form'] = array(
				$name => $value
			);
		}
	}
	
	private static function setColStoreItemIf(&$col, $name, $value = null) {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				self::setColStoreItemIf($col, $k, $v);
			}
			return;
		}
		if (isset($col['store'])) {
			if ($col['store'] !== false) {
				if (!isset($col['store'][$name])) {
					$col['store'][$name] = $value;
				}
			}
		} else {
			$col['store'] = array(
				$name => $value
			);
		}
	}
	
	private function getDefaultFilterListOptions($col) {
		$q = $this->table->createQuery();
		$f = $q->getQualifiedName($col['name']);
		$r = $q->select(new \QuerySelectRaw("DISTINCT $f"))
				->executeSelectColumn();
		natsort($r);
		return array_values($r);
	}

	protected function formConfigFromModel($name, $action = null) {
		$table = ModelTable::getModelTable($this->config->model);
		
		if (null === $col = $table->getColumn($name)) return null;

		$r = array(
		);

		// operation
		if ($action === 'add') $op = ModelColumn::OP_CREATE;
		else if ($action === 'edit') $op = ModelColumn::OP_UPDATE;
		else $op = false;

		if ($op !== false) {
			Arrays::apply($r, array (
					'allowBlank' => $col->isRequired($op)
				)
			);
		}
	}

	public static function convertInheritance(Config $fromNode, $nodeClass) {
		if ($nodeClass === 'tables') {

			$colName = $fromNode->getShortNodeName();

			$converted = Arrays::chooseAs($fromNode, array(
				'title' => 'header'
			));

			$converted['name'] = $colName;

			if ($fromNode->hasNode('simplecombo')) {
				$simplecombo = $fromNode->node('simplecombo', true);
				$formField = array(
					'xtype' => 'oce.simplecombo',
					'field' => $colName
				);
				if ($simplecombo->hasNode('defaults')) {
					$formField['value'] = $simplecombo->getNode('defaults');
				}
				$values = $simplecombo->node('values', true);
				$data = array();
				foreach ($values as $val => $cfg) {
					$data[] = array($val, $cfg['text']);
				}
				$formField['data'] = $data;
				$converted['formField'] = $formField;
			}

			return $converted;
		} else {
			throw new IllegalStateException();
		}
	}

	static function quoteString($str) {
		return "'" . addcslashes($str, "'") . "'";
	}

	static function convertVal($key, $val) {
		if (is_string($val)) {

			foreach (self::$specialStringPrefixes as $prefix) {
				$len = strlen($prefix);
				if (substr($val,0,$len) === $prefix) return substr($val,$len);
			}

			if (array_search($key, self::$specialStringKeys, true) !== false
					|| array_search($val, self::$specialStringVals, true) !== false) {
				return $val;
			}

			return self::quoteString($val);

		} else if (is_bool($val)) {
			return $val ? 'true' : 'false';
		} else if ($val === null) {
			return 'null';

		} else if (is_array($val)) {
			return self::renderArray($val);
		} else {
			return $val;
		}
	}

	static function renderArray($arr, $assoc = null) {

		$assoc = $assoc === null ? Arrays::isAssoc($arr) : $assoc;
		$parts = array();

		if ($assoc) {
			foreach ($arr as $k => &$v) {
				$v = self::convertVal($k, $v);
				$k = self::quoteString($k);
				$parts[] = "$k: $v";
			}
			return '{' . implode(', ', $parts) . '}';
		} else {
			foreach ($arr as $k => $v) {
				$v = self::convertVal($k, $v);
				$parts[] = "$v";
			}
			return '[' . implode(', ', $parts) . ']';
		}
	}

	public function render() {
		$lines = array();
		foreach ($this->columns as $col) {
			if (!is_array($col)) {
				$lines[] = "Oce.defaultField('$col')";
			} else {
				$lines[] = self::renderArray($col, true);
			}
		}
		if (count($lines) > 0) $lines[0] = "\t\t $lines[0]";
		echo implode("\n\t\t,", $lines) . "\n";
	}
	
}
