<?php

namespace eoko\modules\TreeMenu;

use MenuNode;
use eoko\util\Arrays;

class MenuAction {
	
	/** @var string */
	private $id;
	/** @var string */
	private $label = null;
	private $action_family = null;
	private $action = null;
	private $command = null;
	private $color = null;
	private $icon = null;
	private $baseIconCls = null;
	private $expanded = null;
	private $accessLevel = null;
	
	function __construct($id, $label) {
		$this->id = $id;
		$this->label = $label;
	}
	
	public static function __set_state($an) {
		return self::fromArray($an);
	}
	
	/**
	 * @param array $array
	 * @param string $id
	 * @return MenuAction 
	 */
	public static function fromArray(array $array) {
		$a = new MenuAction(null, null);
		foreach ($a as $k => $v) {
			if (isset($array[$k])) $a->$k = $array[$k];
		}
		return $a;
	}
	
	public function getId() {
		return $this->id;
	}

	public function getLabel() {
		return $this->label;
	}
	
	public function getAccessLevel() {
		return $this->accessLevel;
	}
	
	public function toArray($config = null) {
		$r = array();
		foreach ($this as $k => $v) $r[$k] = $v;
		return Arrays::apply($r, $config);
	}
	
	public function createMenuNode($config = null) {
		// ensure action cannot be overriden (action=module.action) in the
		// menu.class methods...
		unset($config['action']);
		return MenuNode::create($this->toArray($config));
	}
}