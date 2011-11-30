<?php

namespace eoko\php;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 30 nov. 2011
 */
class SessionManager {

	protected $savePath;
	protected $sessionName;
	
	private $listeners;
	
	public function __construct() {
		
		$path = MY_EOZE_PATH . '/sessions';
		session_save_path($path);
		if (!file_exists($path)) {
			mkdir($path, 0700);
		}
		
		session_set_save_handler(
			array($this, "open"), 
			array($this, "close"), 
			array($this, "read"), 
			array($this, "write"), 
			array($this, "destroy"), 
			array($this, "gc")
		);
	}
	
	public function getId() {
		return session_id();
	}
	
	public function addListener($event, $fn) {
		$this->listeners[$event][] = $fn;
	}
	
	private function fireEvent($event, $args = null) {
		if (isset($this->listeners[$event])) {
			$args = array_slice(func_get_args(), 1);
			foreach ($this->listeners[$event] as $fn) {
				call_user_func_array($fn, $args);
			}
		}
	}
	
	public function open($savePath, $sessionName) {
		$this->savePath = $savePath;
		$this->sessionName = $sessionName;
		return true;
	}

	public function close() {
		// your code if any
		return true;
	}

	public function read($id) {
		$file = "$this->savePath/sess_$id";
		return (string) @file_get_contents($file);
	}

	public function write($id, $data) {
		$filename = "$this->savePath/sess_$id";
		$file = @fopen($filename, "w");
		if ($file) {
			$return = fwrite($file, $data);
			fclose($file);
			return $return;
		} else {
			return(false);
		}
	}

	public function destroy($id) {
		$filename = "$this->savePath/sess_$id";
		$this->fireEvent('delete', $id);
		return(@unlink($filename));
	}

	public function gc($maxlifetime) {
		foreach (glob("$this->savePath/sess_*") as $filename) {
			if (filemtime($filename) + $maxlifetime < time()) {
				@unlink($filename);
				preg_match('/sess_(?P<id>.+)$/', $filename, $matches);
				$this->fireEvent('delete', $matches['id']);
			}
		}
		return true;
	}

}
