<?php

use eoko\module\Module;
use eoko\module\ModuleManager;
use eoko\template\Template;
use eoko\file\FileType;
use eoko\cqlix\EnumColumn;
use eoko\util\Arrays;
use eoko\config\Config;

class LegacyGridModule {

	private static function loadParentConfig($config) {
		$config = ModuleManager::getModule($config->class)->getConfig();
		if (isset($config['class'])) {
			$parentConfig = clone ModuleManager::getModule($config['class'])->getConfig();
			$config = $parentConfig->apply($config, false);
		}
		return $config;
////		return Config::load(ModuleManager::getModulePath('GridModule') . 'GridModule.yml', ('GridModule'));
//		return ModuleManager::getModule('GridModule')->getConfig();
	}

	public static function generateModule(Module $module) {

		$controllerName = $module->getName();
		$config = $module->getConfig();

		$tplFile = dirname(__FILE__) . DS . 'tpl' . DS . 'gridmodule.js.php';

		$parentConfig = self::loadParentConfig($config);

		$modelName = $config->model;
		$table = ModelTable::getModelTable($modelName);

		$moduleConfig = $config->node('module')
				// Inherit parent module
				->applyIf($parentConfig->get('module', null), false)
				// Defaults
				->applyIf(array(

					'controller' => $controllerName,
					'name' => $controllerName,
					'namespace' => 'Oce.Modules',

					'title' => ucfirst($controllerName),

					'primaryKeyName' => $table->getPrimaryKeyName(),
				), false)
				->toArray();

		// --- Generation Template ---
		$tpl = Template::create()->setFile($tplFile);

		// uses
		if (isset($config['uses'])) {
			$uses = $config->uses;
		} else {
			$uses = array();
		}

		$tpl->merge($moduleConfig);
		if (isset($config['jsClass'])) {
			if (preg_match('/.+\..+\..+\..+/', $config->jsClass)) {
				$tpl->superclass = $config->jsClass;
			} else {
				$tpl->superclass = "Oce.Modules.$config->jsClass.$config->jsClass";
			}
		} else {
			$tpl->superclass = "Oce.Modules.$config->class.$config->class";
		}

		$uses[] = $tpl->superclass;

		$tpl->modelName = json_encode($modelName);
		$tpl->tableName = json_encode($table->getTableName());

		$tpl->uses = json_encode($uses);

		// --- i18n ---

		$i18n = null;
		if (isset($config['i18n'])) {
			$i18n = self::generateI18n($config['i18n'],
					isset($config['i18nOptions']) && $config['i18nOptions']);
			$tpl->i18n = self::toJSTemplate($i18n);
		}

		// --- Extra ---
		$extra = $config->node('extra')
				->applyIf($parentConfig->get('extra', null))
				->toArray();

		// --- Forms ---
		$forms = $config->node('forms')
				->applyIf($parentConfig->get('forms', null))
				->toArray();

		// --- Filters ---
		if (isset($extra['filters'])) {
			// Converting to assoc array (legacy expectation)
			if (!Arrays::isAssocArray($extra['filters'])) {
				$filters = array();
				foreach ($extra['filters'] as $filter) {
					$filters[$filter['name']] = $filter;
				}
				$extra['filters'] = $filters;
			}
		}

//// This was expected to be used by Ajax router... Not been yet.
//
//		// --- Slug ---
//		if (!isset($extra['slug'])) {
//			if (isset($moduleConfig['slug'])) {
//				$extra['slug'] = $moduleConfig['slug'];
//			} else {
//				$extra['slug'] = strtolower($module->getName());
//			}
//		}

		// --- Year ---
		if (method_exists($table, 'isAnnualized')) {
			if (isset($extra['year'])) {
				if (is_array($extra['year']) && isset($extra['enabled']) && $extra['enabled']) {
					$year = $extra['year'];
				} else if (is_bool($extra['year'])) {
					$year = $extra['year'];
				} else {
					$year = $table->isAnnualized();
					$extra['year'] = $year;
				}
			} else {
				$year = $table->isAnnualized();
				$extra['year'] = $year;
			}
		}
//		if ($year) {
//			$config->columns['year'] = 'year';
//		}


		// --- Columns ---
//		$columns = new GridModuleColumns($controllerName, $config, $parentConfig, $table);
		$columns = new \eoko\modules\GridModule\gen\Columns(
				$controllerName, $config, $parentConfig, $table);

		// --- Javascript templates ---
		$parts = array();
		$files = array_merge(
			FileHelper::listFilesIfDirExists(MODULES_PATH . 'grid' . DS . 'jstpl', 're:.html\.php$', false, true)
			,FileHelper::listFilesIfDirExists(MODULES_PATH . $controllerName . DS . 'jstpl', 're:.html\.php$', false, true)
		);
		foreach ($files as $file) {
//			preg_match('/^(.*)\.html\.php$/', basename($file), $matches);
//			$name = $matches[1];
			$name = basename($file, '.html.php');
			$parts[] = "'$name': " . Template::makeJSTemplate($file);
		}
		$tpl->templates = implode("\t\t\n,", $parts);
		$tpl->columns = $columns;


		// --- Tabs ---
		self::buildTabsConfig($config, $i18n, $tpl);

		// --- Renderer ---

		$tpl->renderers = isset($config['renderers']) ?
				$config['renderers'] : array();

//		// --- Enums ---
//		$enumsConfig = self::createEnumsConfig($table);
//		if ($enumsConfig) $tpl->enumsConfig = $enumsConfig;
//		if (isset($config['extra']['extraModels'])) {
//			$extraModelConfigs = $config['extra']['extraModels'];
//			if (!is_array($extraModelConfigs)) $extraModelConfigs = array($extraModelConfigs);
//			$extraModels = array();
//			if (Arrays::isAssoc($extraModelConfigs)) {
//				foreach ($extraModelConfigs as $alias => $extraModel) {
//					if (null !== $extraModelEnums = self::createEnumsConfig(ModelTable::getModelTable($extraModel), false)) {
//						$extraModels[$alias] = $extraModelEnums;
//					}
//				}
//			} else {
//				foreach ($extraModelConfigs as $extraModel) {
//					if (null !== $extraModelEnums = self::createEnumsConfig(ModelTable::getModelTable($extraModel), false)) {
//						$extraModels[$extraModel] = $extraModelEnums;
//					}
////					$extraModels[$extraModel] = self::createEnumsConfig(ModelTable::getModelTable($extraModel), false);
//				}
//			}
//			if ($extraModels) {
////				dump($extraModels);
//				$tpl->extraModels = $extraModels = self::toJSTemplate($extraModels);
//			}
//		}

		// --- Model Config ---
		$tpl->modelConfig = self::createModelConfig($table);

//		dump($config['extra']['modelRelations']);
		if ($config->has('extra', 'modelRelations')) {
			$extraModelConfigs = $config['extra']['modelRelations'];
			$extraModels = array();
			if (!is_array($extraModelConfigs)) $extraModelConfigs = array($extraModelConfigs);
//			if (Arrays::isAssoc($extraModelConfigs)) {
//				foreach ($extraModelConfigs as $alias => $extraModel) {
//					if (null !== $extraModelEnums = self::createModelConfig(ModelTable::getModelTable($extraModel), false)) {
//						$extraModels[$alias] = $extraModelEnums;
//					}
//				}
//			} else {
				foreach ($extraModelConfigs as $extraModel) {
					if ($extraModel === '*') {
						foreach ($table->getRelationsInfo() as $rel) {
							$modelTable = $rel->getTargetTable();
							if (null !== $extraModelEnums = self::createModelConfig($modelTable, false)) {
								$extraModels[$rel->name] = $extraModelEnums;
							}
						}
					} else {
						$modelTable = $table->getRelationInfo($extraModel)->getTargetTable();
	//					if (null !== $extraModelEnums = self::createModelConfig(ModelTable::getModelTable($extraModel), false)) {
						if (null !== $extraModelEnums = self::createModelConfig($modelTable, false)) {
							$extraModels[$extraModel] = $extraModelEnums;
						}
					}
				}
//			}
			if ($extraModels) {
				$tpl->extraModels = $extraModels = self::toJSTemplate($extraModels);
			}
		}


		$tpl->extra = self::toJSTemplate($extra);
		$tpl->forms = self::toJSTemplate($forms);
		$moduleConfig['controller'] .= '.grid';
		$tpl->my = self::toJSTemplate($moduleConfig);


//		if (
//			(null !== $path = ModuleManager::getModulePath($controllerName, false))
//			&& file_exists($extraJSFile =  "$path$controllerName.js")
//		) {
		if ((null !== $extraJSFile = $module->searchPath("$controllerName.js", FileType::JS))
			|| (null !== $extraJSFile = $module->searchPath("$controllerName.js"))) {
			$tpl->extraJS = file_get_contents($extraJSFile);
		}

//		dump("$tpl");
		return $tpl;
	}

	private static function generateI18n($texts, $convertAll = false) {

		require_once LIBS_PATH . 'markdown.php';

		$htmlTexts = array();

		foreach ($texts as $k => $text) {
			if (substr($k, 0, 1) === '>') {
				// removes the <p>...</p>
				$text = Markdown($text);
				$text = substr($text, 3);
				$text = substr($text, 0, -5);
				$htmlTexts[substr($k, 1)] = $text;
			} else if ($convertAll) {
				// removes the <p>...</p>
				$text = Markdown($text);
				if (!strstr($text, "\n\n")) {
					$text = substr($text, 3);
					$text = substr($text, 0, -5);
				}
				$htmlTexts[$k] = $text;
			} else {
				$htmlTexts[$k] = $text;
			}
		}

		return $htmlTexts;
	}

	private static function valueToJSTemplate($v) {
		if ($v === null) {
			return 'null';
		} else if (is_string($v)) {
			return GridModuleColumns::quoteString($v);
		} else if (is_bool($v)) {
			return $v ? 'true' : 'false';
		} else if (is_array($v)) {
			return GridModuleColumns::renderArray($v);
		} else {
			return $v;
		}
	}

	private static function toJSTemplate($array) {

		return json_encode($array);

		if (!is_array($array)) return self::valueToJSTemplate($array);

		$parts = array();

		if (ArrayHelper::isAssoc($array)) {
			foreach ($array as $k => $v) {
				$parts[] = "'$k':" . self::toJSTemplate($v);
			}
			$braces = array('{', '}');
		} else {
			foreach ($array as $v) {
				$parts[] = self::toJSTemplate($v);
			}
			$braces = array('[', ']');
		}

		return $braces[0] . implode(',', $parts) . $braces[1];
	}

	private static function buildTabsConfig(Config $config, $i18n, Template &$tpl = null) {

		if (isset($config['tabs'])) {
			$tabs = array();

			foreach (array('add','edit') as $action) {
				if (isset($config['tabs'][$action]) && $config['tabs'][$action] !== false) {

					$tabConfig = $config['tabs'][$action] === true ? array() : $config['tabs'][$action];
					if (isset($config['tabs']['defaults'])) {
						ArrayHelper::applyIf($tabConfig, $config['tabs']['defaults']);
					}

//					unset($tabConfig['items']);
//					$tabs[$action] = $tabConfig;
//
//					$tabItems = $config->node("tabs/$action/items", true);
//					if (isset($tabConfig['groupTabs']) && $tabConfig['groupTabs']) {
//						foreach ($tabItems as $group) {
//							$tabs[$action]['items'][] =
//									self::buildFormTabPanel($config['columns'], $group);
//						}
//					} else {
//						$tabs[$action]['items'] = self::buildFormTabPanel($config['columns'], $tabItems);
//					}
					$tabs[$action] = $tabConfig;
					self::convertTabFields($config['columns'], $tabs[$action]);
				}
			}

			if ($tpl !== null) {
				if (isset($i18n)) {
					self::convertLangItems($i18n, $tabs);
				}
				$tpl->tabs = self::toJSTemplate($tabs);
			}
			return $tabs;
		}
	}

	private static function convertLangItems($langKeys, &$array) {
		foreach ($array as $k => &$v) {
			if (is_array($v)) {
				self::convertLangItems($langKeys, $v);
			} else if (is_string($v) && preg_match('/^\$lang\.(?P<key>.+)$/', $v, $matches)) {
				if (isset($langKeys[$matches['key']])) {
					$v = $langKeys[$matches['key']];
				} else {
					Logger::get(get_called_class())->warn('Missing lang key: ' . $matches['key']);
					$v = '';
				}
			}
		}
	}

	public static function convertTabFields($columns, &$config) {
		if (isset($config['items'])) {
			$assoc = ArrayHelper::isAssoc($config['items']);
			foreach ($config['items'] as $k => &$v) {
				if (is_string($v)) {
					if (-1 !== $index = ArrayHelper::findKeyIndex($columns, $v)) {
						$v = $index;
					} else {
						unset($config['items'][$k]);
						Logger::get('GridModule')->warn('Invalid item name: ' . $v);
					}
				} else if (is_array($v) && isset($v['field'])) {
					if (-1 !== $index = ArrayHelper::findKeyIndex($columns, $v['field'])) {
						$v['field'] = $index;
					} else {
						unset($config['items'][$k]);
						Logger::get('GridModule')->warn('Invalid item name: ' . $v['field']);
					}
				}
			}
			if (!$assoc) $config['items'] = array_values($config['items']);
		}
		foreach ($config as &$c) {
			if (is_array($c)) {
				self::convertTabFields($columns, $c);
			}
		}
		return $config;
	}

	private static function createModelConfig(ModelTable $table, $encode = true) {
		$config = array();
		foreach ($table->getColumns() as $column) {
			$config['fields'][] = $column->createCqlixFieldConfig();
		}
		foreach ($table->getRelationsInfo() as $rel) {
			if (null !== $cfg = $rel->createCqlixFieldConfig()) {
				$config['fields'][] = $cfg;
			}
		}
		if ($encode) {
			$config = self::toJSTemplate($config);
		}
		return $config;
	}

}

class GridModuleColumns {

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

		$this->columnsConfig = $config->columns;

		$this->templates = array(
			'GridModule' => $parentConfig->node('columns-templates'),
			$moduleName => $config->node('columns-templates'),
		);
		$this->templates['$this'] =& $this->templates[$moduleName];
		$this->templates['$super'] =& $this->templates['GridModule'];

		$this->defaults = $parentConfig->node('columns-defaults')
				->apply($config->node('columns-defaults'))
				->applyIf(array('extra' => array()))
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
		$tplElt = explode('.', $tpl);
		$n = count($tplElt);
		if ($n == 1) {
			// form 'xxx' => find default tpl
			if (isset($this->templates['$this'][$tpl])) return $this->templates['$this'][$tpl];
			else if (isset($this->templates['$super'][$tpl])) return $this->templates['$super'][$tpl];
		} else if ($n == 2) {
			// form 'xxx.yy' => find qualified tpl
			list($module, $name) = $tplElt;
			if (isset($this->templates[$module][$name])) return $this->templates[$module][$name];
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

	protected static function parseSelfTemplates(&$config) {
		foreach ($config as $name => &$colConfig) {
			if (!is_array($colConfig)) {
				if (!isset($config[$name]))
						throw new IllegalStateException('Missing template: "{}"', $name);
				$colConfig = $config[$name];
			} else {
				if (($tpl = ArrayHelper::pickOneOf($colConfig, array('tpl', 'template'))) !== null) {
					if (!isset($config[$tpl]))
							throw new IllegalStateException("Missing template: $tpl");

					$config[$name] = ArrayHelper::applyIf($colConfig, $config[$tpl], false);
				}
			}
		}
	}

	protected function init() {

		self::parseSelfTemplates($this->templates['$super']);

		$this->columns = array();

		foreach ($this->columnsConfig as $name => $colConfig) {

//			if ($name === 'password2') dump_mark();

			if (isset($this->columns[$name]))
				throw new IllegalStateException("Cannot redefine column $name");

			if (!is_array($colConfig)) {
				if ($colConfig != '' && null !== $col = $this->findTemplate($colConfig, false)) {
				} else if (null !== $col = $this->findTemplate($name, false)) {
				} else {
					throw new IllegalStateException("Missing template: $colConfig | $name");
				}
				$colConfig = $col;
			}


			ConfigInheritor::process($colConfig, $this);

			$tpl = ArrayHelper::pickOneOf($colConfig, array('tpl', 'template'));

			if ($tpl !== null) {
				$col = $this->findTemplate($tpl, true);
			} else if ($this->templateByKeyName) {
				if (null === $col = $this->findTemplate($name, false)) {
					$col = array();
				}
			} else {
				$col = array();
			}

			ArrayHelper::apply($col, $colConfig, false);


			if (!isset($col['name'])) $col = array_merge(array('name'=>$name),$col);

			if (null !== $f = $this->table->getField($col['name'])) {
				switch ($f->getType()) {
					case ModelColumn::T_INT:
						if (!isset($col['renderer']))
							$col['renderer'] = 'Oce.ext.Renderer.integer';
						$this->setColFormItemif($col, 'vtype', 'numInt');
						break;
					case ModelColumn::T_FLOAT:
						if (!isset($col['renderer']))
							$col['renderer'] = 'Oce.ext.Renderer.float_fr_2';
						$this->setColFormItemif($col, 'vtype', 'numFloat');
						break;
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
						foreach ($ff['fields'] as &$c) {
							if (is_array($c)
									&& null !== $tpl = ArrayHelper::pickOneOf($c, array('tpl', 'template'))) {

								ArrayHelper::applyIf($c, $this->findTemplate($tpl, true));
							}
						}
					} else if ($ff['xtype'] == 'oce.foreigncombo') {
						if (!isset($ff['column'])) $ff['column'] = $col['name'];
					}
				}
			} else {
//				if ($this->config->node('/options/formPropsFromDB')) {
//					ArrayHelper::applyIf($col['form'], self::formConfigFromModel($col['name']));
//					ArrayHelper::applyIf($col['add'], self::formConfigFromModel($col['name'], 'add'));
//					ArrayHelper::applyIf($col['edit'], self::formConfigFromModel($col['name'], 'edit'));
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
			ArrayHelper::apply($r, array (
					'allowBlank' => $col->isRequired($op)
				)
			);
		}
	}

	public static function convertInheritance(Config $fromNode, $nodeClass) {
		if ($nodeClass === 'tables') {

			$colName = $fromNode->getShortNodeName();

			$converted = ArrayHelper::chooseAs($fromNode, array(
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

		$assoc = $assoc === null ? ArrayHelper::isAssoc($arr) : $assoc;
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
