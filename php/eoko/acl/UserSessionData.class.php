<?php
/**
 * @author Éric Ortéga <eric@planysphere.fr>
 * @since 2/26/11 4:02 PM
 */

namespace eoko\acl;

use IllegalStateException;
use StringHelper;

class UserSessionData {
	
	const DEFAULT_REQ_DATA_NAME = 'sessionDataId';
	
	private $data = null;

	/**
	 * @param int $timeToLive time to live, in seconds
	 * @return UserSessionDataItem
	 */
	public function & create($timeToLive = 3600) {
		do {
			$id = StringHelper::randomString(8);
		} while(isset($this->data[$id]));

		$this->data[$id] = new UserSessionDataItem($id, $timeToLive);

		return $this->data[$id];
	}

	public function destroy($id) {
		// We don't really want to check for the session existance, which would
		// produce a completly meaningless error warning...
		unset($this->data[$id]);
		return true;
	}

	/**
	 * @param string $id the id of the data to retrieve
	 * @return UserSessionDataItem
	 */
	public function getSessionData($id) {
		return isset($this->data[$id]) ? $this->data[$id] : null;
	}

	/**
	 *
	 * @param Request $request
	 * @param string $name
	 * @return UserSessionDataItem
	 */
	public function getFromRequest(
			Request $request, 
			$require = true,
			$name = self::DEFAULT_REQ_DATA_NAME) {

		if ($require) {
			if (null === $data = $this->get($id = $request->req($name))) {
				throw new IllegalStateException("Missing session data (id=$id)");
			} else {
				return $data;
			}
		} else if (null !== $id = $request->get($name, null)) {
			return $this->get($id);
		} else {
			return null;
		}
	}
}