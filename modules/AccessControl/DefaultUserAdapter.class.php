<?php

namespace eoko\modules\AccessControl;

use eoko\acl\AclUserAdapter;
use Security;
use UserTable, User;

class DefaultUserAdapter implements AclUserAdapter {

	public function findUser($username, $password) {
		return UserTable::findOneWhere(
//			'username = ? AND pwd = ?',
			'username = ? AND password = ?',
			array($username, Security::cryptPassword($password))
		);
	}
	
	public function validateUser(User $user) {
		// ok
	}
}