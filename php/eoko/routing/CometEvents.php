<?php

namespace eoko\routing;

use User;
use UserSession;
use eoko\php\SessionSaveHandler;
use eoko\log\Logger;

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

	private $channels;
	private $originalChannels;
	
	private $dir;
	
	private function __construct(SessionSaveHandler $sessionManager) {
		
		$dir = $this->dir = MY_EOZE_PATH . 'Kepler';
		if (!file_exists($this->dir)) {
			mkdir($this->dir, 0700, true);
		}
		
		// Load channels
		$this->channels = $this->loadChannelListeners();
		$channels =& $this->channels;
		
		// make a copy
		$this->originalChannels = $this->channels;

		UserSession::onLogin(function(User $user) use(&$channels) {
			if (UserSession::isAuthorized(UserSession::LEVEL_AUTHENTICATED)) {
				$channels[session_id()] = true;
			}
		});
		
		$sessionManager->addListener('delete', function($sessionId) use(&$channels, $dir) {
			// update channels list
			unset($channels[$sessionId]);
			// delete remaining session data
			if (file_exists($file = "$dir/$sessionId")) {
				unlink($file);
			}
		});
	}
	
	public function __destruct() {
		// Commit chanel listeners modifications
		if ($this->channels !== $this->originalChannels) {
			$this->saveChannelListeners();
		}
		// Commit public queue
		if ($this->othersQueue) {
			$mySessionId = session_id();
			foreach ($this->channels as $sessionId => $active) {
				if ($active && $sessionId !== $mySessionId) {
					$this->writeQueueIn($sessionId, $this->othersQueue);
				}
			}
		}
		// Commit private queue
		if ($this->myQueue) {
			$this->writeQueueIn(session_id(), $this->myQueue);
		}
	}
	
	private function saveChannelListeners() {
		$filename = "$this->dir/channels";
		if (!file_put_contents($filename, serialize($this->channels))) {
			Logger::get($this)->error('Cannot write channels file: ' . $filename);
		}
	}
	
	private function loadChannelListeners() {
		$filename = "$this->dir/channels";
		if (file_exists($filename)) {
			return unserialize(file_get_contents($filename));
		} else {
			return array();
		}
	}
	
	/**
	 * @return CometEvents
	 */
	public static function start(SessionSaveHandler $sessionManager) {
		return self::$instance = new CometEvents($sessionManager);
	}
	
	private function writeQueueIn($sessionId, $queue) {
		
		$filename = "$this->dir/$sessionId";
		$file = fopen($filename, 'a+');

		foreach ($queue as $entry) {
			fwrite($file, serialize($entry) . "\n");
		}

		fclose($file);
	}
	
	private function pushIn(&$queue, $category, $class, $name, array $args = null) {
		if (is_object($class)) {
			$class = get_class($class);
		}
		$userId = $user = UserSession::getUser() ? $user->getId() : null;
		$queue[] = (Object) array(
			'category' => $category,
			'data'     => array(
				'class' => $class,
				'name'  => $name,
				'user'  => $userId,
				'args'  => $args,
			),
		);
	}
	
	private function push($category, $class, $name, array $args = null) {
		$this->pushIn($this->myQueue, $category, $class, $name, $args);
	}
	
	private function pushToOthers($category, $class, $name, array $args = null) {
		$this->pushIn($this->othersQueue, $category, $class, $name, $args);
	}
	
	public static function fire($class, $name, $args = null) {
		UserSession::requireLoggedIn();
		self::$instance->push('events', $class, $name, array_slice(func_get_args(), 2));
	}
	
	public static function publish($channel, $class, $name, $args = null) {
		$args = array_slice(func_get_args(), 2);
		self::$instance->push('events', $class, $name, $args);
		self::$instance->pushToOthers('events', $class, $name, $args);
	}
	
}
