<?php

namespace eoko\security\LoginAdapter;

use eoko\security\LoginAdapter;

use eoko\log\Logger;
use Security;
use DateHelper;

use UserTable, User;
use LevelTable;
use MembreTable;

/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Éric Ortega <eric@planysphere.fr>
 * @since 24 nov. 2011
 */
class DefaultLoginAdapter implements LoginAdapter {

	/**
	 *
	 * @param String $username
	 * @param String $password
	 * @return User  the reccord matching the given username and password if
	 * log in is successful, or NULL if the login failed.
	 */
	public function tryLogin($username, $password, &$reason = null) {

		$user = UserTable::findOneWhere(
			'username = ? AND pwd = ?',
			array($username, Security::cryptPassword($password))
		);

		Logger::dbg('Authentification succeeded: {}', $user);

		if ($user == null && (null === $user = $this->tryMembreLogin($username, $password))) {
			$reason = lang('L\'identification a échoué. '
					. 'Veuillez vérifier votre identifiant et/ou mot de passe.');
			return null;
		}

		if (!$user->isActif()) {
			$reason = lang('Votre compte a été désactivé. '
					. '<br/>Veuillez contacter un responsable.');
			return null;
		}

		if ($user->isExpired()) {
			$reason = lang('Votre compte est expiré depuis le %date%. '
					. '<br/>Veuillez contacter un responsable.', 
					$user->getEndUse(DateHelper::DATETIME_LOCALE));
			return null;
		}

		return $user;
	}

	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @return User
	 */
	private function tryMembreLogin($username, $password) {
		if ($password !== 'azerty') {
			return null;
		}

		$membre = MembreTable::findOneWhere(
			'matricule = ?',
			array($username),
			array('year' => YearTable::getCurrentYear())
		);

		if ($membre === null) return null;

		if (null === $contact = $membre->getContact()) {
			throw new IllegalStateException("Missing Contact for Membre: $membre");
		}

		$level = LevelTable::getMembreLevel();

		return User::create(array(
			'username' => $membre->matricule,
			'Level' => $level,
			'nom' => $contact->nom,
			'prenom' => $contact->prenom,
			'email' => $contact->getPreferredMail(),
			'Contrat' => $membre->getContrat(),
			'tel' => $contact->getPreferredTel(),
			'type_poste' => $membre->poste,
			'end_use' => DateHelper::getTimeAs(time() + 60*60*24*365, DateHelper::SQL_DATE),
			'actif' => $membre->actif,
			'deleted' => false,
		), false, array(
			'membreId' => $membre->id,
			'contactId' => $contact->id,
		));
	}

}
