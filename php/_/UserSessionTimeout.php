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

/**
 * @category Eoze
 * @package UserSession
 * @since 2013-03-04 18:16
 */

/**
 * @deprecated
 */
class UserSessionTimeout extends UserException {

	public function  __construct($message = null, $errorTitle = null, $debugMessage = '', Exception $previous = null) {
		if ($message === null) $message = lang(
			'Vous avez été déconnecté suite à une longue période d\'inactivité. '
				. 'Veuillez vous identifier à nouveau pour continuer votre travail.'
		);
		if ($errorTitle === null) $errorTitle = lang('Déconnexion');

		parent::__construct($message, $errorTitle, $debugMessage, $previous);

		ExtJSResponse::put('cause', 'sessionTimeout');
	}
}
