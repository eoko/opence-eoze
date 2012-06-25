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
			if (!file_exists($file)) {
				$data[$file] = false;
			} else {
				$data[$file] = filemtime($file);
			}
		}
		$this->fileList = $data;
	}
	
	public function test() {
		foreach ($this->fileList as $file => $mtime) {
			if (file_exists($file)) {
				if (filemtime($file) !== $mtime) {
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