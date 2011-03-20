<?php

namespace eoko\modules\MediaManager;

use DataStore;
use eoko\util\Files;
use eoko\file\FileType;

use SecurityException;

class Grid extends GridBase {

	private static $path = MEDIA_PATH;
	private static $baseUrl = MEDIA_BASE_URL;

	/** @var DataStore */
	private static $store = null;

	protected static function getModelName() {
		return null;
	}

	/**
	 * @return DataStore
	 */
	private function getStore($dir, $path, $url) {
		if (true || self::$store === null) {
			self::$store = DataStore::fromArray(self::getFileRows($dir, $path, $url));
			self::$store->putSortAlias('name', 'filename');
			self::$store->putSortAlias('size', 'bytesize');
			self::$store->setDefaultSortOrder('name');
		}
		return self::$store;
	}

	private static function getFileRows($dir, $path, $baseUrl) {
		$rows = array();
		$nextId = 1;
		$urlPath = str_replace(DS, '/', $dir);
		if (substr($dir, -1) !== '/') $urlPath .= '/';
		foreach (Files::listFiles($path, '/^[^.]/', false, Files::LF_PATH_ABS_REL) as $entry) {
			list($rel, $abs) = $entry;
			//$url = $baseUrl . $rel;
			$url = "media/$urlPath$rel";
			$rows[] = array(
				'id' => $nextId++,
				'name' => "<a href=\"$url\">$rel</a>",
				'filename' => $rel,
				'url' => $url,
				'bytesize' => $size = filesize($abs),
				'size' => Files::formatSize($size),
				'datetime' => date('d-m-Y H:i', filemtime($abs)),
				'filemtime' => date('Y-m-d H:i', filemtime($abs)),
				'extension' => Files::getExtension($rel),
				'mime' => FileType::IMAGE()->testFilename($rel) ? 'image' : Files::getExtension($rel),
			);

		}
		return $rows;
	}

	public function load() {

		$dir = '';
		$path = self::$path;
		$url = self::$baseUrl;
		if ($this->request->has('path')) {
			$dir = $path = $this->request->getRaw('path');
			$url = self::$baseUrl . str_replace(DS, '/', $path) . '/';
			$path = realpath(self::$path . $path) . DS;
			// ensure the resulting path is a subdir of the media dir
			if (substr($path, 0, strlen(self::$path)) !== self::$path) {
				throw new SecurityException('GRAVE security exception (probable forbidden files access tentative)');
			}
		}

		$store = $this->getStore($dir, $path, $url);
		$store->sortFromRequest($this->request);
		$store->putInResponseFromRequest($this->request);
		return true;
	}

	public function getMediaDirectories() {
		$dirs = $this->dirs = self::makeDirNode(
			Files::listDirs(self::$path, Files::LF_PATH_ABS_REL, '/^[^.]/', true),
			strlen(self::$path)
		);
		return true;
	}

	private static function makeDirNode($dirs, $len) {
		$r = array();
		foreach ($dirs as $dir) {
			list($name, $abs, $children) = $dir;
			$r[] = array(
				'name' => $name,
				'path' => substr($abs, $len),
				'children' => !count($children) ? null : self::makeDirNode($children, $len)
			);
		}
		return $r;
	}

	public function upload() {
		if (!isset($_FILES['image'])) return false;
		$img = $_FILES['image'];
		$path = $this->request->get('path', "");
		if ($path) $path .= DS;
		move_uploaded_file($img['tmp_name'], MEDIA_PATH . $path . $img['name']);
		return true;
	}

}