<?php

namespace eoko\modules\GridModule\GridExecutor;

use Model;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 1 déc. 2011
 */
interface Plugin {
	
	function beforeSaveModel(Model $model, $new);
	
	function afterSaveModel(Model $model, $wasNew);
}
