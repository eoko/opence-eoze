<?php

namespace eoko\modules\GridModule\gen;

use eoko\module\ModuleManager;
use eoko\template\PHPCompiler, eoko\template\Template;
use eoko\util\Arrays;

use Config;
use \ModelTable;
use \Inflector;

class ExecutorGenerator extends GeneratorBase {

	private $add_mod_autoField = array();
	/** @var PHPCompiler */
	private $tpl;

	public function populate(PHPCompiler $tpl) {

		$this->tpl = $tpl;

		$this->config->controllerInfo = $this->config->node('controllerInfo')
				// Inherit parent module
				->applyIf($this->parentConfig->get('controllerInfo', null))
				->applyIf(array(
					'hasMergeMembers' => false
				))
				->toArray();

		// --- Generation Template ---
//		$tplFile = dirname(__FILE__) . DS . 'tpl' . DS . 'GridController.tpl.php';
//		$tpl = Template::create($tplFile);
//		$tpl->className = $name;
		$tpl->merge($this->config->controllerInfo);
		$tpl->title = str_replace("'", "\\'", $this->config->getValue('module/title', 'Undefined Title'));

//		if (
//			(null !== $path = ModuleManager::getModulePath($name, false))
//			&& (
//				file_exists($file = $path . "$name.php")
//				|| file_exists($file = $path . "controller.php")
//			)
//		) {
//			// substr remove the file's <?php tag
//			$tpl->classEx = substr(file_get_contents($file), 6);
//		}

//		if ($config->has('model')) $tpl->modelName = $config->model;
//		else $tpl->modelName = Inflector::modelFromController($name);
//
//		$table = ModelTable::getModelTable($tpl->modelName);
//		$tpl->autocompleteDBTable = $this->table->getDBTable();

		// --- Tabs Pages ---
		$pages = array();
		if (isset($this->config['tabs'])) {
			foreach (array('add','edit') as $action) {
				if (isset($this->config['tabs'][$action]) && $this->config['tabs'][$action] !== false) {

					$tabConfig = $this->config['tabs'][$action] === true ? array() : $this->config['tabs'][$action];
					if (isset($this->config['tabs']['defaults'])) {
						Arrays::applyIf($tabConfig, $this->config['tabs']['defaults']);
					}

					$tabItems = $tabConfig['items'];

					if (isset($tabConfig['groupTabs']) && $tabConfig['groupTabs']) {
						foreach ($tabItems as $groupName => $groupItems) {
							if (isset($groupItems['items'])) {
								$groupItems = $groupItems['items'];
							}
							foreach ($groupItems as $tabName => $items) {
								if (Arrays::isAssoc($items) && isset($items['page'])) {
									$pages[$items['page']] = $items['page'];
								}
							}
						}
					} else {
						foreach ($tabItems as $tabName => $items) {
							if (Arrays::isAssoc($items) && isset($items['page'])) {
								$pages[$items['page']] = $items['page'];
							}
						}
					}
				}
			}
		}
		foreach ($pages as &$p) {
			$p = "'" . addcslashes($p, "'") . "'";
		}
		unset($p);

		$tpl->tabPages = implode(', ', $pages);

		// --- Process columns infos ---
		$this->processColumnsInfo();

		$this->processAutoValues();

		// --- Autocomplete ---
		if ($this->config->has('autocomplete')) {
			$tpl->autocomplete = str_replace("'", '\\\'', $this->config->autocomplete['label']);
		}

		if ($this->config->has('label')) {
			$tpl->label = $this->config->label;
		}

//		$tpl->primaryKeyName = $this->table->getPrimaryKeyName();

		return $tpl;
////		pre();
////		$tpl->render();
////		die;
//
//		$dir = CACHE_PATH . 'modules' . DS;
//		if (!is_dir($dir)) mkdir($dir);
//		return $tpl->compile(null, "$dir$name.php");
	}

	private function processAutoValues() {

		$autoVals = null;

		foreach ($this->columns->columnsConfig as $name => $col) {
			if (isset($col['autoValue'])) {
				if (null !== $r = $this->processAutoVal($name, $col['autoValue'])) {
					$autoVals[$name] = $r;
				}
			}
		}

		if ($autoVals) $this->tpl->add_mod_autoVals = $autoVals;
	}

	private function processAutoVal($name, $autoVal) {
		if (preg_match('/^(\w+)\s/', $autoVal, $m)) {
			$tag = $m[1];
		} else {
			$tag = $autoVal;
		}
		switch ($tag) {
			case 'currentUser':
				$r = array('\UserSession::getUser()->id');
				if (preg_match('/^currentUser\s+on\s+(.+)$/', $autoVal, $m)) {
					$r[] = $m[1];
				} else if ($this->table->hasRelation($name)
						&& ($rel = $this->table->getRelationInfo($name)) instanceof \ModelRelationInfoHasReference) {
					$r[] = $rel->referenceField;
				}
				return $r;
		}
		return null;
	}

	private function processColumnsInfo() {

		$tpl = $this->tpl;

		$tpl->modelName = $modelName = $this->config->model;
		$tableName = Inflector::tableFromModel($modelName);
		$table = ModelTable::getTable($tableName);

		$foreignSelects = array();
		$gridForeignSelects = array();
		$relationFields = array();

		// Parse
		foreach ($this->columns->columnsConfig as $name => $col) {

			if (isset($col['name'])) $name = $col['name'];

			$fieldType = null;
			if (isset($col['formField']) && isset($col['formField']['xtype'])) {
				$fieldType = $col['formField']['xtype'];
			} else if (isset($col['type'])) {
				$fieldType = $col['type'];
			}

			if (count($parts = explode('->', $name)) > 1) {
				$field = array_pop($parts);
				$relation = implode('->', $parts);

				// TODO does this properly select multiple relation (Rel1->Rel2->...) ?
				if ($table->getRelationInfo($relation)->getTargetTable()->hasRelation($field)) {
//					$relationFields[$name] = $fieldType;
					$relationFields[$name] = array(
						'type' => $fieldType,
						'col' => $col
					);
				} else {
					if (!isset($foreignSelects[$relation])) $foreignSelects[$relation] = array();
					$foreignSelects[$relation][$name] = $field;
					if (!isset($col['grid']) || $col['grid'] || (isset($col['load']) && $col['load'])) {
						if (!isset($gridForeignSelects[$relation])) $gridForeignSelects[$relation] = array();
						$gridForeignSelects[$relation][$name] = $field;
					}
				}
			} else {
				if ($table->hasRelation($name)) {
//					$relationFields[$name] = $fieldType;
					$relationFields[$name] = array(
						'type' => $fieldType,
						'col' => $col
					);
				}
			}
		}

		// Used relations
//		$relationFields = array_keys($relationFields);

		// Sum up
		if (count($relationFields) == 0 && count($foreignSelects) == 0) return;

		$relationSelectionModes = array(
			'%default_mode%' => array()
			,ModelTable::LOAD_FULL => array()
		);

		$formRelationSelection = array(
			ModelTable::LOAD_ID => array()
			,ModelTable::LOAD_NAME => array()
		);

		$gridRelationSelection = array(
			ModelTable::LOAD_NAME => array()
		);

		//... relations by name
		foreach ($relationFields as $relation => $info) {

			$type = $info['type'];
			$col = $info['col'];

			if (!isset($col['grid']) || $col['grid'] || (isset($col['load']) && $col['load'])) {
				$gridRelationSelection[ModelTable::LOAD_NAME][] = $relation;
			}

			switch ($type) {
				case 'oce.foreigncombo':
				case 'oce.simplecombo':
				case 'gridfield':
					$formRelationSelection[ModelTable::LOAD_ID][] = $relation;
					break;
				default:
					$formRelationSelection[ModelTable::LOAD_NAME][] = $relation;
					break;
			}
		}

		//... relations which fields are selected
		$fullRelationSelection = array();
		foreach ($foreignSelects as $relation => $field) {
			if (!isset($fullRelationSelection[$relation])) {
				$fullRelationSelection[$relation] = $field;
			}
		}
		$gridFullRelationSelection = array();
		foreach ($gridForeignSelects as $relation => $field) {
			if (!isset($gridFullRelationSelection[$relation])) {
				$gridFullRelationSelection[$relation] = $field;
			}
		}

		$gridRelationSelection[ModelTable::LOAD_FULL] = $gridFullRelationSelection;
		$formRelationSelection[ModelTable::LOAD_FULL] = $fullRelationSelection;

		$relationSelectionModes = array(
			'form' => $formRelationSelection,
			'grid' => $gridRelationSelection
		);

		// reverse
		$selectionModeForRelations = array();
		foreach (array('form', 'grid') as $action) {
			$selectionModeForRelations[$action] = array();
			foreach (array(ModelTable::LOAD_NAME, ModelTable::LOAD_ID) as $mode) {
				if (isset($relationSelectionModes[$action][$mode])) {
					foreach ($relationSelectionModes[$action][$mode] as $rel) {
						$selectionModeForRelations[$action][$rel] = $mode;
					}
				}
			}
			if (isset($relationSelectionModes[$action][ModelTable::LOAD_FULL])) {
				foreach ($relationSelectionModes[$action][ModelTable::LOAD_FULL] as $base => $fields) {
					foreach ($fields as $name => $field) {
						$selectionModeForRelations[$action][$name] = $field;
					}
				}
			}
		}

		$tpl->relationSelectionModes = str_replace(
			"'%default_mode%'", '$defaultMode',
			self::cleanVarExport($relationSelectionModes)
		);
		$tpl->selectionModeForRelations = str_replace(
			"'%default_mode%'", '$defaultMode',
			self::cleanVarExport($selectionModeForRelations)
		);
	}

	private static function cleanVarExport($v) {
		$v = var_export($v, true);
		$v = str_replace("\n", ' ', $v);
		$v = preg_replace('/ +/', ' ', $v);
		$v = str_replace('array ( ', "array(", $v);
		$v = str_replace(', )', ")", $v);
		return $v;
	}

}
