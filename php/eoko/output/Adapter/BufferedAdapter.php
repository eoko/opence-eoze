<?php

namespace eoko\output\Adapter;

use eoko\output\Adapter;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class BufferedAdapter implements Adapter {
	
	private $buffer;
	
	public function out($string) {
		$this->buffer[] = $string;
	}
	
	public function getBuffer() {
		return $this->buffer;
	}
}
