<?php

namespace eoko\modules\Kepler;

use eoko\cqlix\Model;
use ModelTable;
use eoko\modules\GridModule\GridExecutor;
use eoko\modules\GridModule\GridExecutor\PluginBase;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
class GridExecutorPlugin extends PluginBase {
	
	/**
	 *
	 * @var ModelTable
	 */
	private $table;
	
	public function configure(GridExecutor $gridExecutor, ModelTable $table) {
		$this->table = $table;
	}
	
	public function afterSaveModel(Model $model, $wasNew) {
		// Fire comet events
		if ($wasNew) {
			CometEvents::publish($model, 'created');
			if ($model->hasPrimaryKey()) {
				CometEvents::publish($model->getTable(), 'created', array($model->getPrimaryKeyValue()));
			} else {
				CometEvents::publish($model->getTable(), 'created');
			}
		} else {
			CometEvents::publish($model, 'modified');
			if ($model->hasPrimaryKey()) {
				CometEvents::publish($model->getTable(), 'modified', array($model->getPrimaryKeyValue()));
			} else {
				CometEvents::publish($model->getTable(), 'modified');
			}
		}
	}

	public function afterDelete(array $ids) {
		CometEvents::publish($this->table, 'removed', $ids);
	}

}