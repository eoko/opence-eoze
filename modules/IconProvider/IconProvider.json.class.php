<?php

namespace eoko\modules\IconProvider;

use eoko\module\executor\JsonExecutor;

class Json extends JsonExecutor {

	public function getIconList() {

		$module = $this->getModule();

		$baseClass = $module->getConfig()->get('cssBaseClass');
		$baseClass = str_replace('.', ' ', $baseClass);

		$data = array();
		$i = 1;
		foreach ($module->listFiles() as $file) {
			$data[] = array(
				'id' => $i++,
				'class' => "$baseClass " . $module->makeClass($file),
				'label' => preg_match('/^(.+)\.\w+$/', $file, $m) ? $m[1] : $file,
			);
		}

		usort($data, function($o1, $o2) {
			return strnatcasecmp($o1['label'], $o2['label']);
		});

		$this->data = $data;

		return true;
	}
}
