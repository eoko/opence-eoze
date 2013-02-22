<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace eoko\MultiClient;

use eoko\security\LoginAdapter as Base;
use eoko\php\SessionManager;

use eoko\MultiClient\Model\User;
use User as LegacyUser;

/**
 * Login adapter on external MySQL database for multi clients.
 *
 * On successful connections, this adapter will retrieve the user data, as well as the information
 * about the particular client installation (namely: database name, home directory).
 *
 * The client information are stored in the session, to be inspected later by
 * {@link BootstrapPlugin::initinitConfigManager()}.
 *
 * @category Eoze
 * @package Security
 * @since 2013-02-15 18:22
 */
class LoginAdapter implements Base {

	private $host;
	private $username;
	private $password;
	private $databaseName;

	private $userTable = 'users';
	private $clientTable = 'clients';

	/**
	 * @var SessionManager
	 */
	private $sessionManager;

	/**
	 * @var MultiClient
	 */
	private $multiClient;

	public function __construct(MultiClient $multiClient, SessionManager $sessionManager) {
		$this->sessionManager = $sessionManager;
		$this->multiClient = $multiClient;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $reason
	 * @throws Exception\RuntimeException
	 * @return \User
	 */
	public function tryLogin($username, $password, &$reason = null) {

		$user = User::getTable()->findOneWhere('BINARY `username` = ?', $username);

		if (!$user) {
			$reason = 'Identifiant ou mot de passe incorrect.';
			return null;
		}

		if (!$this->verify($password, $user->getPassword())) {
			$reason = 'Identifiant ou mot de passe incorrect.';
			return null;
		}

		$legacyUser = \User::create(array(
			'id' => $user->getId(),
			'username' => $user->getUsername(),
			'nom' => $user->getLastName(),
			'prenom' => $user->getFirstName(),
			'Level' => \Level::create(array(
				'level' => $user->getLevel(),
				'actif' => true,
			)),
		));

		$client = $user->getClient();

		$this->sessionManager->put('eoko\MultiClient\clientConfig', new Client(array(
			'name' => $client->getName(),
			'homeDirectory' => $client->getHomeDirectory(),
			'databaseName' => $client->getDatabaseName(),
		)));

		return $legacyUser;
	}

	private function verify($input, $existingHash) {
		$bcrypt = new Bcrypt;
		return $bcrypt->verify($input, $existingHash);
	}
}
