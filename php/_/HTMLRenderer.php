<?php

/**
 * @method HTMLRenderer setClass($class)
 * @method HTMLRenderer putClass($class)
 * @method HTMLRenderer pushClass($class)
 */
class HTMLRenderer {

	protected $tag;
	protected $tagParams = null;
	protected $content = null;

	public function __construct($tag, $content = null) {
		$this->tag = $tag;
		$this->content = $content;
	}

	public function __toString() {
		return get_class($this) . "[$this->tag] => $this->content";
	}

	/**
	 * @return string
	 */
	public function render() {

		$this->beforeRender();

		if ($this->tag === null) throw new IllegalStateException(
			'The tag is not set (uncomplete abstract renderer ?)'
		);

		$r = array(
			"<$this->tag",
			$this->buildTagParams(),
			'>'
		);

		if (null !== $content = $this->buildContent()) $r[] = $content;
		
		$r[] = "</$this->tag>";
		return implode('', $r);
	}

	protected function beforeRender() {
		// i like being overridden !!!
	}

	public function output() {
		echo $this->render();
	}
	
	protected function buildContent() {
		if ($this->content === null) {
			return null;
		} else if (is_string($this->content)) {
			return $this->content;
		} else if ($this->content instanceof HTMLRenderer) {
			return $this->content->render();
		} else {
			throw new IllegalStateException('I do not know what to do with $this->content ...');
		}
	}
	
	protected function buildTagParams() {
		if ($this->tagParams === null) return null;
		
		$r = array();
		
		foreach ($this->tagParams as $name => $value) {
			if (is_array($value)) {
				$value = implode(' ', $value);
			}
			$r[] = "$name=\"$value\"";
		}

		return ' ' . implode($r);
	}
	
	/**
	 * @return HTMLRenderer
	 */
	public function __call($name, $arguments) {
		if (substr($name, 0, 3) === 'set') {
			return $this->setParam(strtolower(substr($name, 3)), $arguments[0]);
		} else if (substr($name, 0, 4) === 'push') {
			return $this->pushParam(strtolower(substr($name, 4)), $arguments[0]);
		} else if (substr($name, 0, 3) === 'put') {
			return $this->putParam(strtolower(substr($name, 3)), $arguments[0]);
		} else {
			$class = get_class($this);
			throw new IllegalStateException("Method $class::$name doesn't exist");
		}
	}
	
	/**
	 * @return HTMLRenderer
	 */
	public function setParam($paramName, $value) {
		if ($value === null) {
			unset($this->tagParams[$paramName]);
		} else {
			$this->tagParams[$paramName] = is_array($value) ? $class : array(
				$value
			);
		}
		return $this;
	}
	
	/**
	 * @return HTMLRenderer
	 */
	public function pushParam($paramName, $value) {
		if ($value !== null) {
			$p =& $this->tagParams[$paramName];
			if (is_array($value)) {
				foreach ($value as $v) $p[] = $v;
			} else {
				$p[] = $value;
			}
		}
		return $this;
	}

	/**
	 * @return HTMLRenderer
	 */
	public function putParam($paramName, $value) {
		$p =& $this->tagParam[$paramName];
		if (is_array($value)) {
			foreach (array_diff($value, $p) as $v) {
				if ($v !== null) $p[] = $v;
			}
		} else {
			if ($value !== null && !array_search($value, $p, true)) {
				$p[] = $value;
			}
		}
		return $this;
	}

	/**
	 * Create an HTMLRenderer with no tag set. Notice that if nothing sets the
	 * tag before the rendering operation, this will result in an exception.
	 * @param mixed $content
	 * @return HTMLRenderer
	 */
	public static function createAbstract($content = null) {
		return new HTMLRenderer(null, $content);
	}

	/**
	 * Create either a P HTMLRenderer, if there is only one item, or an UL list
	 * if there is more than one. Items can be either plain strings, or
	 * HTMLRenderers -- in this latter case, the tag will be changed accordingly
	 * to P or LI.
	 * @param array $items
	 * @return HTMLRenderer
	 */
	public static function createULorP($items) {
		switch (count($items)) {
			case 0: return null;
			case 1:
				$item = $items[0];
				if ($item instanceof HTMLRenderer) {
					$item->tag = 'p';
					return $item;
				} else {
					return new HTMLRenderer('p', $items[0]);
				}
			default: return new HTMLRendererList('ul', $items);
		}
	}
}

class HTMLRendererList extends HTMLRenderer {

	protected $items = null;

	public function __construct($tag, $items) {
		parent::__construct($tag);
		$this->setItems($items);
	}

	public function setItems($items) {
		$this->items = array();
		return $this->addItems($items);
	}

	public function getItemAt($i) {
		return $this->items[$i];
	}

	public function addItems($items) {
		if (!is_array($items)) $items = array($items);
		foreach ($items as $item) {
			if ($item instanceof HTMLRenderer) {
				$item->tag = 'li';
				$this->items[] = $item;
			} else {
				$this->items[] = new HTMLRenderer('li', $item);
			}
		}
	}

	protected function buildContent() {
		$s = array();
		foreach ($this->items as $item) {
			$s[] = $item->render();
		}
		return implode('', $s);
	}
}