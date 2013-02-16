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

use eoko\php\SessionManager;
use eoko\config\ConfigManager;
use UserSession;

/**
 * Config bootstrap listener.
 *
 * @category Eoze
 * @package MultiClients
 * @since 2013-02-16 04:48
 */
class BootstrapPlugin {

	private $rootPath;

	public function __construct($configDirectory) {
		$this->rootPath = $configDirectory;
	}

	/**
	 * Inspects session data for clients data, and override in-memory config accordingly. The method
	 * crashes if no client data can be found (multi client requires a known client to run).
	 *
	 * @param \eoko\php\SessionManager $sessionManager
	 * @throws Exception\RuntimeException
	 */
	public function initConfigManager(SessionManager $sessionManager) {
		$sessionData = $sessionManager->getData(false);

		// We want to search for a user's client only if someone is identified!
		if (isset($sessionData['UserSession'])) {
			if (isset($sessionData['eoko\MultiClients\clientConfig'])) {
				/** @var Client $client */
				$client = $sessionData['eoko\MultiClients\clientConfig'];
				ConfigManager::put('eoko\database\database', $client->getDatabaseName());
			} else {
				throw new Exception\RuntimeException('Client installation information missing.');
			}
		}
	}

	/**
	 * Replaces the login adapter with a custom adapter supporting multi clients authentification,
	 * and storing client data in session on successful log in.
	 *
	 * @param \eoko\php\SessionManager $sessionManager
	 */
	public function initUserSession(SessionManager $sessionManager) {
		$clientsConfigFile = $this->rootPath . '/MultiClients.config.php';
		if (file_exists($clientsConfigFile)) {
			$clientsConfig = require $clientsConfigFile;
			UserSession::setLoginAdapter(new LoginAdapter($clientsConfig, $sessionManager));
		}
	}
}
