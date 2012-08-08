<?php

namespace eoko\mvc;

use Request as RequestData;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 juil. 2012
 */
class RestRequestReader extends AbstractRequestReader {

	public function createRequest() {
		$data = array();
		
		foreach ($this->routeMatch->getParams() as $key => $value) {
			if (substr($key, 0, 1) !== '_') {
				$data[$key] = $value;
			}
		}
		
		$contentType = $this->request->getHeader('content-type');
		if ($contentType) {
			if ($contentType->getFieldValue() === 'application/json') {
				foreach (json_decode($this->request->getContent(), true) as $k => $v) {
					$data[$k] = $v;
				}
			}
		} else {
			throw new \UnsupportedOperationException(
				'Unsupported request content-type (ie. unsupported message format): '
				. $contentType->getFieldValue()
			);
		}
		
		return new RequestData($data);
	}
}
