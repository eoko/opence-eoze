<?php

namespace eoko\modules\TreeMenu;

class MenuFamily {

	/** @var string */
	private $id;
	/** @var string */
	private $label;
	private $actions;

	private $iconCls;
	
	function __construct($id, $label, $actions) {
		$this->id = $id;
		$this->label = $label;
		$this->actions = $actions;
	}

	/**
	 * Creates a new MenuFamily from configuration object.
	 * @param array $array
	 * @return MenuFamily 
	 */
	public static function fromArray(array $array) {
		$family = new MenuFamily(null, null, null);
		foreach ($array as $k => $v) {
			$family->$k = $v;
		}
		return $family;
	}

	public function getId() { return $this->id; }
	public function getLabel() { return $this->label; }
	
	public function toArray($associative = true) {
		$r = array();
		foreach ($this as $k => $v) {
			$r[$k] = $v;
		}
		$r['actions'] = array();
		foreach ($this->actions as $action) {
			if ($associative) {
				$r['actions'][$action->getId()] = $action->toArray();
			} else {
				$r['actions'][] = $action->toArray();
			}
		}
		return $r;
	}
	
	public static function __set_state($an) {
		$f = new MenuFamily(null, null, null);
		foreach ($an as $k => $v) $f->$k = $v;
		return $f;
	}
	
	/**
	 * @param string $id
	 * @return MenuAction
	 */
	public function getAction($id) {
		if (isset($this->actions[$id])) {
			return $this->actions[$id];
		} else {
			return null;
		}
	}
}