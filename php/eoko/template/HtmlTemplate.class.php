<?php

namespace eoko\template;
use \IllegalStateException;
use eoko\file\Finder as FileFinder, eoko\file\FileType;
use eoko\file\CannotFindFileException;

class EscapedValue {
	
	private $val;
	public $raw;
	
	function __construct($val, $raw) {
		$this->val = $val;
		$this->raw = $raw;
	}

	public function __toString() {
		return "$this->val";
	}
}

class HtmlTemplate extends Template {
	
	protected $cssIncludes = array();
	protected $jsIncludes = array();
	
	/** @var HtmlTemplate */
	private $parent = null;
	
	/** @var FileFinder */
	private $fileFinder;
	
	public static $renderPrettyDefault = true;
	public $renderPretty;
	
	public static $htmlSpecialCharsDefault = true;
	public $htmlSpecialChars;
	
	public $ajaxLinks = false;
	
	public function __construct(FileFinder $fileFinder = null, $opts = null) {
		// default options
		$this->renderPretty = self::$renderPrettyDefault;
		$this->htmlSpecialChars = self::$htmlSpecialCharsDefault;
		
		$this->fileFinder = $fileFinder;
		
		parent::__construct($opts);
	}
	
	/**
	 * @param array $opts
	 * @return Renderer
	 */
	public static function create(FileFinder $fileFinder = null, $opts = null) {
		$class = get_called_class();
		return new $class($fileFinder, $opts);
	}

	/**
	 * @return HtmlTemplate
	 */
	protected function getFirstParent() {
		if ($this->parent === null) {
			return null;
		} else {
			$parent = $this->parent;
			while ($parent->parent !== null) $parent = $parent->parent;
			return $parent;
		}
	}
	
	private function pushInclude($type, $url, $order) {
		$var = $type . 'Includes';
		
		if ($order === null) $order = \PHP_INT_MAX;
		
		if (isset($this->{$var}[$url])) {
			$order = min($order, $this->{$var}[$url]);
		}
		$this->{$var}[$url] = $order;

		if (null !== $p = $this->getFirstParent()) {
			if (isset($p->{$var}[$url])) {
				$order = min($order, $p->{$var}[$url]);
			}
			$p->{$var}[$url] = $order;
		}
	}
	
	private function pushAliasInclude($typeName, $type, $includes, $order) {
		if ($typeName !== null) {
			if (isset($includes[$typeName])) {
				foreach ($includes[$typeName] as $url) {
					$this->pushAliasIncludeLine($type, $url, $order);
				}
			}
		} else {
			foreach ($includes as $typeName => $typeIncludes) {
				if ($typeName === FileType::JS) $type = 'js';
				else if ($typeName === FileType::CSS) $type = 'css';
				else continue;
				foreach ($typeIncludes as $url) {
					$this->pushAliasIncludeLine($type, $url, $order);
				}
			}
		}
	}

	private function getFileFinder() {
		if (!$this->fileFinder) throw new IllegalStateException(
			'A FileFinder must be set to use push* methods'
		); else return $this->fileFinder;
	}
	
	private function pushAliasIncludeLine($type, $url, $order) {
		if (is_array($url)) {
			if ($url['extra'] === null) $url['extra'] = $order;
			else if ($order === null) $order = $url['extra'];
			else $order += $url['extra'];
			$url = $url['url'];
		}
		$this->pushInclude($type, $url, $order);
	}
	
	public function pushAlias($name, $order = null) {
		if (is_array($name)) {
			foreach (self::nameOrder($name, $order) as $name => $order2) {
				$this->pushAlias($name, $order);
			}
			return $this;
		}
		
		if (FileType::ALIAS() !== ($r = $this->getFileFinder()->searchPath($name, null, $url, null, true))
				|| $url === null) {

			throw new CannotFindFileException('alias ' . $name);
		} else {
			$this->pushAliasInclude(null, null, $url, $order);
		}
		
		return $this;
	}
	
	public function pushCss($name, $order = null, $require = true) {
		
		if (is_array($name)) {
			foreach (self::nameOrder($name, $order) as $css => $order) {
				$this->pushCss($css, $order, $require);
			}
			return $this;
		}

		$r = null;
		if (
			preg_match('@\w+://@', $url = $name)
			|| (null !== ($r = $this->fileFinder->searchPath($name, FileType::CSS, $url, null, $require))
				&& $url !== null)
		) {
			
			if ($r === FileType::ALIAS()) {
				$this->pushAliasInclude(FileType::CSS, 'css', $url, $order);
			} else {
				$this->pushInclude('css', $url, $order);
			}
		}

		return $this;
	}
	
	private static function nameOrder(array $name, $baseOrder) {
		$r = array();
		foreach ($name as $name => $order) {
			if (is_int($name)) {
				$name = $order;
				$order = $baseOrder;
			} else {
				if ($baseOrder === null) $order = $order;
				else if ($order === null) $order = $baseOrder;
				else $order = $order + $baseOrder;
			}
			$r[$name] = $order;
		}
		return $r;
	}
	
	public function pushJs($name, $order = null, $require = true) {

		if (is_array($name)) {
			foreach (self::nameOrder($name, $order) as $js => $order) {
				$this->pushJs($js, $order, $require);
			}
			return $this;
		}
		
		$r = null;
		if (
			preg_match('@\w+://@', $url = $name)
			|| (null !== ($r = $this->fileFinder->searchPath($name, FileType::JS, $url, null, $require))
				&& $url !== null)
		) {
			if ($r === FileType::ALIAS()) {
				$this->pushAliasInclude(FileType::JS, 'js', $url, $order);
			} else {
				$this->pushInclude('js', $url, $order);
			}
		}
		
		return $this;
	}
	
	public function findImageUrl($name, $type = FileType::IMAGE) {
		$path = $this->fileFinder->findPath($name, $type, $url);
		if ($url === null) {
			throw new \IllegalStateException("Cannot find url for file: $path ($name)");
		}
		return $url;
	}
	
	protected function setParent(HtmlTemplate $parent) {
		
		if ($this->parent !== null) {
			throw new IllegalStateException('Parent template already set');
		}
		
		$this->parent = $parent;
		$root = $this->getFirstParent();
		
		if ($this->cssIncludes !== null) {
			$root->cssIncludes = array_merge(
				$root->cssIncludes, $this->cssIncludes
			);
		}
		
		if ($this->jsIncludes !== null) {
			$root->jsIncludes = array_merge(
				$root->jsIncludes, $this->jsIncludes
			);
		}
	}

	public function set($name, $value = null, $htmlSpecialChars = null) {
		if ($value instanceof HtmlTemplate) {
			$value->setParent($this);
		}
		if (is_string($value) && (
			$htmlSpecialChars === true || ($this->htmlSpecialChars && $htmlSpecialChars !== false)
		)) {
			$this->rawVars[$name] = $value;
			$value = new EscapedValue(htmlspecialchars($value), $value);
		}
		return parent::set($name, $value);
	}
	
	public function addLinkExtra(array &$extra, $url) {
		if ($this->ajaxLinks) {
			$extra[] = <<<JS
onClick="return Oce.html.update('$url')"
JS;
		}
	}
	
}
