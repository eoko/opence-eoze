<?php

namespace eoko\mvc;

use Zend\Http\Request;
use eoko\util\Arrays;

/**
 * Eoze legacy request data reader.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 juil. 2012
 */
class LegacyRequestReader implements RequestReader {
	
	/**
	 * @var Request
	 */
	private $request;
	
	public function __construct(Request $request) {
		$this->request = $request;
	}

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
		
		// legacy routing
		if (isset($data['route'])) {
			\eoko\url\Maker::populateRouteRequest($data);
		}
		
		return new \Request($data);
	}
}
