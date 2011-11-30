<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

use eoko\util\Json;
use eoko\output\Output;

if (!isset($GLOBALS['directAccess'])) { header('HTTP/1.0 404 Not Found'); exit('Not found'); }

class ExtJSResponse {

	private static $enabled = true;

	private static $messages = null;

	private static $vars = array();

	private static $answerJson = true;
	
	public static function purge() {
		self::$vars = array();
	}
	
	private static function out($string) {
		Output::out($string);
	}

	public static function put($name, $val) {
		self::$vars[$name] = $val;
	}
	
	public static function putIf($name, $val) {
		if (!array_key_exists($name, self::$vars)) {
			self::$vars[$name] = $val;
		}
	}

	public static function disableJsonAnswer() {
		self::$answerJson = false;
	}

	public static function pushIn($name, $val, $key = null) {
		if (!isset(self::$vars[$name])) {
			self::$vars[$name] = $key === null ? array($val) : array($key => $val);
		} else if (is_array(self::$vars[$name])) {
			if ($key === null) self::$vars[$name][] = $val;
			else self::$vars[$name][$key] = $val;
		} else {
			throw new IllegalStateException('Scalar variable should be array: '. self::$vars[$name]);
		}
	}

	public static function pushMessage($msg, $title = null) {
		if (is_array($msg)) {
			foreach ($msg as $m) {
				self::pushMessage($m, $title);
			}
		} else {
			$fMsg = $title === null ? $msg : array(
				'title' => $title,
				'message' => $msg
			);
			if (self::$messages === null) {
				self::$messages = $fMsg;
			} else if (is_array(self::$messages)) {
				array_push(self::$messages, $fMsg);
			} else {
				$prev = self::$messages;
				self::$messages = array($prev, $fMsg);
			}
		}
	}

	public static function setEnabled($on) {
		self::$enabled = $on;
	}

// Probably not used anymore
//	public static function loadGrid($result) {
//		self::success($result, false, false);
//	}
//
//	public static function loadOne($result) {
//		self::success($result[0], false, false);
//	}

	public static function successEx($data = null, $message_s = null, $other = array(),
			$die = true, $return = false) {

		if (!self::$enabled) return;

		ArrayHelper::applyIf($other, array(
			'success' => true
		));

		if ($data !== null) $other['data'] = $data;

		if ($message_s !== null) $other['message'] = $message_s;

		self::out(Json::encode($other));
	}

	public static function success($data = null, $die = true, $return = false) {

		if (!self::$enabled) {
			return;
		}

		if ($data === null) {
	 		$out = "{success: true}";
		} else {
			$out = Json::encode((array('success'=>true, 'data' => $data)));
		}
		
		self::out($out);

		if ($return) {
			return $out;
		}

		if ($die) die;
	}

	public static function answerRaw($responseData, $die = true, $return = false) {
		$out = Json::encode($responseData);
		self::out($out);
		if ($return) {
			return ob_get_clean();
		}
		if ($die) {
			die;
		}
	}

	public static function answer($die = true) {

		$response = array(
			'success' => true
		);

		if (self::$messages !== null) {
			$response['message'] = self::$messages;
		}

		self::out(Json::encode(ArrayHelper::apply($response, self::$vars)));

		if ($die) {
			die;
		}
	}

	public static function toArray() {
		return self::$vars;
	}

	/**
	 *
	 * @param String $reason
	 * @param Boolean $systemError		whether the error is a system one (an
	 * error internal to the software -- so that the user cannot do anything...),
	 * or a user one (that is, a misuse of their part, that they can correct).
	 * For user's errors, the reason message should inform the user on what
	 * they should do).
	 * For system errors, the message should be presented as a technical
	 * information report to be given to the support staff to handle the problem.
	 * @param Boolean $includeTimestamp	whether a timestamp data should be
	 * included in the response (that should help finding the corresponding
	 * error log line). Defaults to true for system errors, and false for user
	 * errors.
	 */
	public static function failure($reason = null, $systemError = false,
			$errorTitle = null, $die = true, $return = false, $includeTimestamp = null) {

		if (!self::$enabled) return;

		global $lang;

		if ($includeTimestamp === null) $includeTimestamp = $systemError;
		if ($errorTitle === null) $errorTitle = $systemError ? 
			lang('Erreur Sytème') : lang('Erreur');

		$response = array('success' => false);

		// Format error
		$errors = array(
			'system' => $systemError,
			'title' => $errorTitle
		);

		if ($reason !== null) {
			$errors['reason'] = $reason;
			$response['message'] = $reason;
			$response['title'] = $errorTitle;
		}

		if ($includeTimestamp) $errors['timestamp'] = Router::getActionTimestamp();

		$response['errors'] = $errors;

		ArrayHelper::apply($response, self::$vars);

		self::outputAnswer($response, $die, $return);
	}

	protected static function outputAnswer($response, $die, $return) {
		if (self::$answerJson) {
			$out = utf8_encode(json_encode($response));
			self::out($out);
			if ($return && !$die) {
				return $out;
			}
			if ($die) {
				die;
			}
		} else {
			if (isset($response['success']) && !$response['success']) {
				self::out(isset($response['title']) ? "<h1>$response[title]</h1>" : lang('Erreur'));
				if (isset($response['timestamp'])) {
					self::out('<p>' . lang('Informations techniques: #') 
							. $response['timestamp'] . '</p>');
				}
				if (isset($response['message'])) {
					self::out("<p>$response[message]</p>");
				}
			}
		}
	}

	public static function getProxy() {
		return new ExtJSResponseProxy();
	}
}

class ExtJSResponseProxy {
	public function  __call($name, $arguments) {
		return call_user_func_array(array('ExtJSResponse', $name), $arguments);
	}
}