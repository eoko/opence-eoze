<?php

require_once 'init.inc.php';
//Logger::addAppender(new LoggerOutputAppender(false));

Logger::getLogger()->setLevel(Logger::ERROR);

use eoko\util\YmlReader;

$pwd = '';
UserSession::login('eric', $pwd);

$_REQUEST = array_merge($_REQUEST, array(
	'controller' => 'menu',
	'action' => 'loadUserMenu',
));

$crashed = false;
$n = 0;
do {
	try {
		ob_start();
		Router::getInstance()->route();
		ob_end_clean();
		echo "$n\n";
		$n++;
	} catch (Exception $ex) {
		$crached = $ex;
	}
} while (!$crashed && $n < 200);

if ($crashed) echo $crashed;