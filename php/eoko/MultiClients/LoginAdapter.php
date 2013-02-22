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

namespace eoko\MultiClients;

use eoko\security\LoginAdapter as Base;
use User;
use eoko\php\SessionManager;

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
	 * @var MultiClients
	 */
	private $multiClient;

	public function __construct(MultiClients $multiClient, SessionManager $sessionManager) {
		$this->sessionManager = $sessionManager;
		$this->multiClient = $multiClient;
//
//		$this->host = isset($config['host']) ? $config['host'] : null;
//		$this->username = isset($config['username']) ? $config['username'] : null;
//		$this->password = isset($config['password']) ? $config['password'] : null;
//		$this->databaseName = isset($config['database']) ? $config['database'] : null;
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $reason
	 * @throws Exception\RuntimeException
	 * @return \User
	 */
	public function tryLogin($username, $password, &$reason = null) {
//		dump($this);

//		$dbHost = $this->host;
//		$dbUsername = $this->username;
//		$dbPassword = $this->password;
//		$dbName = $this->databaseName;
//
//		$mysql = mysql_connect($dbHost, $dbUsername, $dbPassword);
//
//		mysql_query('SET NAMES utf8');
//
//		if (!$mysql) {
//			throw new Exception\RuntimeException('Cannot connect to clients mysql server.');
//		}
//
//		if (!mysql_select_db($dbName, $mysql)) {
//			throw new Exception\RuntimeException('Cannot select database ' . $dbName . ' on clients mysql server.');
//		}
//
//		$username = mysql_real_escape_string($username, $mysql);

		$sql = <<<SQL
SELECT
		`User`.*,
		`Client`.`name` AS `clientName`,
		`Client`.`home_directory` AS `clientHomeDirectory`,
		`Client`.`database_name` AS `clientDatabaseName`
	FROM `$this->userTable` AS `User`
	LEFT JOIN `$this->clientTable` AS `Client` ON `User`.`client_id` = `Client`.`id`
	WHERE BINARY `username` = "$username" LIMIT 1;
SQL;

		$result = $this->multiClient->getDatabase()->query($sql);
		$data = $result->fetchObject();
//		$result = mysql_query($sql, $mysql);
//
//		$data = mysql_fetch_object($result);
//
//		mysql_close($mysql);

		if (!$data) {
			$reason = 'Identifiant ou mot de passe incorrect.';
			return null;
		}

		if (!$this->verify($password, $data->password)) {
			$reason = 'Identifiant ou mot de passe incorrect.';
			return null;
		}

		$user = \User::create(array(
			'id' => $data->id,
			'username' => $data->username,
			'nom' => $data->last_name,
			'prenom' => $data->first_name,
			'Level' => \Level::create(array(
				'level' => $data->level,
				'actif' => true,
			)),
		));

		$this->sessionManager->put('eoko\MultiClients\clientConfig', new Client(array(
			'name' => $data->clientName,
			'homeDirectory' => $data->clientHomeDirectory,
			'databaseName' => $data->clientDatabaseName,
		)));

		return $user;
	}

	private function verify($input, $existingHash) {
		$bcrypt = new Bcrypt;
		return $bcrypt->verify($input, $existingHash);
	}
}
