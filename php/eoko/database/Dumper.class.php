<?php

namespace eoko\database;

interface Dumper {
	
	const DATA = 'data';

	function dump($filename);
	
	function load($filename);
	
	/**
	 * @return boolean TRUE if the given option (see Dumper constants) is 
	 * handled by this dumper, else FALSE
	 */
	function hasOption($option);
	
	/**
	 * Set the value for the given option.
	 * @throws \IllegalArguementException if the given option is not handled
	 * by this type of Dumper
	 */
	function setOption($option, $value);
}

//class Dumper {
//	
//	private $logger;
//	private $initialDir;
//	
//	public function __construct() {
//		$this->logger = Logger::get($this);
//	}
//	
//	private function before() {
//		$this->initialDir = getcwd();
//	}
//	
//	private function after() {
//		chdir($this->initialDir);
//	}
//	
//	public function dump() {
//		$this->before();
//		try {
//			$this->doDump();
//		} catch (\Exception $ex) {
//			$this->after();
//			$this->logger->error($ex);
//			return false;
//		}
//		$this->after();
//		return false;
//	}
//	
//	private function doDump() {
//		
//		$out = $this->getOutFilename();
//		$this->logger->info('Dumping database to {}{}', getcwd(), $out);
//
//		if (file_exists($out)) unlink($out);
//		
//		$params = $this->config;
//
//		$cmd = "mysqldump --user $config->user --password=$config->password --opt $config->database | gzip > $out";
//		system($cmd);
//
//		if (!file_exists($out)) {
//			throw new Exception('Error with dumping the database!');
//		}
//	}
//	
//	public function load() {
//		
//	}
//}