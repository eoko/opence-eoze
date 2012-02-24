<?php

namespace eoko\modules\GridModule\GridExecutor;

use Model;
use ModelTable;
use ModelTableQuery;
use eoko\modules\GridModule\GridExecutor;
use Request;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
interface Plugin {
	
	public function configure(GridExecutor $gridExecutor, ModelTable $table, Request $request);
	
	function beforeSaveModel(Model $model, $new);
	
	function afterSaveModel(Model $model, $wasNew);
	
	/**
	 * This event is fired before one or several models are going to be
	 * delete. The whole operation can be cancelled by returning false
	 * in one of these listeners.
	 * 
	 * @return bool `false` to cancel the delete operation.
	 */
	function beforeDelete(array $ids);
	
	function afterDelete(array $ids);
	
	function onCreateQueryContext(Request $request, array &$context);
	
	/**
	 * Execute the action with name $name.
	 * @param Pointer $returnValue A variable that will be set to the action return
	 * value, if the action is executed by this plugin.
	 * @returns true if the action was executed, else false.
	 */
	function executeAction($name, &$returnValue);
	
	function afterCreateLoadQuery(ModelTableQuery $query);
}
