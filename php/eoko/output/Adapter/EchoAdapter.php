<?php

namespace eoko\output\Adapter;

use eoko\output\Adapter;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class EchoAdapter implements Adapter {

	public function out($string) {
		echo $string;
	}
}
