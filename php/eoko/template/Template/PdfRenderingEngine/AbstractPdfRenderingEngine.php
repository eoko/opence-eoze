<?php

namespace eoko\template\Template\PdfRenderingEngine;

use eoko\options\OptionConstructor;

abstract class AbstractPdfRenderingEngine extends OptionConstructor
		implements \eoko\template\Template\PdfRenderingEngine {

	private $filename = 'default.pdf';

	public function setFilename($filename) {
		$this->filename = $filename;
	}

	protected function getFilename() {
		if (substr($this->filename, -4) !== '.pdf') {
			return $this->filename = "$this->filename.pdf";
		} else {
			return $this->filename;
		}
	}

}
