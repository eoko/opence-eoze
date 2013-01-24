<?php

namespace eoze\util\ObjectProxy;

use eoze\util\ObjectProxy;
use Closure;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 oct. 2011
 */
class CallbackObjectProxy extends ObjectProxy {

	private $createFunction;

	public function __construct(&$hook, Closure $createFunction) {
		parent::__construct($hook);
		$this->createFunction = $createFunction;
	}

	protected function createObject() {
		return call_user_func($this->createFunction);
	}
}
