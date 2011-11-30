<?php

use eoko\util\Json;

throw new UnsupportedOperationException('$_SESSION must not be used');

class UserMessageService {

	private static $SESSION_NAMESPACE = 'UserMessage';
	private static $JS_NAMESPACE = 'messageService';

	protected static $instance = null;

	protected $waitingMessages;
	protected $nextId = 1;

	protected function __construct() {
		if (isset($_SESSION[self::$SESSION_NAMESPACE])) {
			$this->waitingMessages =& $_SESSION[self::$SESSION_NAMESPACE]['messageQueue'];
			$this->nextId = $_SESSION[self::$SESSION_NAMESPACE]['nextId'];
		} else {
			$this->waitingMessages = array();
			$this->nextId = 1;
		}
	}

	public function __destruct() {
		$_SESSION[self::$SESSION_NAMESPACE]['messageQueue'] = $this->waitingMessages;
		$_SESSION[self::$SESSION_NAMESPACE]['nextId'] = $this->nextId;
	}

	protected static function getInstance() {
		if (self::$instance === null) self::$instance = new UserMessageService();
		return self::$instance;
	}

	protected function nextId() {
		return $this->nextId++;
	}

	public static function ask($title, $msg, $options, $callback) {
		self::getInstance()->doAsk($title, $msg, $options, $callback);
	}
	protected function doAsk($title, $msg, $options, $callback) {
		$id = $this->nextId();

		ExtJSResponse::pushIn(self::$JS_NAMESPACE, array(
			'title' => $title
			,'message' => $msg
			,'options' => $options
			,'id' => $id
		));

		$this->waitingMessages[$id] = $callback;
	}

	public static function parseRequest(Request $request) {
		self::getInstance()->doParseRequest($request);
	}
	protected function doParseRequest(Request $request) {
		if ($request->has(self::$JS_NAMESPACE)) {
			$v = Json::decode($request->getRaw(self::$JS_NAMESPACE));
			foreach ($v as $answer) {
				if (!isset($this->waitingMessages[$answer['id']])) {
					Logger::get($this)->error('Invalid message service data: {}', $answer);
				} else {
					$callback = $this->waitingMessages[$answer['id']]['callback'];
					call_user_func_array($callback, $answer['answer']);
					unset($this->waitingMessages[$answer['id']]);
				}
			}
		}
	}

}