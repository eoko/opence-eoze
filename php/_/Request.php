<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 * @package Opence
 * @subpackage Routing
 */

use eoko\util\Arrays;
use eoko\util\Json;
use eoko\url\Maker as UrlMaker;

use eoze\message\Message;
use eoze\util\Data\DataArray;

class Request implements Message {

	private $params;

	private $originalParams = null;
	
	private $originalRequest = null;
	
	/**
	 * @return Logger
	 */
	protected function getLogger() {
		return Logger::getLogger('Request');
	}

	/**
	 * @return Request
	 */
	public static function getHttpRequest() {
		if (self::$httpRequest !== null) {
			return self::$httpRequest;
		}
		return Router::getInstance()->request;
	}

	public static function setHttpRequest($request) {
		self::$httpRequest = new Request($request);
	}
	
	private function cleanRequestArray($request) {
		unset($request['PHPSESSID']);
		return $request;
	}

	public function __construct($request) {
		
		$this->params = $this->cleanRequestArray($request);

		foreach ($this->params as $key => $param) {
			if (substr($key, 0, 5) === 'json_') {
				$k = substr($key, 5);
				if (isset($this->params[$k])) {
					$this->getLogger()->warn(
						'Json param in request overriding existing one: {}',
						$k
					);
				}
				$this->params[$k] = Json::decode($param);
				unset($this->params[$key]);
			}
		}

		if (isset($this->params['json'])) {

			$jsonData = json_decode(urldecode($this->params['json']), true);

			$this->getLogger()->debug('Json retrieved from request: {}', json_encode($jsonData)); // TODO rx DBG remove

			foreach ($jsonData as $k => $v) {

				if (isset($this->params[$k])) {
					$this->getLogger()->warn('Json param in request overriding existing one: {}', $k);
				}

				$this->params[$k] = $v;
			}

			unset($this->params['json']);
		}
	}
	
	public function buildUrl() {
		$params = array();
		foreach ($this->params as $k => $v) {
			if ($v !== null && $v !== '') {
				if (is_bool($v)) $v = $v ? 1 : 0;
				$params[$k] = $v;
			}
		}
		return UrlMaker::getFor(null, null, $params);
	}

	public function toArray() {
		return $this->params;
	}
	
	private $messageBody = null;
	
	public function getBody() {
		if (!$this->messageBody) {
			$this->messageBody = new DataArray($this->toArray());
		}
		return $this->messageBody;
	}

	public function print_r($preFormatted = true, $return = false) {

		if ($return) ob_start();

		if ($preFormatted) echo '<pre>';
		print_r($this->params);
		if ($preFormatted) echo '</pre>';

		if ($return) return ob_get_clean();
	}

	/**
	 * Whether the request has the given param or not
	 * @param String $key
	 * @param Boolean $excludeEmptyString if set to true, the param will not be
	 * considered set if it contains an empty string
	 * @return Boolean
	 */
	public function has($key, $excludeEmptyString = false) {
		return isset($this->params[$key]) && (!$excludeEmptyString || $this->params[$key] !== '');
	}

	/**
	 *
	 * @param array $keys
	 * @param Bool $excludeEmptyString
	 * @return String the first key found in the request, or false if none is
	 * found
	 */
	public function hasAny($keys, $excludeEmptyString = false) {
		foreach ($keys as $k) {
			if ($this->has($k, $excludeEmptyString)) {
				return $k;
			}
		}
		return false;
	}

	public function getRaw($key) {
		return $this->params[$key];
	}

	/**
	 * Shortcut for {@link Request::get()} to quickly access a boolean value in
	 * the request.
	 * @param string $key
	 * @param bool $exludeEmptyString
	 * @return bool
	 */
	public function is($key, $exludeEmptyString = true) {
		return $this->get($key, false, $exludeEmptyString);
	}

	/**
	 * Get the value of the request for the given key
	 * @param String $key
	 * @param <mixed> $defaultValue		the default value returned if the key
	 * is not set for this request
	 * @param Boolean $excludeEmptyString if set to true, the param will not be
	 * considered set if it contains an empty string
	 * @return Boolean
	 * @see hasKey()
	 */
	public function get($key, $defaultValue = null, $exludeEmptyString = false) {
		return $this->has($key, $exludeEmptyString) ? $this->getRaw($key) : $defaultValue;
	}

	public function getFirst($keys, $defaultValue = null, $excludeEmptyString = false) {
		foreach ($keys as $key) {
			if ($this->has($key, $excludeEmptyString)) {
				return $this->getRaw($key);
			}
		}
		return $defaultValue;
	}

	/**
	 *
	 * @param <type> $keys
	 * @param <type> $defaultValue
	 * @param <type> $excludeEmptyStrings
	 * @return Array
	 */
	public function getAll($keys = null, $defaultValue = null, $excludeEmptyStrings = false) {

		$r = array();

		$originalKeys = $keys;
		if ($keys === null) $keys = array_keys($this->params);
		
		foreach ($keys as $k => $v) {

			$kExcludeEmptyString = $excludeEmptyStrings;
			$kDefault = $defaultValue;

			if (is_array($v)) {
				if (isset($v['excludeEmpty'])) $kExcludeEmptyString = $v['excludeEmpty'];
				if (isset($v['default'])) $kDefault = $v['default'];
				$v = $k;
			}

			if ($kDefault !== null || $originalKeys === null) {
				$r[$v] = $this->get($v, $kDefault, $kExcludeEmptyString);
			} else {
				$r[$v] = $this->req($v, $kExcludeEmptyString);
			}
		}

		return $r;
	}

	/**
	 * Get the value of the request for the given key, or throw a
	 * {@link MissingRequiredRequestParamException} if the key is not set for
	 * this request
	 * @param String $key
	 * @param Boolean $excludeEmptyString if set to true, the param will not be
	 * considered set if it contains an empty string
	 * @param String $exceptionMessage	a custom message for the exception
	 * @return Boolean
	 * @throws MissingRequiredRequestParamException
	 * @see hasKey()
	 */
	public function req($key, $excludeEmptyString = false, $exceptionMessage = false) {
		if (!$this->has($key, $excludeEmptyString)) {
			throw new MissingRequiredRequestParamException($key, $exceptionMessage);
		} else {
			return $this->getRaw($key);
		}
	}

	public function requireFirst($keys, $excludeEmptyString = false) {
		foreach ($keys as $key) {
			if ($this->has($key, $excludeEmptyString)) {
				return $this->getRaw($key);
			}
		}
		throw new MissingRequiredRequestParamException(implode(' | ', $keys));
	}

	public function hasSub($key) {
		return isset($this->params[$key]) && is_array($this->params[$key]);
	}

	public function getSub($key, $default = array()) {
		if ($this->hasSub($key)) {
			return new Request($this->params[$key]);
		} else {
			return new Request($default);
		}
	}

	public function requireSub($key) {
		if (!$this->hasSub($key)) throw new MissingRequiredRequestParamException($key);
		return new Request($this->params[$key]);
	}
	
	public function override($override, $value = null) {
		
		if ($this->originalParams === null) {
			$this->originalParams = $this->params;
		}
		
		if ($value === null) {
			Arrays::apply($this->params, $override);
		} else {
			$this->params[$override] = $value;
		}
	}
	
	public function remove() {
		if ($this->originalParams === null) {
			$this->originalParams = $this->params;
		}
		foreach (func_get_args() as $name) {
			unset($this->params[$name]);
		}
	}

	/**
	 * @return boolean
	 */
	public function isOverriden() {
		return $this->originalParams !== null;
	}
	
	/**
	 * @return Request 
	 */
	public function getOriginal() {
		return new Request($this->originalParams);
	}
}
