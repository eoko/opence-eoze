<?php

namespace eoko\modules\GridModule\GridExecutor;

use Model;
use ModelTable;
use eoko\modules\GridModule\GridExecutor;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
interface Plugin {
	
	public function configure(GridExecutor $gridExecutor, ModelTable $table);
	
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
}
