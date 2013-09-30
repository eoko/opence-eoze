<?php

namespace eoko\template\Template\PdfRenderingEngine;

require_once LIBS_PATH . 'html2pdf.phar';

class Html2PdfEngine extends AbstractPdfRenderingEngine {

	private $sens = 'P';
	private $format = 'A4';
	private $language = 'fr';

	private function createHtml2Pdf($html) {
		$h = new \HTML2PDF(
			$this->sens, 
			$this->format, 
			$this->language
		);
		$h->writeHTML($html);
		return $h;
	}

	public function toFile($html) {
		$this->createHtml2Pdf($html)->Output($this->getFilename(), 'F');
	}

	public function toOutput($html) {
		$this->createHtml2Pdf($html)->Output($this->getFilename(), false);
	}

	public function toVariable($html) {
		return $this->createHtml2Pdf($html)->Output($this->getFilename(), true);
	}

	public function setOrientation($orientation) {
		if ($orientation === self::ORIENTATION_PORTRAIT) {
			$this->sens = 'P';
		} else {
			$this->sens = 'L';
		}
	}

	public function setLanguage($lang) {
		$this->language = $lang;
	}

	public function setFormat($format) {
		$this->format = $format;
	}
}
