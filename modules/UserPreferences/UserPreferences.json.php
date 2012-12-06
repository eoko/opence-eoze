<?php

namespace eoko\modules\UserPreferences;

use eoko\module\executor\JsonExecutor;

use ModelTable;
use UserSession;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 5 sept. 2012
 */
class Json extends JsonExecutor {
	
	/**
	 * @return ModelTable
	 */
	private function getTable() {
		$modelName = $this->getModuleConfig()->get('model');
		$tableName = $modelName . 'Table';
		return ModelTable::getTable($tableName);
	}
	
	public function getUserPreferences() {
		
		UserSession::requireLoggedIn();

		$row = $this->getTable()->createQuery()
			->where('user_id = ?', UserSession::getUserId())
			->select('json_preferences')
			->executeSelectFirst();

		$this->preferences = $row ? $row['json_preferences'] : null;
		
		return true;
	}
	
	public function saveUserPreferences() {
		
		$userId = UserSession::getUserId();
		$table = $this->getTable();
		$record = $table->findOneWhere('user_id = ?', $userId);
		
		if (!$record) {
			$record = $table->createModel(array(
				'user_id' => $userId,
			));
		}
		
		$record->setJsonPreferences($this->request->req('jsonPreferences'));

		$record->save();
		
		return true;
	}
}
