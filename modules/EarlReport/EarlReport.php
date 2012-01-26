<?php

namespace eoko\modules\EarlReport;

use eoko\module\Module;
use RuntimeException;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 26 janv. 2012
 */
class EarlReport extends Module {

	public function createExecutor($type, $action = null, Request $request = null, $internal = false) {
		throw new RuntimeException('EarlReport module is not executable.');
	}
}
