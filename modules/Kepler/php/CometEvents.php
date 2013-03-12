<?php

namespace eoko\modules\Kepler;

use User;
use eoko\log\Logger;
use eoko\config\ConfigManager;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 29 nov. 2011
 */
class CometEvents {

	private $myQueue;
	private $othersQueue;

	private $channelListeners;
	private $initialChannelListeners;

	private $basePath;

	private $directory = 'Kepler';
	private $channelFilename = 'channel-listeners';

	private $disabled = false;

	private $id, $userId;

	public function __construct($basePath, $id) {

		$this->id = $id;

		// Create var dir
		$this->basePath = "$basePath/$this->directory";
		if (!file_exists($this->basePath)) {
			mkdir($this->basePath, 0700, true);
		}

		// Load channels
		$this->channelListeners = $this->loadChannelListeners();

		// make a copy
		$this->initialChannelListeners = $this->channelListeners;
	}

	public function __destruct() {
		$this->commit();
	}

	public function start($userId) {
		$this->userId = $userId;

		// TODO
//		$maxLevel = ConfigManager::get($this, 'userLevel');
//		if ($this->isAuthorized($maxLevel)) {
			$this->subscribeChannel($this->id);
//		}
	}

	private function subscribeChannel($subscriberId) {
		$this->channelListeners[$subscriberId] = true;
	}

	public function destroy() {
		// update channels list
		unset($this->channelListeners[$this->id]);
		// delete remaining session data
		if (file_exists($file = "$this->basePath/$this->id")) {
			unlink($file);
		}
	}

	public function commit() {
		// Commit chanel listeners modifications
		if ($this->channelListeners !== $this->initialChannelListeners) {
			$this->saveChannelListeners();
			$this->initialChannelListeners = $this->channelListeners;
		}
		// Commit public queue
		if ($this->othersQueue) {
			foreach ($this->channelListeners as $sessionId => $active) {
				if ($active && $sessionId !== $this->id) {
					$this->writeQueueIn($sessionId, $this->othersQueue);
				}
			}
			$this->othersQueue = null;
		}
		// Commit private queue
		if ($this->myQueue) {
			$this->writeQueueIn($this->id, $this->myQueue);
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
	 * @param bool $fromOther `true` if the event originate from another **session** (could be
	 * the same user in another browser)
	 * @param string $category
	 * @param string|Observable $class
	 * @param string $name
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
				'user'  => $this->userId,
				'args'  => $args,
				'fromOtherSession' => $fromOther,
			),
		);
	}

	private function push($category, $class, $name, array $args = null) {
		$this->pushIn($this->myQueue, false, $category, $class, $name, $args);
	}

	private function pushToOthers($category, $class, $name, array $args = null) {
		$this->pushIn($this->othersQueue, true, $category, $class, $name, $args);
	}

	public function fire($class, $name, $args = null) {
		$this->push('events', $class, $name, array_slice(func_get_args(), 2));
	}

	public function publish($class, $name, $args = null) {
		if (!$this->disabled) {
			$args = array_slice(func_get_args(), 2);
			$this->push('events', $class, $name, $args);
			$this->pushToOthers('events', $class, $name, $args);
		}
	}

	public function disable() {
		$this->disabled = true;
	}

}
