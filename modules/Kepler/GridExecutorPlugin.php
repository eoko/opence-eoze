<?php

namespace eoko\modules\Kepler;

use eoko\cqlix\Model;
use eoko\modules\GridModule\GridExecutor\PluginBase;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
class GridExecutorPlugin extends PluginBase {
	
	public function afterSaveModel(Model $model, $wasNew) {
		// Fire comet events
		if ($wasNew) {
			CometEvents::publish($model, 'created');
		} else {
			CometEvents::publish($model, 'modified');
		}
		if ($model->hasPrimaryKey()) {
			CometEvents::publish($model->getTable(), 'modified', array($model->getPrimaryKeyValue()));
		} else {
			CometEvents::publish($model->getTable(), 'modified');
		}
	}
}
