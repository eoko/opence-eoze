<?php

namespace eoko\template;

const REQUEST_PAGE_ID_PARAM = 'ajaxPageId';

const SESS_PAGES_NODE		= 'page';
const SESS_LAST_ID			= 'lastId';

/**
 * This class holds information about which css and js files have been included
 * or sent to the client. An instance of it is kept in session by ajax updating
 * pages.
 */
class HtmlPageData {
	
	private $id;
	
	private $includedCss = array();
	private $includedJs = array();
	
	private $newJs = null;
	private $newCss = null;
	
	private $lastActivity = null;
	
	public function __construct() {
		$this->id = self::getFreeId();
	}
	
	public function putInSession() {
		$node =& self::getSessionNode();
		if (!isset($node[SESS_PAGES_NODE])) {
			$node[SESS_PAGES_NODE] = array(
				$this->id => $this
			);
		} else {
			$node[SESS_PAGES_NODE][$this->id] = $this;
		}
	}
	
	private static function getFromIdInSession($id) {
		$node =& self::getSessionNode();
		unset($node[SESS_PAGES_NODE][$id]);
	}
	
	public static function getFromSession(Request $request) {
		if (null !== $id = $request->get(REQUEST_PAGE_ID_PARAM, null)) {
			return self::getFromIdInSession($id);
		} else {
			return null;
		}
	}
	
	private static function &getSessionNode() {
		return $_SESSION[__CLASS__];
	}
	
	private static function getFreeId() {
		$node =& self::getSessionNode();
		if (!isset($node[SESS_LAST_ID])) {
			return $node[SESS_LAST_ID] = 1;
		} else {
			return $node[SESS_LAST_ID] = $node[SESS_LAST_ID] + 1;
		}
	}
	
	public function pushJs($url) {
		if (!isset($this->includedJs[$url])) {
			$this->newJs[] = $url;
		}
	}
	
	public function pushCss($url) {
		if (!isset($this->includedCss[$url])) {
			$this->newCss[] = $url;
		}
	}
	
	public function getNewJsList() {
		return $this->newJs;
	}
	
	public function getNewCssList() {
		return $this->newCss;
	}
	
	private function updateLastActivity() {
		$this->lastActivity = time();
	}
	
	public function mergeInTemplate(HtmlTemplate $tpl) {
		if ($tpl instanceof HtmlRootTemplate) {
		}
	}
}