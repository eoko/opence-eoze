<?php

namespace eoko\util\file;

use IllegalStateException;

use eoko\util\Singleton;

class FileType {

	public $name;
	private $extensions;
	private $categories;
	public $description;

	function __construct($name, $extensions, $categories, $description = null) {
		$this->name = $name;
		$this->extensions = is_array($extensions) ? $extensions : array($extensions);
		$this->description = $description;
		$this->categories = is_array($categories) ? $categories : array($categories);
	}

	public function getCategories() {
		return $this->categories;
	}

	public function getExtensions() {
		return $this->extensions;
	}

	public static function __set_state($values) {
		return new FileType($values['name'], $values['extensions'], $values['categories'],
				isset($values['description']) ? $values['description'] : null);
	}
}

class FileTypes extends Singleton {

//	protected static $cacheVersion = 0;

	const IMAGE		= 1;
	const PDF		= 2;

	protected $types;
	protected $categories;
	protected $extensions;

	protected function construct() {

		$this->types = array(
			self::IMAGE => new FileType(
				'image',
				array('jpg', 'png', 'gif', 'bmp'),
				self::IMAGE
			),
			self::PDF => new FileType(
				array('document PDF', 'documents PDF'),
				'pdf',
				self::PDF
			),
		);

		$this->categories = array();
		foreach ($this->types as $type) {
			$this->categories = array_merge($this->categories, $type->getCategories());
		}

		$this->extensions = array();
		foreach ($this->types as $type) {
			foreach ($type->getExtensions() as $ext) {
				if (isset($this->extensions[$ext])) throw new IllegalStateException(
					"Dupplicated file type for extension: $ext"
				);
				$this->extensions[$ext] = $type;
			}
		}
	}

}
