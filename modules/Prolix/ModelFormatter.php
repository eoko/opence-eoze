<?php

namespace eoko\modules\Prolix;

use Model, ModelTable, ModelColumn;

use eoko\util\Arrays;

use IllegalArgumentException;
use InvalidConfigurationException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */
class ModelFormatter {

	private $modelName;

	private $tableName;

	private $config;
	private $fieldsConfig;

	/**
	 * @var ModelTable
	 */
	private $table;

	public function __construct($name, array $config = null) {
		$this->modelName    = isset($config['model']) ? $config['model'] : $name;
		$this->tableName    = isset($config['table']) ? $config['table'] : $name . 'Table';
		$this->config       = $config;
		$this->fieldsConfig = isset($config['fields']) ? $config['fields'] : null;
	}

	public function getModelName() {
		return $this->modelName;
	}

	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * @return Model
	 */
	public function getTable() {
		return ModelTable::getTable($this->getTableName());
	}

	public function getFieldConfig($alias) {
		if (isset($this->fieldsConfig[$alias])) {
			return $this->fieldsConfig[$alias];
		}
	}

	private function applyUiDefaults(&$uiConfig, $column, $context) {

	}

	private function applyUiConfig(&$fieldConfig, ModelColumn $column) {
		if (isset($fieldConfig['ui'])) {
			foreach ($fieldConfig['ui'] as $context => &$uiConfig) {
				if ($uiConfig !== false) {
					if (is_string($uiConfig)) {
						$uiConfig = array(
							'xtype' => $uiConfig
						);
					} else if (!is_array($uiConfig)) {
						throw new InvalidConfigurationException(
							"Invalid config for field {$column->getName()}'s $context ui. "
							. 'Expected: string|array, found: ' . gettype($uiConfig)
						);
					}
					$uiConfig = $this->applyUiDefaults($uiConfig, $column, $context);
				}
			}
		}
	}

	public function getFields() {
		$return = array();
		foreach ($this->getTable()->getColumns() as $column) {
			assert($column instanceof ModelColumn);
			$alias = $column->getAlias();
			$return[$alias] = array(
				'mapTo'    => $column->getName(),
				'primary'  => $column->isPrimary(),
				'type'     => $column->getType(),
				'length'   => $column->getLength(),
				'required' => !$column->isNullable(),
				'default'  => $column->getDefault(),
			);
			Arrays::apply(
				$return[$alias],
				$this->getFieldConfig($alias)
			);
			$this->applyUiConfig(&$return[$alias], $column);
		}
		dump($return);
		return $return;
	}
}
