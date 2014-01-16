<?php
/**
 * Copyright (C) 2013 Eoko
 *
 * This file is part of Opence.
 *
 * Opence is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Opence is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Opence. If not, see <http://www.gnu.org/licenses/gpl.txt>.
 *
 * @copyright Copyright (C) 2013 Eoko
 * @licence http://www.gnu.org/licenses/gpl.txt GPLv3
 * @author Éric Ortega <eric@eoko.fr>
 */

namespace Eoze\Session\SaveHandler;

use Zend\Session\SaveHandler\DbTableGateway;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

/**
 * A database save handler that fires an event when sessions are destroyed.
 *
 * @category Eoze
 * @package Session
 * @subpackage SaveHandler
 * @since 2013-03-06 19:01
 */
class ObservableDbTableGateway extends DbTableGateway implements ObservableInterface {

	/**
	 * @var \Zend\EventManager\EventManagerInterface
	 */
	public $events;

	/**
	 * Retrieve the event manager
	 *
	 * Lazy-loads an EventManager instance if none registered.
	 *
	 * @return EventManagerInterface
	 */
	public function getEventManager() {
		$this->events = new EventManager(array(self::IDENTIFIER, __CLASS__, get_called_class()));
		return $this->events;
	}

	/**
	 * Overridden to fire the destroy event.
	 *
	 * @param string $id
	 * @return bool
	 */
	public function destroy($id) {
		if (parent::destroy($id)) {
			$this->getEventManager()->trigger(self::EVENT_DESTROY, $this, array($id));
		} else {
			return false;
		}
	}

	/**
	 * Overridden to fire the destroy event.
	 *
	 * @param int $maxlifetime
	 * @return bool
	 */
	public function gc($maxlifetime) {

		$events = $this->getEventManager();
		// Not implemented
		// $hasListeners = $events->hasListeners(self::EVENT_DESTROY);
		// ... so fallback to security
		$hasListeners = true;

		if ($hasListeners) {
			$platform = $this->tableGateway->getAdapter()->getPlatform();

			$select = new \Zend\Db\Sql\Select();
			$select
				->columns('id')
				->where(sprintf('%s + %s < %d',
				$platform->quoteIdentifier($this->options->getModifiedColumn()),
				$platform->quoteIdentifier($this->options->getLifetimeColumn()),
				time()
			));

			$ids = $this->tableGateway->selectWith($select);
		}

		if (parent::gc($maxlifetime)) {
			// fire event
			if ($hasListeners) {
				foreach ($ids as $id) {
					$events->trigger(self::EVENT_DESTROY, $this, array($id));
				}
			}
			// return
			return true;
		} else {
			return false;
		}
	}
}
