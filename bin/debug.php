<?php

require_once 'init.inc.php';
//Logger::addAppender(new LoggerOutputAppender(false));

Logger::getLogger()->setLevel(Logger::ERROR);
Logger::getLogger('eoko\cache\Cache')->setLevel(Logger::DEBUG);

//require_once 'Benchmark/Iterate.php';
//
//$benchmark = new Benchmark_Iterate;
//
//$parent = array(
//	'key1' => 1,
//	'key2' => 2,
//	'indexed' => array(1,2,3),
//	'sub' => array(
//		'key1' => 1,
//		'key2' => 2,
//		'indexed' => array(1,2,3),
//	)
//);
//
//$child = array(
//	'key2' => 3,
//	'key3' => 3,
//	'indexed[]' => array(4,5,6),
//	'sub' => array(
//		'key2' => 3,
//		'key3' => 3,
//		'indexed' => array(4,5,6),
//	)
//);
//
//$benchmark->run(10000, 'eoze\Config\Helper::extend', $parent, $child);
//
//print_r($benchmark->get());
