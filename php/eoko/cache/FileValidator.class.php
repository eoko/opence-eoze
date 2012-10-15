<?php

namespace eoko\cache;

use eoko\log\Logger;

class FileValidator {
	
	private $fileList;
	
	public function __construct($fileList) {
		
		if (!is_array($fileList)) {
			$fileList = array($fileList);
		}
		
		$data = array();
		foreach ($fileList as $file) {
			$data[$file] = self::filemtime($file);
		}
		$this->fileList = $data;
	}
	
	// tests all files in a directory, returning the last mtime
	private static function filemtime($file) {
		if (!file_exists($file)) {
			return false;
		} else if (is_dir($file)) {
			$di = new \DirectoryIterator($file);
			$mtime = filemtime($file);
			foreach ($di as $fi) {
				if ($fi->isFile()) {
					if ($fi->getMTime() > $mtime) {
						$mtime = $fi->getMTime();
					}
				}
			}
			return $mtime;
		} else {
			return filemtime($file);
		}
	}
	
	public function test() {
		foreach ($this->fileList as $file => $mtime) {
			if (file_exists($file)) {
				if (self::filemtime($file) !== $mtime) {
					Logger::get($this)->debug(
						'File {} has changed (previously: {})',
						$file,
						$mtime === false ? 'was not present' : $mtime
					);
					return false;
				}
			} else {
				if ($mtime !== false) {
					Logger::get($this)->debug(
						'File {} previously present has been deleted',
						$file
					);
					return false;
				}
			}
		}
		return true;
	}
}