<?php

namespace eoko\mvc;

use eoko\util\Arrays;
use eoko\config\ConfigManager;

/**
 * Eoze legacy request data reader.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 juil. 2012
 */
class LegacyRequestReader extends AbstractRequestReader {

	const CONFIG_NODE = 'eoko/router';

	public function createRequest() {

		$data = $_REQUEST;

		if (
			isset($_SERVER['REQUEST_METHOD'])
				&& $_SERVER['REQUEST_METHOD'] === 'POST' 
				&& isset($_SERVER['CONTENT_TYPE'])
				&& preg_match('/(?:\bapplication\/|\/)json(?:\b|;)/', $_SERVER['CONTENT_TYPE']) 
			|| isset($_GET['contentType'])
				&& preg_match('/(?:^|\/)json$/i', $_GET['contentType'])
		) {

			Arrays::apply($data, json_decode(file_get_contents("php://input"), true));

			unset($data['contentType']);
		}

		// Route data
		foreach ($this->routeMatch->getParams() as $name => $value) {
			if (substr($name, 0, 1) !== '_') {
				if (!isset($data[$name])) {
					$data[$name] = $value;
				}
			}
		}

		// Default controller
		if (!isset($data['controller'])) {
			$data['controller'] = ConfigManager::get(self::CONFIG_NODE, 'indexModule');
		}

		return new \Request($data);
	}
}
