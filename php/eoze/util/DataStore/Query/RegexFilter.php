<?php

namespace eoze\util\DataStore\Query;

use eoze\util\DataStore;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 nov. 2011
 */
class RegexFilter extends Filter {

	public function test($element) {
		return !!preg_match($this->value, $this->getFieldValue($element));
	}
}
