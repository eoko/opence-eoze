<?php

require __DIR__ . '/php/init.inc.php';
if (class_exists('DatabaseTestCase')) {
	Logger::warn('Class DatabaseTestCase already exists! That means the shortcut cannont be used'
			. ' in PHPUnit\'s test files...');
} else {
	class_alias('eoze\\test\\phpunit\\DatabaseTestCase', 'DatabaseTestCase');
}
