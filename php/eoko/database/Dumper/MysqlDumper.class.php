<?php

namespace eoko\database\Dumper;

use eoko\database\Dumper;
use eoko\log\Logger;
use eoko\util\collection\Map;

use \IllegalStateException, \IllegalArgumentException;

class MysqlDumper implements Dumper {
	
	/** @var Logger */
	private $logger;
	
	/** @var Map */
	private $config;
	
	private $dumpData = true;
	
	public function __construct(Map $config) {
		$this->logger = new Logger($this);
		$this->config = $config;
	}
	
	public function hasOption($option) {
		switch ($option) {
			case self::DATA: return true;
			default: return false;
		}
	}
	
	public function setOption($option, $value) {
		$m = "set$option";
		if (\method_exists($this, $m)) {
			$this->$m($value);
		} else {
			throw new \IllegalArgumentException(
				get_class($this) . 'does not handle option ' . $option
			);
		}
	}
	
	protected function setData($on) {
		$this->dumpData = $on ? true : false;
	}
	
	public function dump($filename) {
		
		$this->logger->info('Dumping database to {}', $filename);
		
		$dir = dirname($filename);
		$filename = basename($filename);
		
		$owd = getcwd();
		if (!@chdir($dir)) {
			throw new IllegalArgumentException("Database Dump Abort: Directory does not exists: $dir");
		}
		
		try {
			$this->doDump($filename);
		} catch (\Exception $ex) {
			// restore original dir
			chdir($owd);
			throw $ex;
		}
		
		// restore original dir
		chdir($owd);
	}
	
	private function doDump($filename) {
		if (file_exists($filename)) unlink($filename);

		$config = $this->config;
		$cmd = "mysqldump --user $config->user --password=$config->password --opt $config->database | gzip > $filename";
		system($cmd);

		if (!file_exists($filename)) {
			throw new IllegalStateException('Error with dumping the database!');
		}
	}
	
	public function load($filename) {
		
		$this->logger->info('Loading database from {}', $filename);
		
		if (!file_exists($filename)) {
			throw new IllegalArgumentException('Missing dump file: ' . $filename);
		}

		$config = $this->config;
		$cmd = "gunzip < $filename | mysql --user $config->user --password=$config->password $config->database";
		system($cmd);
	}
	
}