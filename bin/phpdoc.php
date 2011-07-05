#!/usr/bin/php
<?php
require_once 'init.inc.php';

/**
 * Generates php documentation.
 * @author Éric Ortéga <eric@mail.com>
 * @package Opence
 * @subpackage bin
 */

$output = 'HTML:Smarty:PHP';
$package = APP_NAME;
$target = ROOT;
$docDir = 'doc/php';
$ignores = array(
	'inc/lib/', 'doc/', '*.tpl.php', '*.html.php'
);

if (count($ignores) > 0) {
	$ignores = implode(',', $ignores);
} else {
	$ignores = '';
}

$cmd = 'phpdoc '
		. "--target $docDir "
		. "--directory $target "
		. "--output $output "
		. "--defaultpackagename $package "
		. "--filename *.php "
		. "--ignore $ignores ";

echo $cmd;
chdir(ROOT);
system($cmd);
