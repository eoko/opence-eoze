<?php

namespace eoko\security;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 */
abstract class UserSessionHandler {

	private $listeners;

	public function addListener($event, $fn) {
		$this->listeners[$event][] = $fn;
	}

	protected function fireEvent($event, $_ = null) {
		if ($this->listeners[$event]) {
			$args = array_slice(func_get_args(), 1);
			foreach ($this->listeners[$event] as $fn) {
				call_user_func_array($fn, $args);
			}
		}
	}

	/**
	 * @return User
	 */
	abstract public function getUser();

	abstract public function getUserId($require = false);

	abstract public function isAuthorized($level);

}
