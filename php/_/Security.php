<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @license http://www.planysphere.fr/licenses/psopence.txt
 */

class Security {

	static function cryptPassword($password) {
		return sha1($password);
	}
}

