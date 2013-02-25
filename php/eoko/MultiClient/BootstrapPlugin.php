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

use eoko\php\SessionManager;
use eoko\config\ConfigManager;
use UserSession;
use eoko\config\Application;

/**
 * Config bootstrap listener.
 *
 * @category Eoze
 * @package MultiClient
 * @since 2013-02-16 04:48
 */
class BootstrapPlugin {

	/**
	 * @var MultiClient
	 */
	private $multiClient;

	/**
	 * @param string $configDirectory Path of the directory where config will be read.
	 */
	public function __construct($configDirectory) {
		$this->multiClient = MultiClient::forDirectory($configDirectory);
	}

	/**
	 * Gets the config array by reading a MultiClient.config.php file, if it exists in the config
	 * directory.
	 *
	 * @return array|bool
	 */
	public function getConfig() {
		return $this->multiClient->getConfig();
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
			if (isset($sessionData['eoko\MultiClient\clientConfig'])) {
				/** @var Client $client */
				$this->setClient($sessionData['eoko\MultiClient\clientConfig']);
			} else {
				throw new Exception\RuntimeException('Client installation information missing.');
			}
		}
	}

	/**
	 * Sets the client to be used. This method will affect the application configuration & environment
	 * to match the specified client.
	 *
	 * @param Client $client
	 * @throws Exception\RuntimeException
	 */
	private function setClient(Client $client) {

		// --- Home directory

		$paths = Application::getInstance()->getPaths();
		$clientHomeDirectory = $client->getHomeDirectory();

		$config = $this->getConfig();
		if (!isset($config['homeDirectory'])) {
			throw new Exception\RuntimeException('Missing config for MultiClient: homeDirectory');
		}

		if (empty($clientHomeDirectory)) {
			throw new Exception\RuntimeException('Missing home directory for client: ' . $client->getName());
		}

		$basePath = realpath($config['homeDirectory']) . '/'
			. rtrim($clientHomeDirectory, '/') . '/';

		$realPath = realpath($basePath);

		if (!$realPath) {
			mkdir($basePath, 0744);
			$realPath = realpath($basePath);
		}

		if (!$realPath) {
			throw new Exception\RuntimeException('Cannot create directory: ' . $basePath);
		}

		$paths->setPath('home', $realPath);

		// --- Database

		// MUST BE DONE **AFTER** HOME DIRECTORY
		// (because the following call will trigger loading in-memory config, and possibly the
		// Cache, that needs to knows the location of the cache directory...)

		ConfigManager::put('eoko\database\database', $client->getDatabaseName());
	}

	/**
	 * Replaces the login adapter with a custom adapter supporting multi clients authentification,
	 * and storing client data in session on successful log in.
	 *
	 * @param \eoko\php\SessionManager $sessionManager
	 * @throws \RuntimeException
	 */
	public function initUserSession(SessionManager $sessionManager) {
		$config = $this->getConfig();
		if ($config !== false) {
			if (isset($config['database'])) {
				UserSession::setLoginAdapter(new LoginAdapter($this->multiClient, $sessionManager));
			} else {
				throw new \RuntimeException('Missing configuration: database');
			}
		}
	}
}
