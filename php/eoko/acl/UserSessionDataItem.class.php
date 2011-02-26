<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 */

namespace eoko\acl;

class UserSessionDataItem implements ArrayAccess {

	private $startTime;
	private $timeToLive;

	public $id;
	public $data = array();

	function __construct($id, $timeToLive = 3600) {
		$this->id = $id;
		$this->startTime = time();
		$this->timeToLive = $timeToLive;
	}

	public function renewLease() {
		$this->startTime = time();
	}
	
	public function & __get($name) {
		return $this->data[$name];
	}
	
	public function & __set($name, $value) {
		$this->data[$name] = $value;
		return $this->data[$name];
	}

	/**
	 * @return bool
	 */
	public function isExpired() {
		if ($this->startTime === -1) {
			return true;
		} else {
			if (time() - $this->startTime > $this->timeToLive) {
				$this->startTime = -1;
				return true;
			} else {
				return false;
			}
		}
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->data);
	}

	public function offsetGet($offset) {
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value) {
		return $this->data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function pushIdInResponse($name = UserSession::DEFAULT_REQ_DATA_NAME) {
		ExtJSResponse::put($name, $this->id);
	}

}