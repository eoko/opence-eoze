<?php

namespace eoko\modules\root;

use eoko\module\executor\JsonExecutor;

use eoko\util\Arrays;
use eoko\module\Module;

use Exception;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 2 déc. 2011
 */
class Multipart extends JsonExecutor {
	
	public function index() {
		
		$responses = array();
		
		foreach ($this->request->get('requests') as $request) {
			
			$data = $request['data'];
			
			$result = array(
				'id' => $request['id'],
			);

			// TODO implement bufferizable for other type of content 
			// (raw data is easy to buffer!)
			if (!isset($data['accept']) || $data['accept'] !== 'json') {
				$result['cannotBuffer'] = true;
			}
			
			else {
				// TODO this code probably doesn't catch PHP errors...
				// So, one request error could crash the whole batch :(
				try {
					$executor = Module::parseRequestAction(new \Request($data));

					if ($executor instanceof JsonExecutor) {
						$result['data'] = $executor(true);
					} 
					
					// TODO ... implement bufferizable for other type of
					// executor (see upper)
					
					else {
						$result['cannotBuffer'] = true;
					}
				} catch (Exception $ex) {
					// TODO better interpretation of the error
					// => is the exception interpretable to give the
					//    user a better understanding of what happened?
					$result['error'] = true;
				}

				$responses[$request['id']] = $result;
			}
		}
		
		$this->results = $responses;
		
		return true;
	}
}
