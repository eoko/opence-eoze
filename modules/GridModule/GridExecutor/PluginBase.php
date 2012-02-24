<?php

namespace eoko\modules\GridModule\GridExecutor;

use Model;
use ModelTable;
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
	
	public function configure(GridExecutor $gridExecutor, ModelTable $table) {}
	
	public function afterSaveModel(Model $model, $wasNew) {}

	public function beforeSaveModel(Model $model, $new) {}
	
	public function beforeDelete(array $ids) {}
	
	public function afterDelete(array $ids) {}
	
	public function onCreateQueryContext(Request $request, array &$context) {}
	
	public function executeAction($name, &$returnValue) {
		$method = "action_$name";
		if (method_exists($this, $method)) {
			$returnValue = $this->$method();
			return true;
		}
		return false;
	}
}
