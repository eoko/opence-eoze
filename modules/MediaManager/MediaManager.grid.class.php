<?php

namespace eoko\modules\MediaManager;

use DataStore;
use eoko\util\Files;
use eoko\file\FileType;
use eoko\log\Logger;
use eoko\config\Application;

use SecurityException;
use RuntimeException;

/**
 * @method MediaManager getModule
 */
class Grid extends GridBase {

	private static $path;
	private static $baseUrl;

	/** @var DataStore */
	private static $store = null;

	protected function construct() {
		parent::construct();
		self::$path = $this->getModule()->getDownloadPath();
		if (null !== $url = $this->getConfig()->get('downloadUrl')) {
			self::$baseUrl = SITE_BASE_URL . $url . '/';
		} else if (defined(MEDIA_BASE_URL)) {
			Logger::get($this)->error('Deprecated feature flagged for imminent removal');
			self::$baseUrl = MEDIA_BASE_URL;
		} else {
			throw new RuntimeException('Missing configuration: downloadUrl');
		}
	}

	private function getConfig() {
		return $this->getModule()->getConfig();
	}

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
        
        $formatDate = function($time) {
//            return strftime('%a %d %b %Y %T', $time);
            return strftime('%c', $time);
        };
        if (!setlocale(LC_ALL, "fr_FR.utf8")) {
            if (!setlocale(LC_ALL, "fr_FR")) {
                $formatDate = function($time) {
                    return date('d/m/Y H:i:s', $time);
                };
            }
        }
        
        // Directories
        foreach(Files::listDirs($path, Files::LF_PATH_ABS_REL) as $entry) {
            list($rel, $abs) = $entry;
			$url = self::$baseUrl . "$urlPath$rel";
			$rows[] = array(
				'id' => $nextId++,
//				'name' => "<a href=\"$url\">$rel</a>",
				'filename' => $rel,
				'url' => $url,
                'imageUrl' => null,
				'bytesize' => null,
				'size' => count(glob("$abs/*")) . ' éléments',
//				'datetime' => date('d-m-Y H:i', filemtime($abs)),
				'filemtime' => date('Y-m-d H:i:s', filemtime($abs)),
                'hsFilemtime' => $formatDate(filemtime($abs)),
				'extension' => null,
				'mime' => 'folder',
                'type' => 'Dossier',
			);
        }
        
        // Files
		foreach (Files::listFiles($path, '/^[^.]/', false, Files::LF_PATH_ABS_REL) as $entry) {
			list($rel, $abs) = $entry;
			//$url = $baseUrl . $rel;
			$url = self::$baseUrl . "$urlPath$rel";
			$rows[] = array(
				'id' => $nextId++,
				'name' => "<a href=\"$url\">$rel</a>",
				'filename' => $rel,
				'url' => $url,
                'imageUrl' => FileType::IMAGE()->testFilename($rel) ? $url : null,
				'bytesize' => $size = filesize($abs),
				'size' => Files::formatSize($size),
//				'datetime' => date('d-m-Y H:i', filemtime($abs)),
				'filemtime' => date('Y-m-d H:i', filemtime($abs)),
                'hsFilemtime' => $formatDate(filemtime($abs)),
				'extension' => Files::getExtension($rel),
				'mime' => FileType::IMAGE()->testFilename($rel) ? 'image' : Files::getExtension($rel),
                'type' => self::getFileTypeName(Files::getExtension($rel)),
			);
		}
		return $rows;
	}
    
    protected static function getFileTypeName($extension) {
        switch (strtolower($extension)) {
            case 'odt': return 'Texte OpenDocument';
            case 'ods': return 'Classeur OpenDocument';
            case 'xls': return 'Feuille de calcul Microsoft Excel';
            case 'xlsx': return 'Feuille de calcul Microsoft Excel 2007';
            case 'doc': return 'Document Microshoft Word';
            case 'docx': return 'Document Microshoft Word 2007';
            case 'gz': return 'Archive gzip';
            case 'png':
            case 'jpg':
                return 'Image ' . strtoupper($extension);
            default: return 'Document ' . strtoupper($extension);
        }
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
				dump(array(
					$path,
					self::$path,
				));
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

	private function resolveMediaPath($path) {
		if (!$path) {
			return '';
		}
		$path = trim($path, '/\\');
		$destination = Application::getInstance()->resolvePath('media/') . $path;
//		$path = str_replace('//', '/', $path);
		// TODO this security is *weak*
		if (strstr($path, '..')) {
			throw new SecurityException('Trying to resolve restricted path: ' . $destination);
		}
		return $destination;
	}

	public function upload() {
		if (!isset($_FILES['image'])) {
			return false;
		}
		$img = $_FILES['image'];
		$filename = ltrim($img['name'], '\\/');
		$path = trim($this->request->get('path', ''), '/\\');
		move_uploaded_file($img['tmp_name'], $this->resolveMediaPath($path . $filename));
		return true;
	}

	public function delete() {
		$dir = $this->request->get('path', '');
		$dir = str_replace('/', DS, $dir);
		if ($dir && substr($dir, -1) !== DS) $dir .= DS;
        $file = $this->request->req('file');
        if ($file === '..') {
            return false;
        }
		$filename = $this->resolveMediaPath($dir . $file);
        return self::recursiveDelete($filename);
	}
    
    /**
     * @author http://fr2.php.net/manual/en/function.unlink.php
     */
    private static function recursiveDelete($file){
        if (is_file($file)) {
            return @unlink($file);
        } else if (is_dir($file)) {
            $scan = glob(rtrim($file,'/').'/*');
            foreach ($scan as $index => $path) {
                self::recursiveDelete($path);
            }
            return @rmdir($file);
        }
    }

}
