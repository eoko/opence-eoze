<?php
/**
 * @author Éric Ortéga <eric@mail.com>
 */

require_once PHP_PATH . '_/ExtJSResponse.php';

use eoko\php\ErrorException;

class ExceptionHandler {

	private static $instance;

	public function __construct() {
		set_exception_handler(array($this, 'process'));
		ErrorException::registerErrorHandler();
		self::$instance = $this;
	}

	public function  __destruct() {
		Logger::flush();
	}

	public static function processException($ex) {
		self::$instance->process($ex);
	}

	public function process(Exception $ex, $answer = true) {

		if ($ex instanceof UserException) {

			// User error are not logged

			$systemError = false;
			$errorTitle = $ex->hasErrorTitle() ? $ex->getErrorTitle() :
					lang('Erreur');
			$reason = $ex->hasUserMessage() ? $ex->getUserMessage() :
					lang('Commande invalide, veuillez réessayer.');

			$includeTimestamp = false;

			Logger::getLogger('ExceptionHandler')->error('UserException', $ex);

		}

		else {
			$systemError = true;
			$errorTitle = lang('Erreur Système');
			$includeTimestamp = true;

			// Let the client-side handle the default user message for technical
			// errors...
			$reason = null;

			// ... except if a user message has been explicitly set otherwise
			if ($ex instanceof SystemException && $ex->hasUserMessage()) {
				$reason = $ex->getUserMessage();
			}

			// Log exception
			Logger::getLogger()->error('Uncaught exception', $ex);

			// DBG: trying to catch the output of some mystic errors that are
			// not correctly pushed to the logs :(
			error_log($ex->__toString());

			if ($answer && Logger::getLogger()->isActive(Logger::DEBUG)) {
//				header('HTTP/1.1 500 Internal Server Error');
			}
		}

		if ($answer) {
//			if (!$reason && eoko\config\Application::getInstance()->isDevMode()) {
//				$reason = "$ex";
//			}
			ExtJSResponse::failure($reason, $systemError, $errorTitle, true, false,
				$includeTimestamp);
		}
	}

}

$exceptionHandler = new ExceptionHandler();
