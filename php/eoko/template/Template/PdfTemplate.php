<?php

namespace eoko\template\Template;

use eoko\log\Logger;

use IllegalArgumentException;

class PdfTemplate extends \eoko\template\Template {

	/**
	 * @var PdfRenderingEngine
	 */
	private $engine = array(
		'class' => 'Html2PdfEngine',
		'orientation' =>  PdfRenderingEngine::ORIENTATION_PORTRAIT,
		'language' => 'fr',
		'format' => 'A4',
	);

	public function __construct($opts = null) {
		parent::__construct($opts);
		$this->createEngine();
	}

	private function createEngine() {
		if (!($this->engine instanceof PdfRenderingEngine)) {
			if (!isset($this->engine['class'])) {
				throw new IllegalArgumentException('Missing engine class');
			}
			$class = $this->engine['class'];
			if (!strstr($class, '\\')) {
				// I guess, this is the base class name
				$class = __NAMESPACE__ . "\\PdfRenderingEngine\\$class";
			}
			$this->engine = new $class($this->engine);
		}
	}

	/**
	 * @return PdfRenderingEngine
	 */
	public function getEngine() {
		return $this->engine;
	}

	public function render($return = null) {
		if ($return) {
			return $this->engine->toVariable(parent::render(true));
		} else {
			if (!headers_sent()) {
				header('Content-type: application/pdf');
			} else {
				Logger::get($this)->warn('Headers already sent, pdf output mime type'
						. ' will most probably be corrupted');
			}
			$this->engine->toOutput(parent::render(true));
		}
	}

	public function toFile($filename = null) {
		if ($filename) {
			$this->engine->setFilename($filename);
		}
		return $this->engine->toFile(parent::render(true));
	}

}
