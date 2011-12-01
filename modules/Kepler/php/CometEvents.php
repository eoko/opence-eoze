<?php

namespace eoko\modules\Kepler;

use User;
use UserSession;
use eoko\php\SessionManager;
use eoko\security\UserSessionHandler;
use eoko\log\Logger;
use eoko\config\ConfigManager;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 nov. 2011
 */
class CometEvents {

	private static $instance;
	
	private $myQueue;
	private $othersQueue;

	private $channelListeners;
	private $initialChannelListeners;
	
	private $basePath;
	
	/**
	 * @var UserSessionHandler
	 */
	private $userSession;
	/**
	 * @var SessionManager
	 */
	private $sessionManager;
	
	private $directory = 'Kepler';
	private $channelFilename = 'channel-listeners';
	
	private function __construct($basePath, UserSessionHandler $userSession, 
			SessionManager $sessionManager) {
		
		$this->userSession = $userSession;
		$this->sessionManager = $sessionManager;
		
		// Create var dir
		$dir = $this->basePath = "$basePath/$this->directory";
		if (!file_exists($this->basePath)) {
			mkdir($this->basePath, 0700, true);
		}
		
		// Load channels
		$this->channelListeners = $this->loadChannelListeners();
		$channelListeners =& $this->channelListeners;
		
		// make a copy
		$this->initialChannelListeners = $this->channelListeners;
		
		$maxLevel = ConfigManager::get($this, 'userLevel');
		
		$userSession->addListener('login', 
			function(User $user) use($userSession, $sessionManager, &$channelListeners, $maxLevel) {
				if ($userSession->isAuthorized($maxLevel)) {
					$channelListeners[$sessionManager->getId()] = true;
				}
			}
		);
		
		$sessionManager->addListener('delete', function($sessionId) use(&$channelListeners, $dir) {
			// update channels list
			unset($channelListeners[$sessionId]);
			// delete remaining session data
			if (file_exists($file = "$dir/$sessionId")) {
				unlink($file);
			}
		});
	}
	
	public function __destruct() {
		$this->commit();
	}
	
	public function commit() {
		// Commit chanel listeners modifications
		if ($this->channelListeners !== $this->initialChannelListeners) {
			$this->saveChannelListeners();
			$this->initialChannelListeners = $this->channelListeners;
		}
		// Commit public queue
		if ($this->othersQueue) {
			$mySessionId = $this->sessionManager->getId();
			foreach ($this->channelListeners as $sessionId => $active) {
				if ($active && $sessionId !== $mySessionId) {
					$this->writeQueueIn($sessionId, $this->othersQueue);
				}
			}
			$this->othersQueue = null;
		}
		// Commit private queue
		if ($this->myQueue) {
			$this->writeQueueIn($this->sessionManager->getId(), $this->myQueue);
			$this->myQueue = null;
		}
	}
	
	private function saveChannelListeners() {
		$filename = "$this->basePath/$this->channelFilename";
		if (!file_exists($dir = dirname($filename))) {
			mkdir($dir, 0700, true);
		}
		if (!file_put_contents($filename, serialize($this->channelListeners))) {
			Logger::get($this)->error('Cannot write channels file: ' . $filename);
		}
	}
	
	private function loadChannelListeners() {
		$filename = "$this->basePath/$this->channelFilename";
		if (file_exists($filename)) {
			return unserialize(file_get_contents($filename));
		} else {
			return array();
		}
	}
	
	/**
	 * @return CometEvents
	 */
	public static function start($varPath, UserSessionHandler $userSession, SessionManager $sessionManager) {
		return self::$instance = new CometEvents($varPath, $userSession, $sessionManager);
	}
	
	private function writeQueueIn($sessionId, $queue) {
		
		$filename = "$this->basePath/$sessionId";
		$file = fopen($filename, 'a+');

		foreach ($queue as $entry) {
			fwrite($file, serialize($entry) . "\n");
		}

		fclose($file);
	}
	
	/**
	 *
	 * @param array $queue
	 * @param bool $fromOther `true` if the event originate from another **user**
	 * @param type $category
	 * @param type $class
	 * @param type $name
	 * @param array $args 
	 */
	private function pushIn(&$queue, $fromOther, $category, $class, $name, array $args = null) {
		if (is_object($class)) {
			if ($class instanceof Observable) {
				$class = $class->getCometObservableName();
			} else {
				$class = get_class($class);
			}
		}
		$queue[] = (Object) array(
			'category' => $category,
			'data'     => array(
				'class' => $class,
				'name'  => $name,
				'user'  => $this->userSession->getUserId(false),
				'args'  => $args,
				'fromOther' => $fromOther,
			),
		);
	}
	
	private function push($category, $class, $name, array $args = null) {
		$this->pushIn($this->myQueue, false, $category, $class, $name, $args);
	}
	
	private function pushToOthers($category, $class, $name, array $args = null) {
		$this->pushIn($this->othersQueue, true, $category, $class, $name, $args);
	}
	
	public static function fire($class, $name, $args = null) {
		self::$instance->push('events', $class, $name, array_slice(func_get_args(), 2));
	}
	
	public static function publish($class, $name, $args = null) {
		$args = array_slice(func_get_args(), 2);
		self::$instance->push('events', $class, $name, $args);
		self::$instance->pushToOthers('events', $class, $name, $args);
	}
	
}
