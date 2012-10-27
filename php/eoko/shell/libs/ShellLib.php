<?php

/**
 * Description of ShellLib
 *
 * @author merlin
 */

namespace eoko\shell\libs;

class ShellLib {
	
	/**
	 * Get tty width
	 * @return int
	 */
	public function getTtyWidth() {
		return exec("echo `stty size` | awk '{print $1}'");
	}
	
	/**
	 * Get tty Height
	 * @return int 
	 */
	public function getTtyHeight() {
		return exec("echo `stty size` | awk '{print $2}'");
	}
}

