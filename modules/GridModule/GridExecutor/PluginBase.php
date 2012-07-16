<?php

namespace eoko\modules\GridModule\GridExecutor;

use Model;
use ModelTable;
use ModelTableQuery;
use eoko\modules\GridModule\GridExecutor;
use Request;

/**
 * A base adapter implementing {@link GridExecutorPlugin}, with every method
 * doing nothing.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
class PluginBase implements Plugin {
	
	/**
	 * @var GridExecutor
	 */
	private $gridExecutor;

	/**
	 * @var ModelTable
	 */
	private $table;
	
	/**
	 * @var Request
	 */
	private $request;
	
	public function configure(GridExecutor $gridExecutor, ModelTable $table, Request $request) {
		$this->gridExecutor = $gridExecutor;
		$this->table = $table;
		$this->request = $request;
	}
	
	/**
	 * @return GridExecutor
	 */
	protected function getGridExecutor() {
		return $this->gridExecutor;
	}

	/**
	 * @return ModelTable
	 */
	protected function getTable() {
		return $this->table;
	}
	
	/**
	 * @return Request
	 */
	protected function getRequest() {
		return $this->request;
	}
	
	public function executeAction($name, &$returnValue) {
		$method = "action_$name";
		if (method_exists($this, $method)) {
			$returnValue = $this->$method();
			return true;
		}
		return false;
	}
	
	// Empty listener implementations
	
	public function addModelContext(Model $model) {}

	public function afterSaveModel(Model $model, $wasNew) {}

	public function beforeSaveModel(Model $model, $new) {}
	
	public function beforeDelete(array $ids) {}
	
	public function afterDelete(array $ids) {}
	
	public function onCreateQueryContext(Request $request, array &$context) {}
	
	public function configureLoadQuery(ModelTableQuery $query) {}
	
	public function afterExecuteLoadQuery(ModelTableQuery $query) {}
}
