<?php

namespace eoko\modules\Wkhtml2pdf;

use eoko\module\Module;
use Wkhtmltopdf;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 janv. 2012
 */
class Wkhtml2pdf extends Module {
	
	public function createExecutor($type, $action = null, Request $request = null, $internal = false) {
		throw new RuntimeException('EarlReport module is not executable.');
	}

	/**
	 * @return Wkhtmltopdf
	 */
	public function getAdapter($options = array()) {

		require_once __DIR__ . '/lib/Wkhtmltopdf.php';
		
		$config = $this->getConfig();
		
		$wk = new Wkhtmltopdf($options);
		
//		$wk->setBinPath()
		
		return $wk;
	}
}
