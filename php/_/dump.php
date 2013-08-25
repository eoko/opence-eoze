<?php

if (!isset($dumpPre)) { $dumpPre = '<pre>'; $endDumpPre = '</pre>'; }

// Debug function
function dump($var, $maxDeep = 50, $die = true, $deep = 0) {
	global $dumpPre, $endDumpPre;

//	while (ob_get_level()) {
//		ob_end_clean();
//	}

//	dump_trace(false);
	$trace = debug_backtrace();
//	Logger::get('dump')->debug('dumping: {}:{}', $trace[0]['file'], $trace[0]['line']);
	foreach ($trace as $tr) {
		if (isset($tr['file']) && $tr['file'] !== __FILE__) {
			Logger::get('dump')->debug('dumping: {}:{}', $tr['file'], $tr['line']);
			break;
		}
	}
//	if (isset($trace[0]['file'])) {
//		Logger::get('dump')->debug('dumping: {}:{}', $trace[0]['file'], $trace[0]['line']);
//	}

	if ($deep === 0) echo $dumpPre;
	$tab = str_repeat("\t", $deep);
	if (is_array($var)) {
		echo 'Array(' . "\n";
		foreach ($var as $k => $v) {
			if ($v === null) $v = '<b>null</b>';
			else if ($v === false) $v = '<b>false</b>';
			else if ($v === true) $v = '<b>true</b>';
			if (is_array($v) && $maxDeep > $deep) {
				echo "$tab\t[$k] => ";
				dump($v, $maxDeep, false, $deep + 1);
			} else {
				if (is_object($v)) $v = method_exists($v, '__toString') ? "$v" : get_class($v);
				echo "$tab\t[$k] => $v\n";
			}
		}
		echo "$tab)\n";
	} else if (is_object($var)) {
		print_r($var);
//		echo get_class($var);
	} else {
		if ($var === null) $var = '<b>null</b>';
		else if ($var === false) $var = '<b>false</b>';
		else if ($var === true) $var = '<b>true</b>';
		echo $tab . $var . "\n";
	}
	if ($deep === 0) echo $endDumpPre;
	if ($die) die;
}
$dump_after_mark = false;
function dump_after($var, $die = true, $maxDeep = 50, $deep = 0) {
	global $dump_after_mark;
	if ($dump_after_mark)
		dump ($var, $maxDeep, $die, $deep);
}
function dump_is_after() {
	global $dump_after_mark;
	return $dump_after_mark;
}
function dump_mark($n = null) {
	static $count = null;

	if ($n !== null) {
		if ($count === null) {
			$count = $n;
		} else {
			$count--;
		}
		if ($count > 0) return;
	}
	global $dump_after_mark;
	$dump_after_mark = true;
}
function dump_trace($die = true, $light = true) {
	global $dumpPre, $endDumpPre;
	echo $dumpPre;
//	debug_print_backtrace(false);
	if ($light) {
		$e = new Exception();
		print_r(str_replace(ROOT, '', $e->getTraceAsString()));
	} else {
		ob_start();
		debug_print_backtrace();
		echo str_replace(ROOT, '', ob_get_clean());
	}
	echo $endDumpPre;
	if ($die) die;
}
function dump_trace_after($die = true) {
	global $dump_after_mark;
	global $dumpPre, $endDumpPre;
	if ($dump_after_mark) {
		echo $dumpPre;
		$e = new Exception();
		print_r(str_replace(ROOT, '', $e->getTraceAsString()));
		echo $endDumpPre;
		if ($die) die;
	}
}
function dump_trace_if($condition, $die = true) {
	if ($condition) {
		dump_trace($die);
	}
}
function trace($n = 1, $die = false) {
	global $dumpPre, $endDumpPre;
	$trace = debug_backtrace();
	$n = $n === 0 ? count($trace)-1 : min($n, count($trace)-1);
	echo '<hr>';
	for ($i=1; $i<$n+1; $i++) {
		$t = $trace[$i];
		$pos = isset($t['file']) ? basename($t['file']) . ":$t[line]" : "internal";
		$fn = isset($t['class']) ? "$t[class]->$t[function]" : $t['function'];
		foreach ($t['args'] as &$a) {
			if (is_string($a)) $a = "'$a'";
			else if (is_array($a)) $a = 'Array[' . count($a) . ']';
			else if (is_object($a)) $a = get_class($a);
		}
		$args = implode(', ', $t['args']);
		$fill = str_repeat(' ', max(0, 50 - strlen($pos)));
		echo "$dumpPre$pos$fill$fn($args)$endDumpPre";
	}
	if ($die) die;
}
//function out($var, $die = false) {
//	if (is_array($var) || is_object($var)) {
//		print_r($var);
//	} else {
//		echo $var . "\n";
//	}
//	if ($die) die;
//}
function dumpl($var, $maxDeep = 3) {
	dump($var, $maxDeep, false);
}
function pre($msg = null) { echo '<pre>' . $msg . "\n"; }


function countr($o) {
	if (is_array($o)) return count(o);
	else return $o->count();
}
