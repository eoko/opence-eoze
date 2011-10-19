<?php
/**
 * @author Éric <eric@planysphere.fr>
 */
namespace eoko\test;

putenv('APPLICATION_ENV=test');
define('UNIT_TEST', true);

set_include_path(get_include_path() . ':' . __DIR__);

require_once __DIR__ 
		. DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . '..'
		. DIRECTORY_SEPARATOR . 'php'
		. DIRECTORY_SEPARATOR . 'index.php';

restore_error_handler();
restore_exception_handler();

define('TMP_DIR', CACHE_PATH . 'tmp' . DS);
if (!is_dir(TMP_DIR)) {
	mkdir(TMP_DIR);
}