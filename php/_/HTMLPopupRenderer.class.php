<?php

class HTMLPopupRenderer extends HTMLRenderer {

	const INFO = 1;
	const WARNING = -1;
	const ERROR = -2;

	protected $type;

	public static function getClassFor($type) {
		if ($type === null) {
			return null;
		} else {
			switch ($type) {
				case self::INFO: return 'info';
				case self::ERROR: return 'error';
				case self::WARNING: return 'warning';
				default: throw new IllegalArgumentException('Incorrect value: ' . $type);
			}
		}
	}

	public function setType($type) {
		$this->type = $type;
	}

	protected function beforeRender() {
		parent::beforeRender();
		$this->putClass(self::getClass($this->type));
	}

	public static function createSPAN($content, $type = null) {
		return new HTMLPopupRenderer('span', $content);
	}
}