<?php

namespace eoko\modules\MercurialConsole;

use eoko\module\executor\JsonExecutor;

class Json extends JsonExecutor {
	
	public function id() {
		$hg = '/usr/bin/hg';
		$id = trim(`$hg id -ibt`);
		$log = `$hg log -l 1`;
		preg_match('/\ndate:\s+([^\n]+)\n/', $log, $m);
		$this->output = "<strong>Version:</strong> $id ($m[1])";
		return true;
	}
	
}