<?php

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 25 nov. 2011
 */

use eoko\config\ConfigManager;

$databaseConfig = ConfigManager::get('eoko\database');

if (isset($databaseConfig['test'])) {
	if (!$databaseConfig['test']['database']) {
		throw new Exception('Invalid test database configuration');
	}
	$database = $databaseConfig['test']['database'];
	$user = isset($databaseConfig['test']['user']) ? $databaseConfig['test']['user']
			: $databaseConfig['user'];
	$password = isset($databaseConfig['test']['password']) ? $databaseConfig['test']['password']
			: $databaseConfig['password'];

	if (file_exists($file = DATABASE_DUMP_PATH . 'data.sql.gz')) {
		Logger::info('Syncing test database from file: ' . $file);
		exec("gunzip < $file | mysql --user $user --password=$password $database");
	}
}
