<?php

namespace eoko\modules\GridModule\GridExecutor;

use Model;

/**
 * A base adapter implementing {@link GridExecutorPlugin}, with every method
 * doing nothing.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
class PluginBase implements Plugin {
	
	public function afterSaveModel(Model $model, $wasNew) {}

	public function beforeSaveModel(Model $model, $new) {}
}
