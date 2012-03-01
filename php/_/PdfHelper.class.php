<?php

use eoko\template\Template;

class PdfHelper {

	public static function render($src, $options = array()) {
		
		require_once LIBS_PATH . 'html2pdf.phar';

		ArrayHelper::applyIf($options, array(
			'orientation' => 'P',
			'size' => 'A4',
			'lang' => 'fr',
			'unicode' => true,
			'encoding' => 'UTF-8',
			'margins' => array(5, 5, 5, 8)
		));

		$html2pdf = new HTML2PDF(
			$options['orientation'],
			$options['size'],
			$options['lang'],
			$options['unicode'],
			$options['encoding'],
			$options['margins']
		);

		if ($src instanceof Template) {
			$src = $src->render(true);
		}

		$html2pdf->WriteHTML($src);

		return $html2pdf;
	}

	public static function output($name, $src, $options = array()) {
		self::render($src, $options)->Output($name);
	}

	public static function writeFile($name, $src, $options = array()) {
		self::render($src, $options)->Output($name, 'F');
	}

	/**
	 *
	 * @param string $filename
	 * @param Controller $ff
	 * @return Html2PdfTemplate
	 */
	public static function createTemplate($filename, eoko\file\FileFinder $ff = null) {
//		return new Html2PdfTemplate($filename, $controller);
		return Html2PdfTemplate::create($ff)->setFile($filename);
	}
}

class Html2PdfTemplate extends eoko\template\Template {

	public function imageTagAbsolute($path, $props = null) {
		if ($props !== null) {
			foreach ($props as $name => &$prop) $prop = "$name=\"$prop\"";
			$props = ' ' . implode(' ', $props);
		}
		return '<img src="' . $path . "\"$props />";
	}

	public function echoImageTagAbsolute($path, $props = null) {
		echo $this->imageTagAbsolute($path, $props);
	}

	public function imageTag($path, $props = null) {
		return $this->imageTagAbsolute(IMAGES_PATH . $path, $props);
	}

	public function echoImageTag($path, $props = null) {
		echo $this->imageTag($path, $props);
	}

	public function imageTagBaseDir($path, $props = null) {
		return $this->imageTagAbsolute($this->path($path), $props);
	}

	public function echoImageTagBaseDir($path, $props = null) {
		echo $this->imageTagBaseDir($path, $props);
	}

	protected function createBooleanRenderer($value, $default = null) {
		return new Html2PdfTemplate_RendererImage($this, $value, array(
			true => 'icons/cross.png',
			false => 'icons/tick.png',
			null => 'icons/sport_8ball.png'
		));
	}

	protected function date($value, $nullString = '-') {
		if ($value === null || $value === '') return $nullString;
		return DateHelper::getDateAs($value, DateHelper::DATE_LOCALE);
	}

	protected function echoDate($value, $nullString = '-') {
		echo $this->date($value, $nullString);
	}
}

abstract class Html2PdfTemplate_Renderer {

	protected $value, $default;
	/** @var Html2PdfTemplate */
	protected $template;

	public function __construct(Html2PdfTemplate $tpl, $value, $default = null) {
		$this->template = $tpl;
		$this->default = $default;
		if (is_array($value)) {
			switch (count($value)) {
				case 2: $this->value = $value[0]->__get($value[1]); break;
				case 3: call_user_func_array(array(
					$value[0], $value[1],
					is_array($value[3]) ? $value[3] : array($value[3])
				)); break;
				default: throw new IllegalArgumentException();
			}
		}
	}
}

class Html2PdfTemplate_RendererImage extends Html2PdfTemplate_Renderer {

	protected $assoc;

	public function __construct(Html2PdfTemplate $tpl, $value, array $assoc, $default = null) {
		parent::__construct($tpl, $value, $default);
		$this->assoc = $assoc;
	}

	public function __toString() {
		if (isset($this->assoc[$this->value])) {
			$src = $this->assoc[$this->value];
		} else {
			$src = $this->assoc[$default];
		}
		if (!preg_match('/\..{3,4}?$/', $src)) $src .= '.png';
		return $this->template->imageTag($src);
	}
}