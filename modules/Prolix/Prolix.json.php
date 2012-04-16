<?php
namespace eoko\modules\Prolix;

use eoko\module\executor\JsonExecutor;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 8 nov. 2011
 */
class Json extends JsonExecutor {
	
	public function index() {
		
		$this->models = array();
		
		dump($tables);
		
		return true;
	}
	
	public function hello() {
		$sub = new Sub\Test;
		$this->msg = "$sub";
		return true;
	}
}
