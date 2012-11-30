<?php

require __DIR__ . '/php/init.inc.php';
if (class_exists('DatabaseTestCase')) {
	Logger::get('PHPUnit')->warn('Class DatabaseTestCase already exists! That means the shortcut cannot be used'
			. ' in PHPUnit\'s test files...');
} else {
	class_alias('eoze\\test\\phpunit\\DatabaseTestCase', 'DatabaseTestCase');
}

// Ensure vfsStream is installed
include_once 'vfsStream/vfsStream.php';

if (!class_exists('vfsStreamWrapper')) {
	exit(<<<ERROR
vfsStream required for eoze tests:

You can install it with pear:

$ pear channel-discover pear.php-tools.net
$ pear install pat/vfsStream-beta

See: https://github.com/mikey179/vfsStream/wiki/Install
ERROR
	);
}
