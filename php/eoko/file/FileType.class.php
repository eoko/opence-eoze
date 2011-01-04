<?php

namespace eoko\file;
use eoko\util\Arrays, eoko\util\Enum;
use \IllegalArgumentException;

class FileType extends Enum {
	
	const HTML		= 'HTML';
	const HTML_TPL	= 'HTML_TPL';
	const JS		= 'JS';
	const CSS		= 'CSS';
	const PHP		= 'PHP';
	const YML       = 'YML';
	
	const PNG       = 'PNG';
	const JPG       = 'JPG';
	const GIF       = 'GIF';
	const IMAGE     = 'IMAGE';
	
	const ALIAS    = 'ALIAS';
	
	public $type;
	public $extensions;
	
	protected static $args = null;
	protected static function getArgs() {
		return array(
			self::HTML => array(
				self::HTML, array('html', 'htm')
			),
			self::HTML_TPL => array(
				self::HTML_TPL, array('html.php', 'html', 'htm.php', 'htm')
			),
			self::JS => array(
				self::JS, 'js'
			),
			self::CSS => array(
				self::CSS, 'css'
			),
			self::PHP => array(
				self::PHP, array('php', 'class.php')
			),
			self::ALIAS => array(
				self::ALIAS, null
			),
			self::YML => array(
				self::YML, 'yml'
			),
			self::PNG => array(
				self::PNG, 'png'
			),
			self::JPG => array(
				self::JPG, 'jpg'
			),
			self::GIF => array(
				self::GIF, 'gif'
			),
			// TODO real FileType composition
			self::IMAGE => array(
				self::IMAGE, array('png', 'jpg', 'gif')
			),
		);
	}
	
	protected function construct($type, $extensions) {
		$this->type = $type;
		$this->extensions = $extensions;
	}
	
	public function __toString() {
		return $this->type;
	}
	
	/**
	 * @param string $filename
	 * @return boolean
	 */
	public function testFilename($filename) {
		if (is_array($this->extensions)) {
			foreach ($this->extensions as $ext) {
				if (substr($filename, -strlen($ext)) === $ext) return true;
			}
			return false;
		} else {
			return substr($filename, -strlen($this->extensions)) === $this->extensions;
		}
	}
	
	public static function parse($in, &$extensions = null, &$typeName = null) {
		if ($in === null) {
			$extensions = false;
			return $typeName = null; 
		} else if (is_string($in)) {
			// TODO: use the static array to return an array.. avoiding useless instanciation
			return self::parse(self::$in(), $extensions, $typeName);
//			$extensions = false;
//			return $typeName = $in;
		} else if ($in instanceof FileType) {
			$extensions = $in->extensions;
			return $typeName = $in->type;
		} else if (is_array($in)) {
			if (Arrays::isAssoc($in)) {
				$extensions = isset($in['extensions']) ? $in['extensions'] : false;
				return $typeName = $in['type'];
			} else {
				// shortcut form, only extensions, null type
				$extensions = $in;
				return $typeName = null;
			}
		} else {
			throw new IllegalArgumentException();
		}
	}
}